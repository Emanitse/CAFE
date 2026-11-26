<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $setting = SystemSetting::current();
        return view('components.system_settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'system_name' => 'required|string|max:100',
            'system_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:3072',
            'reset_logo'  => 'nullable|boolean',
        ], [
            'system_name.required' => 'System name is required.',
            'system_name.max'      => 'System name must not exceed 100 characters.',
            'system_logo.image'    => 'The uploaded logo must be a valid image file.',
            'system_logo.mimes'    => 'Logo must be a PNG, JPG, WEBP, or SVG image.',
            'system_logo.max'      => 'Logo image size must not exceed 3MB.',
        ]);

        $setting = SystemSetting::current();
        $newName = trim($validated['system_name']);
        $resetLogo = $request->boolean('reset_logo');
        $hasNewLogo = $request->hasFile('system_logo');

        if (!$hasNewLogo && !$resetLogo && $newName === $setting->system_name) {
            return response()->json([
                'success' => true,
                'unchanged' => true,
                'message' => 'No changes were made.',
            ]);
        }

        $logoPath = $setting->system_logo;

        if ($resetLogo) {
            if ($logoPath && file_exists(public_path($logoPath))) {
                @unlink(public_path($logoPath));
            }
            $logoPath = null;
        } elseif ($hasNewLogo) {
            $uploadDir = public_path('uploads/system');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if ($logoPath && file_exists(public_path($logoPath))) {
                @unlink(public_path($logoPath));
            }

            $file = $request->file('system_logo');
            $filename = 'logo_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $logoPath = 'uploads/system/' . $filename;
        }

        $setting->update([
            'system_name' => $newName,
            'system_logo' => $logoPath,
        ]);

        SystemSetting::clearCache();
        $fresh = SystemSetting::current();

        AuditLogger::logAccess('System Settings Updated', 'success', $request, [
            'details' => "Updated system branding (Name: {$fresh->system_name}" . ($hasNewLogo ? ', new logo uploaded' : ($resetLogo ? ', logo reset to default' : '')) . ").",
        ]);

        return response()->json([
            'success' => true,
            'unchanged' => false,
            'message' => 'System settings updated across all pages.',
            'setting' => [
                'system_name' => $fresh->system_name,
                'logo_url' => $fresh->logo_url,
                'has_custom_logo' => !empty($fresh->system_logo),
            ],
        ]);
    }
}