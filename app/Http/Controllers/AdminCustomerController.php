<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCustomerController extends Controller
{
    public function index(): Response
    {
        $customers = User::where('is_admin', false)
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->withMax('orders', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'city' => $user->city,
                'orders' => $user->orders_count,
                'spent' => (int) ($user->getAttributes()['orders_sum_total'] ?? 0),
                'lastOrder' => $user->orders_max_created_at,
                'joined' => $user->created_at,
                'tag' => $user->customer_tag,
            ]);

        return Inertia::render('admin/CustomersPage', ['customers' => $customers]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $customers = User::where('is_admin', false)->withCount('orders')->whereIn('id', $validated['ids'])->get();
        $blocked = $customers->filter(fn (User $c) => $c->orders_count > 0);
        $deletable = $customers->reject(fn (User $c) => $c->orders_count > 0);

        $deletable->each->delete();

        if ($blocked->isNotEmpty()) {
            $prefix = $deletable->isNotEmpty() ? "{$deletable->count()} pelanggan dihapus. " : '';

            return back()->withErrors([
                'customer' => $prefix.'Dilewati karena punya riwayat pesanan: '.$blocked->pluck('name')->implode(', ').'.',
            ]);
        }

        return back()->with('success', $deletable->count().' pelanggan dihapus');
    }

    public function show(User $customer): Response
    {
        abort_if($customer->is_admin, 404);

        return Inertia::render('admin/CustomerDetailPage', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'city' => $customer->city,
                'spent' => (int) $customer->orders()->sum('total'),
                'orders' => $customer->orders()->count(),
                'joined' => $customer->created_at,
                'tag' => $customer->customer_tag,
                'address' => $customer->addresses()->where('is_primary', true)->value('address_text'),
            ],
            'orders' => $customer->orders()->with(['items', 'shippingMethod', 'paymentMethod'])->latest()->get()->map->toCatalog()->values(),
        ]);
    }
}
