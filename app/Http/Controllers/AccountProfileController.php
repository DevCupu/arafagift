<?php

namespace App\Http\Controllers;

use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('account/ProfilePage', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['phone' => Phone::normalize((string) $request->input('phone', ''))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user())],
            'phone' => ['nullable', 'string', 'regex:/^0[0-9]{8,15}$/'],
            'birth_date' => ['nullable', 'date'],
        ], [
            'phone.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Profil disimpan');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('success', 'Kata sandi diperbarui');
    }
}
