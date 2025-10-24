@extends('layouts.admin')

@section('content')
@php
$consentType = [
'date' => "Date",
'connection' => "Connection",
'intimate' => "Intimacy"
];
$dateType = [
'dinner' => "Dinner",
'coffee_Tea' => "Coffee Tea",
'movie' => "Movie",
'drinks' => "Drinks",
'concert_Live_Music' => "Concert Live Music",
'walk_Park' => "Walk Park",
'museum_Exhibition' => "Museum Exhibition",
'activity_Game' => "Activity Game",
'home_Visit' => "Home Visit",
'trip_travel' => "Trip Travel",
'other' => "Other"
];
$IntimacyType = [
'kissing' => "kissing",
'touching_cuddling' => "Touching Cuddling",
'staying_over_sharing_bed' => "Staying Over Sharing Bed",
'sexual_activity_general' => "Sexual Activity General",
'inviting_to_private_residence' => "Inviting To Private Residence",
'other' => "Other"
];
@endphp
<div class="container-fluid py-2">
    <div class="card shadow-sm" style="border-radius: 20px;">
        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('consentRequests.editOrUpdate', $consentRequest) }}">
                @csrf
                @method('PUT')

                <div style="max-height: 90vh; overflow-y: auto; overflow-x: hidden">

                    <!-- Basic Info -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sent By</label>
                            <input type="text" class="form-control" value="{{ $consentRequest->createdBy->name ?? 'N/A' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sent To</label>
                            <input type="text" class="form-control" value="{{ $consentRequest->sentTo->name ?? 'N/A' }}" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Consent Type</label>
                            <input type="text" class="form-control" value="{{ $consentType[$consentRequest->consent_type] }}" readonly>
                        </div>
                        @if($consentRequest->date_type)
                        <div class="col-md-6">
                            <label class="form-label">Date Type</label>
                            <input type="text" class="form-control" value="{{ $dateType[$consentRequest->date_type] ?? '-' }}" readonly>
                        </div>
                        @elseif($consentRequest->intimacy_type)
                        <div class="col-md-6">
                            <label class="form-label">Intimacy Type</label>
                            <input type="text" class="form-control" value="{{ $IntimacyType[$consentRequest->intimacy_type] ?? '-' }}" readonly>
                        </div>
                        @endif
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Description</label>
                            <div class="form-control" rows="3" readonly>
                                {{ $consentRequest->other_type_description ?? '-' }}
                            </div>
                        </div>
                    </div>
                    @if($consentRequest->consent_type != 'connection')
                        <!-- Event Details -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Event Date</label>
                                <input type="text" class="form-control"
                                    value="{{ $consentRequest->event_date ? \Carbon\Carbon::parse($consentRequest->event_date)->format('Y-m-d H:i') : '-' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Event Duration (Hour)</label>
                                <input type="text" class="form-control" value="{{ $consentRequest->event_duration ?? '-' }}" readonly>
                            </div>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label">Location</label>
                            <div class="form-control" rows="3" readonly>
                                @if(is_array($consentRequest->location) || is_object($consentRequest->location))
                                {{ implode(', ', (array) $consentRequest->location) }}
                                @else
                                {{ $consentRequest->location ?? '-' }}
                                @endif
                            </div>
                        </div>
                    @endif
                    <!-- Editable Status -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="pending" {{ $consentRequest->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="accepted" {{ $consentRequest->status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="rejected" {{ $consentRequest->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <!-- <option value="expired" {{ $consentRequest->status === 'expired' ? 'selected' : '' }}>Expired</option> -->
                            <option value="cancelled" {{ $consentRequest->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-4">Update Status</button>
                <a href="{{ route('consentRequests') }}" class="btn btn-secondary rounded-pill px-4 ms-2">Back to Requests</a>

            </form>
        </div>
    </div>
</div>
@endsection