<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

// ── Gerakan stok manual ──

it('records an inbound purchase with supplier and document number', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $supplier = Supplier::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.inventory.record', $product), [
        'type' => 'purchase',
        'qty' => 5,
        'supplier_id' => $supplier->id,
        'note' => 'PO-2026-001',
    ])->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(15);

    $movement = StockMovement::where('product_id', $product->id)->where('type', 'purchase')->first();
    expect($movement->delta)->toBe(5)
        ->and($movement->balance_before)->toBe(10)
        ->and($movement->balance_after)->toBe(15)
        ->and($movement->supplier_id)->toBe($supplier->id)
        ->and($movement->user_id)->toBe($this->admin->id)
        ->and($movement->document_number)->toStartWith('IN-');
});

it('records an outbound damage movement requiring a note', function () {
    $product = Product::factory()->create(['stock' => 8]);

    $this->actingAs($this->admin)->post(route('admin.inventory.record', $product), [
        'type' => 'damage',
        'qty' => 2,
    ])->assertSessionHasErrors('note');

    expect($product->fresh()->stock)->toBe(8);

    $this->actingAs($this->admin)->post(route('admin.inventory.record', $product), [
        'type' => 'damage',
        'qty' => 2,
        'note' => 'Kemasan penyok saat pengiriman',
    ])->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(6);

    $movement = StockMovement::where('product_id', $product->id)->where('type', 'damage')->first();
    expect($movement->delta)->toBe(-2)
        ->and($movement->document_number)->toStartWith('OUT-');
});

it('blocks movements that would push stock below zero', function () {
    $product = Product::factory()->create(['stock' => 3]);

    $this->actingAs($this->admin)->post(route('admin.inventory.record', $product), [
        'type' => 'loss',
        'qty' => 5,
        'note' => 'Stok opname',
    ]);

    expect($product->fresh()->stock)->toBe(3)
        ->and(StockMovement::where('product_id', $product->id)->where('type', 'loss')->exists())->toBeFalse();
});

it('supports signed opname adjustments with ADJ documents', function () {
    $product = Product::factory()->create(['stock' => 20]);

    $this->actingAs($this->admin)->post(route('admin.inventory.record', $product), [
        'type' => 'adjustment',
        'delta' => -3,
        'note' => 'Selisih hitung fisik',
    ])->assertSessionHasNoErrors();

    $movement = StockMovement::where('product_id', $product->id)->where('type', 'adjustment')->first();
    expect($product->fresh()->stock)->toBe(17)
        ->and($movement->document_number)->toStartWith('ADJ-');
});

it('updates the low stock threshold inline', function () {
    $product = Product::factory()->create(['low_stock_threshold' => 5]);

    $this->actingAs($this->admin)->patch(route('admin.inventory.threshold', $product), [
        'low_stock_threshold' => 12,
    ])->assertSessionHasNoErrors();

    expect($product->fresh()->low_stock_threshold)->toBe(12);
});

// ── Riwayat & ekspor ──

it('returns the movement history as json with balances', function () {
    $product = Product::factory()->create(['stock' => 4]);
    StockMovement::create([
        'product_id' => $product->id,
        'user_id' => $this->admin->id,
        'type' => 'initial',
        'delta' => 4,
        'balance_before' => 0,
        'balance_after' => 4,
        'document_number' => 'IN-20260824-0001',
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.inventory.history', $product));

    $response->assertOk()
        ->assertJsonPath('movements.0.type', 'initial')
        ->assertJsonPath('movements.0.balanceBefore', 0)
        ->assertJsonPath('movements.0.balanceAfter', 4)
        ->assertJsonPath('movements.0.inbound', 4)
        ->assertJsonPath('movements.0.operator', $this->admin->name);
});

it('exports the inventory as csv', function () {
    $product = Product::factory()->create([
        'sku' => 'AGF-CSV-01',
        'stock' => 7,
        'low_stock_threshold' => 3,
        'cost' => 10000,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.inventory.export'));

    $response->assertOk();
    $body = trim((string) $response->streamedContent());
    expect(str_starts_with($body, 'SKU,Nama,Kategori,Satuan,Lokasi,Supplier,Stok'))->toBeTrue()
        ->and($body)->toContain('Batas Menipis')
        ->and($body)->toContain('AGF-CSV-01')
        ->and($body)->toContain('70000');
});

// ── Integrasi pesanan ──

function orderWithItem(int $stock, int $qty): array
{
    $order = Order::factory()->create(['status' => 'paid']);
    $product = Product::factory()->create(['stock' => $stock]);

    $item = new OrderItem([
        'name' => $product->name,
        'sku' => $product->sku,
        'art' => $product->art,
        'qty' => $qty,
        'price' => $product->price,
    ]);
    $item->order()->associate($order);
    $item->product()->associate($product);
    $item->save();

    return [$order, $product];
}

it('deducts stock once when an order enters processing or shipped', function () {
    [$order, $product] = orderWithItem(stock: 10, qty: 4);

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), ['status' => 'processing'])
        ->assertSessionHasNoErrors();
    expect($product->fresh()->stock)->toBe(6);

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), ['status' => 'shipped'])
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(6)
        ->and(StockMovement::where('product_id', $product->id)->where('type', 'sale')->count())->toBe(1);
});

it('restores stock when a deducted order is cancelled and never double-restores', function () {
    [$order, $product] = orderWithItem(stock: 10, qty: 4);

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), ['status' => 'processing']);
    $this->actingAs($this->admin)->put(route('admin.order.update', $order), ['status' => 'cancelled'])
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(10);

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), ['status' => 'pending']);

    expect($product->fresh()->stock)->toBe(10)
        ->and(StockMovement::where('product_id', $product->id)->where('type', 'sale_cancelled')->count())->toBe(1);
});

it('rejects the status transition when the stock is insufficient', function () {
    [$order, $product] = orderWithItem(stock: 2, qty: 4);

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), ['status' => 'processing'])
        ->assertSessionHasErrors();

    expect($product->fresh()->stock)->toBe(2)
        ->and($order->fresh()->status)->toBe('paid');
});
