import QRCode from 'qrcode';

document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveProfileBtn');
    if (!saveBtn) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const alertBox = document.getElementById('profileAlert');

    const usernameInput = document.getElementById('profileUsername');
    const emailInput = document.getElementById('profileEmail');
    const currentPasswordInput = document.getElementById('currentPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmNewPassword');

    const sidebarUsername = document.getElementById('sidebarUsername');
    const sidebarInitial = document.getElementById('sidebarProfileInitial');

    function showAlert(box, message, type = 'danger') {
        box.className = `alert alert-${type} py-2 small mb-3`;
        box.textContent = message;
        box.classList.remove('d-none');
    }

    function hideAlert(box) {
        box.classList.add('d-none');
        box.textContent = '';
    }

    // ---------------------------------------------------------
    // 1. SAVE ACCOUNT CREDENTIALS
    // ---------------------------------------------------------
    saveBtn.addEventListener('click', async function () {
        hideAlert(alertBox);

        const username = usernameInput.value.trim();
        const email = emailInput.value.trim();
        const current_password = currentPasswordInput.value;
        const new_password = newPasswordInput.value;
        const new_password_confirmation = confirmPasswordInput.value;

        if (!username || !email) {
            return showAlert(alertBox, 'Username and email are required.');
        }

        if (new_password && new_password !== new_password_confirmation) {
            return showAlert(alertBox, 'New password confirmation does not match.');
        }

        this.disabled = true;
        this.textContent = 'SAVING...';

        try {
            const res = await fetch('/profile/update', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    username,
                    email,
                    current_password,
                    new_password,
                    new_password_confirmation,
                })
            });

            const data = await res.json();

            if (!res.ok) {
                const firstError = data.errors
                    ? Object.values(data.errors)[0][0]
                    : (data.message || 'Failed to update profile.');
                return showAlert(alertBox, firstError, 'danger');
            }

            if (data.unchanged) {
                return showAlert(alertBox, data.message, 'secondary');
            }

            if (data.success) {
                showAlert(alertBox, data.message, 'success');

                currentPasswordInput.value = '';
                newPasswordInput.value = '';
                confirmPasswordInput.value = '';

                const initial = data.user.username.charAt(0).toUpperCase();
                document.getElementById('cardDisplayUsername').textContent = data.user.username;
                document.getElementById('profileAvatarInitial').textContent = initial;

                if (sidebarUsername) sidebarUsername.textContent = data.user.username;
                if (sidebarInitial) sidebarInitial.textContent = initial;
            }
        } catch (err) {
            showAlert(alertBox, 'An error occurred while updating your profile.');
        } finally {
            this.disabled = false;
            this.textContent = 'SAVE CHANGES';
        }
    });

    // ---------------------------------------------------------
    // 2. TWO-FACTOR AUTHENTICATION (ENABLE / DISABLE)
    // ---------------------------------------------------------
    const twoFactorAlert = document.getElementById('twoFactorAlert');
    const twoFactorStatusBadge = document.getElementById('twoFactorStatusBadge');
    const twoFactorHelpText = document.getElementById('twoFactorHelpText');
    const openEnable2faBtn = document.getElementById('openEnable2faBtn');
    const openDisable2faBtn = document.getElementById('openDisable2faBtn');

    const enable2faModal = new bootstrap.Modal(document.getElementById('enable2faModal'));
    const enable2faModalAlert = document.getElementById('enable2faModalAlert');
    const qrCanvas = document.getElementById('twoFactorQrCanvas');
    const manualKeyEl = document.getElementById('twoFactorManualKey');
    const confirmCodeInput = document.getElementById('confirm2faCodeInput');
    const confirmEnable2faBtn = document.getElementById('confirmEnable2faBtn');

    const disable2faModal = new bootstrap.Modal(document.getElementById('disable2faModal'));
    const disable2faModalAlert = document.getElementById('disable2faModalAlert');
    const disablePasswordInput = document.getElementById('disable2faPasswordInput');
    const confirmDisable2faBtn = document.getElementById('confirmDisable2faBtn');

    function setTwoFactorUI(enabled) {
        if (enabled) {
            twoFactorStatusBadge.textContent = 'Enabled';
            twoFactorStatusBadge.className = 'badge bg-success px-3 py-2';
            twoFactorHelpText.textContent = 'Your account is protected with 2-step verification.';
            openEnable2faBtn.classList.add('d-none');
            openDisable2faBtn.classList.remove('d-none');
        } else {
            twoFactorStatusBadge.textContent = 'Disabled';
            twoFactorStatusBadge.className = 'badge bg-secondary px-3 py-2';
            twoFactorHelpText.textContent = '2FA is currently off. Enable it to protect your login.';
            openEnable2faBtn.classList.remove('d-none');
            openDisable2faBtn.classList.add('d-none');
        }
    }

    // Open Enable 2FA Modal & Generate QR Code
    openEnable2faBtn?.addEventListener('click', async function () {
        hideAlert(twoFactorAlert);
        hideAlert(enable2faModalAlert);
        confirmCodeInput.value = '';
        this.disabled = true;
        this.textContent = 'Preparing QR...';

        try {
            const res = await fetch('/profile/2fa/setup', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            });
            const data = await res.json();

            if (data.success) {
                manualKeyEl.textContent = data.secret.match(/.{1,4}/g).join(' ');
                await QRCode.toCanvas(qrCanvas, data.qrUri, { width: 190, margin: 1 });
                enable2faModal.show();
            } else {
                showAlert(twoFactorAlert, 'Could not initialize 2FA setup.', 'danger');
            }
        } catch (err) {
            showAlert(twoFactorAlert, 'Failed to generate 2FA QR code.', 'danger');
        } finally {
            this.disabled = false;
            this.textContent = 'Enable 2FA';
        }
    });

    // Verify 6-Digit Code & Activate 2FA
    confirmEnable2faBtn?.addEventListener('click', async function () {
        hideAlert(enable2faModalAlert);
        const code = confirmCodeInput.value.trim();

        if (!/^\d{6}$/.test(code)) {
            return showAlert(enable2faModalAlert, 'Please enter a valid 6-digit code.');
        }

        this.disabled = true;
        this.textContent = 'Verifying...';

        try {
            const res = await fetch('/profile/2fa/confirm', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ code })
            });
            const data = await res.json();

            if (!res.ok) {
                const msg = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Verification failed.');
                return showAlert(enable2faModalAlert, msg);
            }

            if (data.success) {
                enable2faModal.hide();
                setTwoFactorUI(true);
                showAlert(twoFactorAlert, data.message, 'success');
            }
        } catch (err) {
            showAlert(enable2faModalAlert, 'An error occurred during verification.');
        } finally {
            this.disabled = false;
            this.textContent = 'Verify & Activate';
        }
    });

    // Open Disable 2FA Modal
    openDisable2faBtn?.addEventListener('click', function () {
        hideAlert(twoFactorAlert);
        hideAlert(disable2faModalAlert);
        disablePasswordInput.value = '';
        disable2faModal.show();
    });

    // Confirm Disable 2FA
    confirmDisable2faBtn?.addEventListener('click', async function () {
        hideAlert(disable2faModalAlert);
        const password = disablePasswordInput.value;

        if (!password) {
            return showAlert(disable2faModalAlert, 'Please enter your current password.');
        }

        this.disabled = true;
        this.textContent = 'Disabling...';

        try {
            const res = await fetch('/profile/2fa/disable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ password })
            });
            const data = await res.json();

            if (!res.ok) {
                const msg = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Failed to disable 2FA.');
                return showAlert(disable2faModalAlert, msg);
            }

            if (data.success) {
                disable2faModal.hide();
                setTwoFactorUI(false);
                showAlert(twoFactorAlert, data.message, 'secondary');
            }
        } catch (err) {
            showAlert(disable2faModalAlert, 'An error occurred while disabling 2FA.');
        } finally {
            this.disabled = false;
            this.textContent = 'Disable 2FA';
        }
    });
});