@extends('layouts.admin')

@section('content')
<style>
.content-scrollable {
    height: calc(100vh - 70px); /* adjust 70px according to your header height */
    overflow-y: auto;
    padding: 20px; /* optional padding */
}
@media screen and (max-width: 500px) {
    .consent-data{
        display: block !important;
    }
}
</style>
@php
    $consentType = [
        'date'       => "Date",
        'connection' => "Connection",
        'intimate'   => "Intimacy"
    ];
@endphp
<div class="content-scrollable">
    <div class="container-fluid py-1">

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4 ">
                <div class="card text- bg-secondary-subtle shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Users</h6>
                        <p class="card-text">{{ $totalUsers }}</p>
                        <div class="d-flex flex-column">
                            <small>Active Users: {{ $userStatusCounts['active'] ?? '0' }}</small>
                            <small>Blocked Users: {{ $userStatusCounts['blocked'] ?? '0' }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card text- bg-primary-subtle shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Created Subscriptions</h6>
                        <p class="card-text">{{ $activeSubscriptions }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card text- bg-secondary-subtle shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title">Consent Requests</h6>
                        <p class="card-text">{{ $totalConsentRequests }}</p>
                        <div class="d-flex g-2 justify-content-center consent-data">
                            <div class="d-flex flex-column consent-data">
                                <small>Pending: {{ $consentStatusCounts['pending'] ?? '0' }}</small>
                                <small>Rejected: {{ $consentStatusCounts['rejected'] ?? '0' }}</small>
                            </div>
                            <div class="d-flex flex-column consent-data ms-2">
                                <small>Accepted: {{ $consentStatusCounts['accepted'] ?? '0' }}</small>
                                <small>Cancelled: {{ $consentStatusCounts['cancelled'] ?? '0' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Records -->
        <div class="row g-3">

            <!-- Latest Users -->
            <div class="col-12 col-lg-12">
                <div class="card shadow-sm h-100" style="border-radius: 20px;">
                    <div class="card-header bg-primary text-white">Latest Users</div>
                    <div class="card-header bg- text-light"><a href="{{route('users')}}">View All</a></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Email Verified</th>
                                        <th>Registered At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($latestUsers as $user)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="{{ route('users.editOrUpdate', $user->id) }}">{{ $user->name }}</a></td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ ucfirst($user->status) }}</td>
                                        <td>{{ $user->email_verified_at ? 'Yes' : 'No' }}</td>
                                        <td>{{ $user->created_at }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No users found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-3">

            <!-- Latest Subscriptions -->
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100" style="border-radius: 20px;">
                    <div class="card-header bg-success text-white">Latest Subscriptions</div>
                    <div class="card-header bg- text-light"><a href="{{route('subscriptions.index')}}">View All</a></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Created By</th>
                                        <th>Subscription ID</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($latestSubscriptions as $subscription)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="{{ route('users.editOrUpdate', $subscription->user->id) }}">{{ $subscription->user->name }}</a></td>
                                        <td>{{ substr($subscription->subscription_id, 0, 6) }}****</td>
                                        <td>{{ ucfirst($subscription->status) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No subscriptions found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Consent Requests -->
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm h-100" style="border-radius: 20px;">
                    <div class="card-header bg-success text-light">Latest Consent Requests</div>
                    <div class="card-header bg- text-light"><a href="{{route('consentRequests')}}">View All</a></div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Created By</th>
                                        <th>Sent To</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($latestConsentRequests as $consent)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="{{ route('users.editOrUpdate', $consent->createdBy) }}">{{ $consent->createdBy->name ?? 'N/A' }}</a></td>
                                        <td><a href="{{ route('users.editOrUpdate', $consent->sentTo) }}">{{ $consent->sentTo->name ?? 'N/A' }}</a></td>
                                        <td>{{ $consentType[$consent->consent_type] }}</td>
                                        <td>{{ ucfirst($consent->status) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No consent requests found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection