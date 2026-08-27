<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
            'free_shipping_from' => ['required', 'integer', 'min:0'],
            'free_shipping_cities' => ['nullable', 'string', 'max:500'],
            'bulk_minimum' => ['required', 'integer', 'min:0'],
        ]);

        Setting::firstOrFail()->update($validated);

        return back()->with('success', 'Pengaturan toko disimpan');
    }
}
