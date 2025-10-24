@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
            @endif

            <form method="POST" action="{{ route('users.editOrUpdate', $user) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="overflow-auto pe-3" style="max-height: 85vh;">

                    <!-- Profile Header Section -->
                    <div class="text-center mb-4 pb-4 border-bottom">
                        <div class="position-relative d-inline-block mb-3">
                            <img src="{{ $user->detail?->profile_image ? asset('storage/' . $user->detail->profile_image) : asset('storage/profile_images/default_image.jpg') }}"
                                alt="Profile Image"
                                class="img-fluid rounded-circle shadow"
                                style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #fff;">
                        </div>
                        <div class="mt-3">
                            <label class="btn btn-outline-primary btn-sm rounded-pill px-4">
                                <i class="fas fa-upload me-2"></i>Change Photo
                                <input type="file" name="profile_image" class="d-none" accept="image/*">
                            </label>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="mb-4">
                        <h5 class="mb-3 fw-bold text-dark">
                            <i class="fas fa-user-circle me-2 text-primary"></i>Account Information
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Full Name</label>
                                <input type="text" name="name" class="form-control rounded-3" value="{{ old('name', $user->name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Email Address</label>
                                <input type="email" name="email" class="form-control rounded-3" value="{{ old('email', $user->email) }}">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Email Verified</label>
                                <select name="email_verified" class="form-select rounded-3">
                                    <option value="1" {{ $user->email_verified_at ? 'selected' : '' }}>Verified</option>
                                    <option value="0" {{ !$user->email_verified_at ? 'selected' : '' }}>Not Verified</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">ID Verified</label>
                                <select name="id_verified" class="form-select rounded-3">
                                    <option value="1" {{ $user->id_verified ? 'selected' : '' }}>Verified</option>
                                    <option value="0" {{ !$user->id_verified ? 'selected' : '' }}>Not Verified</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Created At</label>
                                <input type="text" class="form-control bg-light rounded-3" value="{{ $user->created_at->format('Y-m-d H:i') }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Updated At</label>
                                <input type="text" class="form-control bg-light rounded-3" value="{{ $user->updated_at->format('Y-m-d H:i') }}" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Account Status</label>
                                <select name="status" class="form-select rounded-3">
                                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="blocked" {{ old('status', $user->status) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    @if($user->detail)
                    <!-- Personal Details -->
                    <div class="mb-4 pt-3 border-top">
                        <h5 class="mb-3 fw-bold text-dark">
                            <i class="fas fa-id-card me-2 text-primary"></i>Personal Details
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Username</label>
                                <input type="text" name="user_name" class="form-control rounded-3" value="{{ old('user_name', $user->detail->user_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Phone Number</label>
                                <input type="text" name="phone" class="form-control rounded-3" value="{{ old('phone', $user->detail->phone) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Date of Birth</label>
                                <input type="date" name="dob" class="form-control rounded-3" value="{{ old('dob', $user->detail->dob) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Gender & Identity -->
                    <div class="mb-4 pt-3 border-top">
                        <h5 class="mb-3 fw-bold text-dark">
                            <i class="fas fa-user-friends me-2 text-primary"></i>Gender & Identity
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Gender</label>
                                @php $genders = ['male','female','other']; @endphp
                                <select name="gender" class="form-select rounded-3">
                                    @if($user->detail->gender && !in_array($user->detail->gender, $genders))
                                    <option value="{{ $user->detail->gender }}" selected>{{ ucfirst($user->detail->gender) }}</option>
                                    @endif
                                    @foreach($genders as $option)
                                    <option value="{{ $option }}" {{ old('gender', $user->detail->gender) === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="gender_other" class="form-control rounded-3 mt-2" value="{{ old('gender_other', $user->detail->gender === 'other' ? $user->detail->gender_other : '') }}" placeholder="Specify if other">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Pronouns</label>
                                @php $pronouns = ['he/him','she/her','they/them','he/they','she/they','other']; @endphp
                                <select name="pronouns" class="form-select rounded-3">
                                    @if($user->detail->pronouns && !in_array($user->detail->pronouns, $pronouns))
                                    <option value="{{ $user->detail->pronouns }}" selected>{{ $user->detail->pronouns }}</option>
                                    @endif
                                    @foreach($pronouns as $option)
                                    <option value="{{ $option }}" {{ old('pronouns', $user->detail->pronouns) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="pronouns_other" class="form-control rounded-3 mt-2" value="{{ old('pronouns_other', $user->detail->pronouns === 'other' ? $user->detail->pronouns_other : '') }}" placeholder="Specify if other">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Gender Identity</label>
                                @php $genderIdentities = ['male','female','trans-male','trans-female','non-binary','genderqueer','genderfluid','agender','other']; @endphp
                                <select name="gender_identity" class="form-select rounded-3">
                                    @if($user->detail->gender_identity && !in_array($user->detail->gender_identity, $genderIdentities))
                                    <option value="{{ $user->detail->gender_identity }}" selected>{{ ucfirst($user->detail->gender_identity) }}</option>
                                    @endif
                                    @foreach($genderIdentities as $option)
                                    <option value="{{ $option }}" {{ old('gender_identity', $user->detail->gender_identity) === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="gender_identity_other" class="form-control rounded-3 mt-2" value="{{ old('gender_identity_other', $user->detail->gender_identity === 'other' ? $user->detail->gender_identity_other : '') }}" placeholder="Specify if other">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Sexual Orientation</label>
                                @php $orientations = ['heterosexual','gay','lesbian','bisexual','pansexual','asexual','queer','demisexual','questioning','other']; @endphp
                                <select name="gender_orientation" class="form-select rounded-3">
                                    @if($user->detail->gender_orientation && !in_array($user->detail->gender_orientation, $orientations))
                                    <option value="{{ $user->detail->gender_orientation }}" selected>{{ ucfirst($user->detail->gender_orientation) }}</option>
                                    @endif
                                    @foreach($orientations as $option)
                                    <option value="{{ $option }}" {{ old('gender_orientation', $user->detail->gender_orientation) === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="gender_orientation_other" class="form-control rounded-3 mt-2" value="{{ old('gender_orientation_other', $user->detail->gender_orientation === 'other' ? $user->detail->gender_orientation_other : '') }}" placeholder="Specify if other">
                            </div>
                        </div>
                    </div>

                    <!-- Bio Section -->
                    <div class="mb-4 pt-3 border-top">
                        <h5 class="mb-3 fw-bold text-dark">
                            <i class="fas fa-align-left me-2 text-primary"></i>Bio
                        </h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted">About</label>
                                <textarea name="bio" class="form-control rounded-3" rows="4">{{ old('bio', $user->detail->bio) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription -->
                    <div class="mb-4 pt-3 border-top">
                        <h5 class="mb-3 fw-bold text-dark">
                            <i class="fas fa-crown me-2 text-primary"></i>Subscription Details
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Status</label>
                                <input type="text" name="subscription_status" class="form-control rounded-3" value="{{ old('subscription_status', $user->detail->subscription_status) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Start Date</label>
                                <input type="date" name="subscription_start_date" class="form-control rounded-3" value="{{ old('subscription_start_date', $user->detail->subscription_start_date) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">End Date</label>
                                <input type="date" name="subscription_end_date" class="form-control rounded-3" value="{{ old('subscription_end_date', $user->detail->subscription_end_date) }}">
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

                <!-- Action Buttons -->
                <div class="mt-4 pt-3 d-flex gap-2 border-top">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-save me-2"></i>Update User
                    </button>
                    <a href="{{ route('users') }}" class="btn btn-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection