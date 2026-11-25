@extends('layouts.app')

@section('content')
<div class="container-fluid pb-4">

    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-0">Profile &amp; Settings</h3>
        <small class="text-muted">Manage your account credentials, password, and two-factor authentication</small>
    </div>

    <div class="row justify-content-center g-4">
        <!-- Account Credentials Card -->
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

        <!-- Two-Factor Authentication (2FA) Card -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Two-Factor Authentication (Authenticator App)</h6>
                        <small class="text-muted">Offline 6-digit login verification via Microsoft Authenticator</small>
                    </div>
                    <span id="twoFactorStatusBadge" class="badge {{ $account->two_factor_enabled ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
                        {{ $account->two_factor_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>

                <div class="card-body p-4">
                    <div id="twoFactorAlert" class="alert d-none py-2 small mb-3" role="alert"></div>

                    <p class="text-muted small mb-3">
                        Add an extra layer of security to your account. When enabled, logging in requires a rotating 6-digit verification code generated offline by <strong>Microsoft Authenticator</strong> (or any TOTP authenticator app).
                    </p>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <small class="text-muted" id="twoFactorHelpText">
                            {{ $account->two_factor_enabled
                                ? 'Your account is protected with 2-step verification.'
                                : '2FA is currently off. Enable it to protect your login.' }}
                        </small>

                        <button type="button" id="openEnable2faBtn" class="btn bg-coffee text-white fw-bold px-4 {{ $account->two_factor_enabled ? 'd-none' : '' }}">
                            Enable 2FA
                        </button>

                        <button type="button" id="openDisable2faBtn" class="btn btn-outline-danger fw-bold px-4 {{ $account->two_factor_enabled ? '' : 'd-none' }}">
                            Disable 2FA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ENABLE 2FA MODAL (QR CODE SETUP) -->
<div class="modal fade" id="enable2faModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold">Set Up Microsoft Authenticator</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="enable2faModalAlert" class="alert alert-danger d-none py-2 small text-start"></div>

                <p class="small text-muted mb-3">
                    1. Open <strong>Microsoft Authenticator</strong> on your phone, tap <strong>+</strong> &rarr; <strong>Other account (Google, Facebook, etc.)</strong>, and scan this QR code:
                </p>

                <div class="d-inline-block p-3 bg-white border rounded shadow-sm mb-3">
                    <canvas id="twoFactorQrCanvas"></canvas>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Or enter this setup key manually:</small>
                    <code id="twoFactorManualKey" class="fs-6 fw-bold text-dark user-select-all"></code>
                </div>

                <hr>

                <div class="text-start mb-3">
                    <label for="confirm2faCodeInput" class="form-label fw-bold small">
                        2. Enter the 6-digit code shown in the app to confirm:
                    </label>
                    <input type="text"
                           id="confirm2faCodeInput"
                           class="form-control form-control-lg text-center fw-bold bg-light"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           placeholder="000000"
                           autocomplete="one-time-code">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmEnable2faBtn" class="btn bg-coffee text-white fw-bold px-4">
                        Verify &amp; Activate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DISABLE 2FA CONFIRMATION MODAL -->
<div class="modal fade" id="disable2faModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold">Disable Two-Factor Authentication</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="disable2faModalAlert" class="alert alert-danger d-none py-2 small"></div>

                <p class="small text-muted mb-3">
                    Enter your current account password to confirm disabling 2FA:
                </p>

                <div class="mb-4">
                    <label for="disable2faPasswordInput" class="form-label fw-bold small">Current Password</label>
                    <input type="password" id="disable2faPasswordInput" class="form-control bg-light" placeholder="Enter your password">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDisable2faBtn" class="btn btn-danger fw-bold px-4">
                        Disable 2FA
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection