<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\BillingDetail;
use Carbon\Carbon; 
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Models\RecentActivity;

class SubscriptionController extends Controller
{
    
   public function updateStripeStatus()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $today = Carbon::today()->toDateString(); // Current date in Y-m-d format

        // Process users in chunks of 100 whose subscription has ended or is today
        UserDetail::whereNotNull('subscription_id')
            ->where('subscription_end_date', '<=', $today) // use plain where for varchar
            ->chunk(50, function ($users) {

                foreach ($users as $user) {
                    try {
                        $subscription = Subscription::retrieve($user->subscription_id);

                        $status = $subscription->status;
                        $item = $subscription->items->data[0]; // get first subscription item
                        $startDate = Carbon::createFromTimestamp($item->current_period_start)->toDateString();
                        $endDate   = Carbon::createFromTimestamp($item->current_period_end)->toDateString();
                        $user->subscription_status = $status;
                        $user->subscription_start_date = $startDate;
                        $user->subscription_end_date = $endDate;
                        $user->save();

                    } catch (\Exception $e) {
                        \Log::error("Stripe update failed for user_id {$user->user_id}: ".$e->getMessage());
                    }
                }

            });

        return response()->json([
            'message' => 'Stripe statuses updated successfully for expired/current subscriptions'
        ]);
    }
    
    // API to create Checkout Session
    public function createCheckoutSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan' => 'required|string',
            'website' => 'required|string', // If you need to validate user_id
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'messages' => $validator->errors(),  // Return the validation errors
            ], 400);
        }
            
        $user = Auth::user();
        $price_id  = "";
        if ($request->plan == 'yearly') {
            $price_id = env('PRICE_ID');
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid plan selected.',
            ], 400);
        }
        if ($request->website != 'weconsent.app') {
            return response()->json([
                'status' => false,
                'message' => 'Invalid website.',
            ], 400);
        }
        $minuteCacheKey = 'checkout_session_user_' . $user->id . '_minute';
        $dailyCacheKey = 'checkout_session_user_' . $user->id . '_daily';
    
        // Check 5-minute limit
        if (Cache::has($minuteCacheKey)) {
            return response()->json([
                'status' => false,
                'message' => 'You can only create a checkout session once every 2 minutes.',
            ], 429);
        }
    
        // Check daily limit
        $dailyCount = Cache::get($dailyCacheKey, 0);
        if ($dailyCount >= 10) {
            return response()->json([
                'status' => false,
                'message' => 'You have reached the daily limit of 10 checkout sessions.',
            ], 429);
        }
        \Log::info("all data", [
            'all' =>$request->all()
        ]);
        try {
            //Stripe::setApiKey('sk_test_51PYSZI2N............');
            Stripe::setApiKey(env('STRIPE_SECRET'));
    
           $session = \Stripe\Checkout\Session::create([
                'line_items' => [[
                    'price' => $price_id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'client_reference_id' => $user->id ?? null,
                'subscription_data' => [
                    'metadata' => [
                        'website' => $request->website,
                        'user_id' => $user->id,
                        'price_id' => $price_id,
                    ],
                ],
                'success_url' => 'https://www.dashboard.weconsent.app/payment/success',
                'cancel_url' => 'https://www.dashboard.weconsent.app/payment/error',
                'expires_at' => time() + 1800,
            ]);
    
            // Set 5-minute lock
            Cache::put($minuteCacheKey, true, now()->addMinutes(2));
    
            // Increment daily count and set expiration to midnight
            $expiresAt = now()->endOfDay();
            Cache::put($dailyCacheKey, $dailyCount + 1, $expiresAt);
            
            RecentActivity::create([
                'user_id' => $user->id, // or any user id
                'action' => 'Payment',
                'details' => 'Checkout session started for payment',
                'type' => 'Payment Started', // optional category
            ]);
            return response()->json([
                'status'=>true,
                'user_id'=>$user->id,
                'checkout_url' => $session->url,
            ],200);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Stripe API error: ' . $e->getMessage());
    
            return response()->json([
                'status' => false,
                'message' => 'Stripe API error: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            \Log::error('General error: ' . $e->getMessage());
    
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    
    
    
    public function webhook(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
        //$endpoint_secret = "whsec_............";
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }
        
        Log::info('Stripe webhook received', [
            'event_type' => $event->type,
            'event_id' => $event->id,
            'even data'=>$event->data->object,
        ]);
        
        
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;
            /*   
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
                
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
                
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
                
            case 'customer.subscription.deleted':
                $this->handleSubscriptionCancelled($event->data->object);
                break;
            */   
            default:
                Log::info('Unhandled webhook event', ['event_type' => $event->type]);
        }
    
        return response('Webhook handled', 200);
    }
    
    private function handleCheckoutCompleted($session)
    {
        try {
            /*Log::info('Processing checkout.session.completed', [
                'session_id' => $session->id,
                'client_reference_id' => $session->client_reference_id
            ]);
            */
            // Set Stripe API key
            //Stripe::setApiKey('');
            Stripe::setApiKey(env('STRIPE_SECRET'));
            // Get subscription details to access metadata
            //$subscription = \Stripe\Subscription::retrieve($session->subscription);
            
            $subscription = \Stripe\Subscription::retrieve([
                'id' => $session->subscription,
                'expand' => ['default_payment_method'],
            ]);
        
            $userId = $subscription->metadata->user_id ?? $session->client_reference_id;
            $website = $subscription->metadata->website ?? null;
            $priceId = $subscription->metadata->price_id ?? null;
            
            Log::info('Extracted metadata from subscription', [
                //'user_id' => $userId,
                //'website' => $website,
                //'price_id' => $priceId,
               // 'subscription_id' => $subscription->id,
                //'subscription' =>$subscription
            ]);
            
            $paymentMethodId = null;
            $cardBrand = null;
            $cardLast4 = null;
            $cardExpMonth = null;
            $cardExpYear = null;
            if ($subscription->default_payment_method) {
                $paymentMethodId = $subscription->default_payment_method->id ?? null;
    
                if (isset($subscription->default_payment_method->card)) {
                    $card = $subscription->default_payment_method->card;
                    $cardBrand = $card->brand;
                    $cardLast4 = $card->last4;
                    $cardExpMonth = $card->exp_month;
                    $cardExpYear = $card->exp_year;
                }
            } else {
                // Fallback: retrieve from customer invoice settings
                $customer = \Stripe\Customer::retrieve($subscription->customer);
                $paymentMethodId = $customer->invoice_settings->default_payment_method ?? null;
            }
            
            // Verify website
            if ($website === 'weconsent.app') {
                
                $userDetail = UserDetail::where('user_id', $userId)->first();

                if ($userDetail) {
                    $subscriptionItem = $subscription->items->data[0] ?? null;

                    $startTimestamp = $subscriptionItem->current_period_start ?? null;
                    $endTimestamp   = $subscriptionItem->current_period_end ?? null;
                    
                    
                    //Log::info('Full subscription object', $subscription->toArray());

                    $userDetail->update([
                        'subscription_id' => $subscription->id,
                        'stripe_customer_id' => $subscription->customer,
                        'stripe_price_id'=>$priceId,
                        'subscription_status' => $subscription->status,
                        'subscription_start_date'=> date('Y-m-d H:i:s', $startTimestamp),
                        'subscription_end_date'  => date('Y-m-d H:i:s', $endTimestamp),
                    ]);
                    
                    BillingDetail::create([
                        'user_id' => $userId,
                        'subscription_id' => $subscription->id,
                        'customer_id' => $subscription->customer,
                        'price_id' => $priceId,
                        'status' => $subscription->status,
                        'start_date' => date('Y-m-d H:i:s', $startTimestamp),
                        'end_date'   => date('Y-m-d H:i:s', $endTimestamp),
                    
                        // optional billing info
                        'billing_email' => $session->customer_details->email ?? null,
                        'billing_name'  => $session->customer_details->name ?? null,
                        'billing_phone' => $session->customer_details->phone ?? null,
                        'billing_address' => $session->customer_details->address->line1 ?? null,
                        
                        // payment method info
                        'payment_method_id' => $paymentMethodId,
                        'card_brand'        => $cardBrand,
                        'card_last4'        => $cardLast4,
                        'card_exp_month'    => $cardExpMonth,
                        'card_exp_year'     => $cardExpYear,
                    ]);

                }
                Cache::forget("user_detail_{$userId}");
            } else {
                Log::warning('Invalid website in metadata', ['website' => $website]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error processing checkout completed', [
                'error' => $e->getMessage(),
                'session_id' => $session->id
            ]);
        }
    }
    
    
    /*
    private function handlePaymentSucceeded($invoice)
    {
        try {
            Log::info('Processing invoice.payment_succeeded', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription
            ]);
            
            if ($invoice->subscription) {
                // Set Stripe API key
                Stripe::setApiKey('');
                
                $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
                
                $userId = $subscription->metadata->user_id ?? null;
                $website = $subscription->metadata->website ?? null;
                
                Log::info('Payment succeeded for subscription', [
                    'user_id' => $userId,
                    'website' => $website,
                    'subscription_id' => $subscription->id,
                    'amount_paid' => $invoice->amount_paid
                ]);
                
                if ($website === 'weconsent.app' && $userId) {
                    $this->updateUserPaymentStatus($userId, true, [
                        'subscription_id' => $subscription->id,
                        'invoice_id' => $invoice->id,
                        'amount_paid' => $invoice->amount_paid,
                        'event' => 'payment_succeeded'
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error processing payment succeeded', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id
            ]);
        }
    }
    
    private function handlePaymentFailed($invoice)
    {
        try {
            Log::info('Processing invoice.payment_failed', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription
            ]);
            
            if ($invoice->subscription) {
                // Set Stripe API key
                Stripe::setApiKey('');
                
                $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
                
                $userId = $subscription->metadata->user_id ?? null;
                $website = $subscription->metadata->website ?? null;
                
                Log::warning('Payment failed for subscription', [
                    'user_id' => $userId,
                    'website' => $website,
                    'subscription_id' => $subscription->id,
                    'amount_due' => $invoice->amount_due
                ]);
                
                if ($website === 'weconsent.app' && $userId) {
                    // You might want to set paid to false or handle grace period
                    $this->updateUserPaymentStatus($userId, false, [
                        'subscription_id' => $subscription->id,
                        'invoice_id' => $invoice->id,
                        'amount_due' => $invoice->amount_due,
                        'event' => 'payment_failed'
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error processing payment failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id
            ]);
        }
    }
    
    private function handleSubscriptionUpdated($subscription)
    {
        try {
            Log::info('Processing customer.subscription.updated', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status
            ]);
            
            $userId = $subscription->metadata->user_id ?? null;
            $website = $subscription->metadata->website ?? null;
            
            if ($website === 'weconsent.app' && $userId) {
                // Update based on subscription status
                $isPaid = in_array($subscription->status, ['active', 'trialing']);
                
                $this->updateUserPaymentStatus($userId, $isPaid, [
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status,
                    'event' => 'subscription_updated'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error processing subscription updated', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);
        }
    }
    
    private function handleSubscriptionCancelled($subscription)
    {
        try {
            Log::info('Processing customer.subscription.deleted', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status
            ]);
            
            $userId = $subscription->metadata->user_id ?? null;
            $website = $subscription->metadata->website ?? null;
            
            if ($website === 'weconsent.app' && $userId) {
                $this->updateUserPaymentStatus($userId, false, [
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status,
                    'event' => 'subscription_cancelled'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error processing subscription cancelled', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);
        }
    }
    
    private function updateUserPaymentStatus($userId, $paid, $logData = [])
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                Log::error('User not found for payment status update', [
                    'user_id' => $userId,
                    'log_data' => $logData
                ]);
                return;
            }
            
            // Update user's paid status
            $user->update(['paid' => $paid]);
            
            Log::info('User payment status updated successfully', array_merge([
                'user_id' => $userId,
                'user_email' => $user->email,
                'paid_status' => $paid,
                'updated_at' => now()
            ], $logData));
            
        } catch (\Exception $e) {
            Log::error('Error updating user payment status', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'paid' => $paid,
                'log_data' => $logData
            ]);
        }
    }
    */
}
