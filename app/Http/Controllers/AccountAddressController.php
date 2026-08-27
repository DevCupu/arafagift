<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountAddressController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('account/AddressPage', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_primary')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $user = $request->user();

        if (! $user->addresses()->exists()) {
            $validated['is_primary'] = true;
        }

        $user->addresses()->create($validated);

        return back()->with('success', 'Alamat baru disimpan');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $address->update($this->validated($request));

        return back()->with('success', 'Alamat diperbarui');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $wasPrimary = $address->is_primary;
        $address->delete();

        if ($wasPrimary) {
            $request->user()->addresses()->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Alamat dihapus');
    }

    public function setPrimary(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $request->user()->addresses()->update(['is_primary' => false]);
        $address->update(['is_primary' => true]);

        return back()->with('success', 'Alamat utama diperbarui');
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'address_text' => ['required', 'string', 'max:400'],
        ]);
    }
}
