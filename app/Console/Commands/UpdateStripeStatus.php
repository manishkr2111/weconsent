<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserDetail;
use Stripe\Stripe;
use Stripe\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateStripeStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-stripe-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Stripe subscription statuses and dates for users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Stripe subscription update...');

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $today = Carbon::today()->toDateString();

        // Process users in chunks to avoid memory issues
        UserDetail::whereNotNull('subscription_id')
            ->where('subscription_end_date', '<=', $today)
            ->chunk(50, function ($users) {
                foreach ($users as $user) {
                    try {
                        $subscription = Subscription::retrieve($user->subscription_id);

                        $status = $subscription->status;

                        // Get the first subscription item (handles new Stripe API structure)
                        $item = $subscription->items->data[0] ?? null;

                        if ($item) {
                            $startDate = Carbon::createFromTimestamp($item->current_period_start)->toDateString();
                            $endDate   = Carbon::createFromTimestamp($item->current_period_end)->toDateString();

                            $user->subscription_status = $status;
                            $user->subscription_start_date = $startDate;
                            $user->subscription_end_date = $endDate;
                            $user->save();

                            $this->info("Updated user ID {$user->user_id} | Status: $status | Start: $startDate | End: $endDate");
                        } else {
                            \Log::warning("No subscription items found for user_id {$user->user_id}");
                        }
                    } catch (\Exception $e) {
                        \Log::error("Stripe update failed for user_id {$user->user_id}: " . $e->getMessage());
                        $this->error("Error updating user ID {$user->user_id}: " . $e->getMessage());
                    }
                }
            });

        $this->info('Stripe subscription update completed.');
    }
}
