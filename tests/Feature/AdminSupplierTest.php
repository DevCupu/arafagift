<?php

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin create, update, and list suppliers', function () {
    $this->actingAs($this->admin)->post(route('admin.suppliers.store'), [
        'name' => 'CV Kurma Sejahtera',
        'phone' => '0812-1111-2222',
        'email' => 'sales@kurmasejahtera.id',
        'address' => 'Jl. Sawah Besar 12, Jakarta',
        'note' => 'Spesialis kurma',
    ])->assertSessionHasNoErrors();

    $supplier = Supplier::where('name', 'CV Kurma Sejahtera')->first();
    expect($supplier->phone)->toBe('0812-1111-2222');

    $this->actingAs($this->admin)->put(route('admin.suppliers.update', $supplier), [
        'name' => 'CV Kurma Sejahtera Jaya',
        'phone' => $supplier->phone,
        'email' => $supplier->email,
        'address' => $supplier->address,
        'note' => null,
    ])->assertSessionHasNoErrors();

    expect(Supplier::find($supplier->id)->name)->toBe('CV Kurma Sejahtera Jaya')
        ->and(Supplier::count())->toBe(1);
});

it('blocks deleting a supplier that still has products', function () {
    $supplier = Supplier::factory()->create();
    Product::factory()->create(['supplier_id' => $supplier->id]);

    $this->actingAs($this->admin)->delete(route('admin.suppliers.destroy', $supplier))
        ->assertSessionHasErrors('supplier');

    expect(Supplier::find($supplier->id))->not->toBeNull();
});

it('deletes an unused supplier', function () {
    $supplier = Supplier::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.suppliers.destroy', $supplier))
        ->assertSessionHasNoErrors();

    expect(Supplier::find($supplier->id))->toBeNull();
});
