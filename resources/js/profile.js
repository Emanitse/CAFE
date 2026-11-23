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

    function showAlert(message, type = 'danger') {
        alertBox.className = `alert alert-${type} py-2 small mb-3`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    saveBtn.addEventListener('click', async function () {
        hideAlert();

        const username = usernameInput.value.trim();
        const email = emailInput.value.trim();
        const current_password = currentPasswordInput.value;
        const new_password = newPasswordInput.value;
        const new_password_confirmation = confirmPasswordInput.value;

        if (!username || !email) {
            return showAlert('Username and email are required.');
        }

        if (new_password && new_password !== new_password_confirmation) {
            return showAlert('New password confirmation does not match.');
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
                return showAlert(firstError, 'danger');
            }

            if (data.unchanged) {
                return showAlert(data.message, 'secondary');
            }

            if (data.success) {
                showAlert(data.message, 'success');

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
            showAlert('An error occurred while updating your profile.');
        } finally {
            this.disabled = false;
            this.textContent = 'SAVE CHANGES';
        }
    });
});