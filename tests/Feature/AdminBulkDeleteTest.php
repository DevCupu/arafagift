<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Supplier;
use App\Models\Testimonial;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('bulk deletes orders and restores their deducted stock', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $orders = Order::factory()->count(2)->create(['status' => 'processing']);
    foreach ($orders as $order) {
        $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'art' => $product->art, 'qty' => 2, 'price' => $product->price]);
        \App\Services\Inventory::lock($product, fn ($p) => \App\Services\Inventory::apply($p, 'sale', -2, $this->admin->id, null, 'test', $order->order_number));
    }
    expect($product->fresh()->stock)->toBe(6);

    $this->actingAs($this->admin)->delete(route('admin.orders.bulkDestroy'), [
        'order_numbers' => $orders->pluck('order_number')->all(),
    ])->assertSessionHasNoErrors();

    expect(Order::count())->toBe(0);
    expect(Order::withTrashed()->count())->toBe(2);
    expect($product->fresh()->stock)->toBe(10);
});

it('bulk deletes products', function () {
    $products = Product::factory()->count(3)->create();

    $this->actingAs($this->admin)->delete(route('admin.products.bulkDestroy'), [
        'ids' => $products->pluck('id')->all(),
    ])->assertSessionHasNoErrors();

    expect(Product::count())->toBe(0);
});

it('bulk deletes categories but skips ones that still have products', function () {
    $empty = Category::factory()->count(2)->create();
    $used = Category::factory()->create();
    Product::factory()->create(['category_id' => $used->id]);

    $this->actingAs($this->admin)->delete(route('admin.categories.bulkDestroy'), [
        'ids' => [...$empty->pluck('id')->all(), $used->id],
    ])->assertSessionHasErrors('category');

    expect(Category::count())->toBe(1);
    expect(Category::find($used->id))->not->toBeNull();
});

it('bulk deletes suppliers but skips ones still used by products', function () {
    $empty = Supplier::factory()->create();
    $used = Supplier::factory()->create();
    Product::factory()->create(['supplier_id' => $used->id]);

    $this->actingAs($this->admin)->delete(route('admin.suppliers.bulkDestroy'), [
        'ids' => [$empty->id, $used->id],
    ])->assertSessionHasErrors('supplier');

    expect(Supplier::count())->toBe(1);
    expect(Supplier::find($used->id))->not->toBeNull();
});

it('bulk deletes promotions', function () {
    $promotions = Promotion::factory()->count(2)->create();

    $this->actingAs($this->admin)->delete(route('admin.promotions.bulkDestroy'), [
        'ids' => $promotions->pluck('id')->all(),
    ])->assertSessionHasNoErrors();

    expect(Promotion::count())->toBe(0);
});

it('bulk deletes testimonials', function () {
    $testimonials = Testimonial::factory()->count(2)->create();

    $this->actingAs($this->admin)->delete(route('admin.content.testimonials.bulkDestroy'), [
        'ids' => $testimonials->pluck('id')->all(),
    ])->assertSessionHasNoErrors();

    expect(Testimonial::count())->toBe(0);
});

it('bulk deletes faqs', function () {
    $faqs = Faq::factory()->count(2)->create();

    $this->actingAs($this->admin)->delete(route('admin.content.faqs.bulkDestroy'), [
        'ids' => $faqs->pluck('id')->all(),
    ])->assertSessionHasNoErrors();

    expect(Faq::count())->toBe(0);
});

it('blocks non-admin users from bulk deleting', function () {
    $products = Product::factory()->count(2)->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->delete(route('admin.products.bulkDestroy'), [
        'ids' => $products->pluck('id')->all(),
    ])->assertForbidden();

    expect(Product::count())->toBe(2);
});
