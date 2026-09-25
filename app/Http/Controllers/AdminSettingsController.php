<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\StoreSettingsCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/SettingsPage', [
            'settings' => Setting::firstOrFail(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:400'],
            'origin_city' => ['nullable', 'string', 'max:120'],
            'origin_destination_id' => ['nullable', 'string', 'max:50'],
            'free_shipping_from' => ['nullable', 'integer', 'min:0'],
            'free_shipping_cities' => ['nullable', 'string', 'max:500'],
            'bulk_minimum' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['free_shipping_from'] = $validated['free_shipping_from'] ?? 0;
        $validated['bulk_minimum'] = $validated['bulk_minimum'] ?? 0;

        Setting::firstOrFail()->update($validated);
        StoreSettingsCache::forget();

        return back()->with('success', 'Pengaturan toko disimpan');
    }
}
