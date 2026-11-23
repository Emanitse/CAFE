@extends('layouts.app')

@section('content')
<div class="container-fluid pb-4">

    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-0">Profile &amp; Settings</h3>
        <small class="text-muted">Manage your account credentials and password</small>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center gap-3">
                    <div id="profileAvatarInitial"
                         class="rounded-circle bg-coffee text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 48px; height: 48px; font-size: 1.25rem;">
                        {{ strtoupper(substr($account->username, 0, 1)) }}
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0" id="cardDisplayUsername">{{ $account->username }}</h6>
                        <small class="text-muted text-capitalize">{{ $account->role }} Account</small>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div id="profileAlert" class="alert d-none py-2 small mb-3" role="alert"></div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="profileUsername" class="form-label fw-bold">Username</label>
                            <input type="text" id="profileUsername" class="form-control bg-light" value="{{ $account->username }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="profileEmail" class="form-label fw-bold">Email Address</label>
                            <input type="email" id="profileEmail" class="form-control bg-light" value="{{ $account->email }}" required>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold text-dark mb-1">Change Password</h6>
                    <small class="text-muted d-block mb-3">Leave these fields blank if you do not want to change your password.</small>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="currentPassword" class="form-label fw-bold">Current Password</label>
                            <input type="password" id="currentPassword" class="form-control bg-light" placeholder="Enter current password">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="newPassword" class="form-label fw-bold">New Password</label>
                            <input type="password" id="newPassword" class="form-control bg-light" placeholder="Min. 8 characters">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="confirmNewPassword" class="form-label fw-bold">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" class="form-control bg-light" placeholder="Re-enter new password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" id="saveProfileBtn" class="btn bg-coffee text-white fw-bold px-4 py-2 shadow-sm">
                            SAVE CHANGES
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection