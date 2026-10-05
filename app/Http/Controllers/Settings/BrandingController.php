<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingFaviconRequest;
use App\Http\Requests\Settings\UpdateBrandingLogoRequest;
use App\Http\Requests\Settings\UpdateBrandingNameRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    /**
     * Show the branding settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Branding', [
            'branding' => Setting::getBranding(),
        ]);
    }

    /**
     * Update the application name.
     */
    public function update(UpdateBrandingNameRequest $request): RedirectResponse
    {
        Setting::set('branding_name', $request->validated('name'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('App name updated successfully.'),
        ]);

        return to_route('branding.edit');
    }

    /**
     * Upload and update the application logo.
     */
    public function updateLogo(UpdateBrandingLogoRequest $request): RedirectResponse
    {
        $oldLogo = Setting::get('branding_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $file = $request->file('logo');
        $filename = 'logo_'.time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('branding', $filename, 'public');

        Setting::set('branding_logo', $path);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('App logo updated successfully.'),
        ]);

        return to_route('branding.edit');
    }

    /**
     * Reset the application logo to default.
     */
    public function resetLogo(): RedirectResponse
    {
        $oldLogo = Setting::get('branding_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        Setting::forget('branding_logo');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('App logo reset to default.'),
        ]);

        return to_route('branding.edit');
    }

    /**
     * Upload and update the application favicon.
     */
    public function updateFavicon(UpdateBrandingFaviconRequest $request): RedirectResponse
    {
        $oldFavicon = Setting::get('branding_favicon');
        if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
            Storage::disk('public')->delete($oldFavicon);
        }

        $file = $request->file('favicon');
        $filename = 'favicon_'.time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('branding', $filename, 'public');

        Setting::set('branding_favicon', $path);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Favicon updated successfully.'),
        ]);

        return to_route('branding.edit');
    }

    /**
     * Reset the application favicon to default.
     */
    public function resetFavicon(): RedirectResponse
    {
        $oldFavicon = Setting::get('branding_favicon');
        if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
            Storage::disk('public')->delete($oldFavicon);
        }

        Setting::forget('branding_favicon');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Favicon reset to default.'),
        ]);

        return to_route('branding.edit');
    }
}
