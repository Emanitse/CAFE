@extends('layouts.app')

@section('content')
<div class="container-fluid pb-4">

    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-0">System Settings</h3>
        <small class="text-muted">Superadmin global branding configuration — changes sync automatically across all pages</small>
    </div>

    <div class="row g-4">
        <!-- Configuration Form Card -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark mb-0">Application Identity &amp; Logo</h6>
                </div>

                <div class="card-body p-4">
                    <div id="systemSettingsAlert" class="alert d-none py-2 small mb-3" role="alert"></div>

                    <div class="mb-4">
                        <label for="systemNameInput" class="form-label fw-bold">System Name</label>
                        <input type="text"
                               id="systemNameInput"
                               class="form-control bg-light"
                               maxlength="100"
                               value="{{ $setting->system_name }}"
                               placeholder="e.g. Cafe Ordering System"
                               required>
                        <small class="text-muted">Displayed on the Login screen, navigation sidebar, browser title, and exported reports.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold d-block">System Logo</label>
                        <div class="d-flex flex-wrap align-items-center gap-3 p-3 bg-light border rounded">
                            <div class="bg-white border rounded p-2 d-flex align-items-center justify-content-center" style="width: 84px; height: 84px;">
                                <img id="settingsLogoPreview"
                                     src="{{ $setting->logo_url }}"
                                     alt="System Logo"
                                     class="img-fluid"
                                     style="max-height: 68px; max-width: 68px; object-fit: contain;">
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <label for="systemLogoInput" class="btn btn-sm bg-coffee text-white fw-bold mb-0" style="cursor: pointer;">
                                        Choose New Logo
                                    </label>
                                    <input type="file" id="systemLogoInput" class="d-none" accept="image/png,image/jpeg,image/webp,image/svg+xml">

                                    <button type="button"
                                            id="resetSystemLogoBtn"
                                            class="btn btn-sm btn-outline-secondary fw-bold {{ $setting->system_logo ? '' : 'd-none' }}"
                                            data-default-url="{{ asset('images/cofee-bean.png') }}">
                                        Reset to Default
                                    </button>
                                </div>
                                <small class="text-muted d-block">Recommended: Square PNG, SVG, or WEBP with transparent background (Max 3MB).</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" id="saveSystemSettingsBtn" class="btn bg-coffee text-white fw-bold px-4 py-2 shadow-sm">
                            SAVE SYSTEM SETTINGS
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Branding Preview Card -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark mb-0">Live Branding Preview</h6>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center align-items-center text-center bg-light">
                    <div class="bg-white border rounded-4 shadow-sm p-4 w-100">
                        <img id="liveBrandPreviewImg"
                             src="{{ $setting->logo_url }}"
                             alt="Brand Preview"
                             class="mb-3"
                             style="max-width: 110px; max-height: 110px; object-fit: contain;">
                        <h4 id="liveBrandPreviewName" class="fw-bold text-brown mb-1">{{ $setting->system_name }}</h4>
                        <small class="text-muted">Login &amp; Sidebar Brand Preview</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection