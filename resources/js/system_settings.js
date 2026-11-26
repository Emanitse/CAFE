document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveSystemSettingsBtn');
    if (!saveBtn) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const alertBox = document.getElementById('systemSettingsAlert');

    const nameInput = document.getElementById('systemNameInput');
    const logoInput = document.getElementById('systemLogoInput');
    const resetLogoBtn = document.getElementById('resetSystemLogoBtn');

    const settingsPreviewImg = document.getElementById('settingsLogoPreview');
    const livePreviewImg = document.getElementById('liveBrandPreviewImg');
    const livePreviewName = document.getElementById('liveBrandPreviewName');

    const sidebarBrandName = document.getElementById('sidebarSystemName');
    const sidebarBrandLogo = document.getElementById('sidebarSystemLogo');

    let resetLogoFlag = false;

    function showAlert(message, type = 'danger') {
        alertBox.className = `alert alert-${type} py-2 small mb-3`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    // Live text preview
    nameInput.addEventListener('input', function () {
        livePreviewName.textContent = this.value.trim() || 'Cafe Ordering System';
    });

    // Live logo preview
    logoInput.addEventListener('change', function () {
        hideAlert();
        const file = this.files[0];
        if (!file) return;

        if (file.size > 3 * 1024 * 1024) {
            this.value = '';
            return showAlert('Logo image size must not exceed 3MB.');
        }

        resetLogoFlag = false;
        const url = URL.createObjectURL(file);
        settingsPreviewImg.src = url;
        livePreviewImg.src = url;
        resetLogoBtn?.classList.remove('d-none');
    });

    // Reset to default logo
    resetLogoBtn?.addEventListener('click', function () {
        hideAlert();
        logoInput.value = '';
        resetLogoFlag = true;
        const defaultUrl = this.dataset.defaultUrl;
        settingsPreviewImg.src = defaultUrl;
        livePreviewImg.src = defaultUrl;
        this.classList.add('d-none');
    });

    // Save System Settings
    saveBtn.addEventListener('click', async function () {
        hideAlert();

        const systemName = nameInput.value.trim();
        if (!systemName) {
            return showAlert('System name is required.');
        }

        const formData = new FormData();
        formData.append('system_name', systemName);
        if (logoInput.files[0]) {
            formData.append('system_logo', logoInput.files[0]);
        }
        if (resetLogoFlag) {
            formData.append('reset_logo', '1');
        }

        this.disabled = true;
        this.textContent = 'SAVING...';

        try {
            const res = await fetch('/system-settings/update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await res.json();

            if (!res.ok) {
                const firstError = data.errors
                    ? Object.values(data.errors)[0][0]
                    : (data.message || 'Failed to update system settings.');
                return showAlert(firstError, 'danger');
            }

            if (data.unchanged) {
                return showAlert(data.message, 'secondary');
            }

            if (data.success) {
                showAlert(data.message, 'success');
                logoInput.value = '';
                resetLogoFlag = false;

                settingsPreviewImg.src = data.setting.logo_url;
                livePreviewImg.src = data.setting.logo_url;
                livePreviewName.textContent = data.setting.system_name;

                if (data.setting.has_custom_logo) {
                    resetLogoBtn?.classList.remove('d-none');
                } else {
                    resetLogoBtn?.classList.add('d-none');
                }

                // Sync sidebar & document title immediately
                if (sidebarBrandName) sidebarBrandName.textContent = data.setting.system_name;
                if (sidebarBrandLogo) sidebarBrandLogo.src = data.setting.logo_url;
                document.title = data.setting.system_name;
            }
        } catch (err) {
            showAlert('An error occurred while saving system settings.');
        } finally {
            this.disabled = false;
            this.textContent = 'SAVE SYSTEM SETTINGS';
        }
    });
});