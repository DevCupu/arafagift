<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Setting::create(['store_name' => 'ArafahGift']);
    $this->owner = User::factory()->create();
    $this->order = Order::factory()->create(['user_id' => $this->owner->id, 'tracking_token' => str_repeat('i', 32)]);
    OrderItem::factory()->create(['order_id' => $this->order->id, 'name' => 'Paket Kurma Premium']);
});

it('shows the invoice to the tracking-token holder', function () {
    $this->get(route('order.track.invoice', $this->order->tracking_token))
        ->assertOk()
        ->assertSee($this->order->order_number)
        ->assertSee('Paket Kurma Premium');

    $this->get('/lacak/'.str_repeat('z', 32).'/invoice')->assertNotFound();
});

it('shows the invoice to the account owner only', function () {
    $this->actingAs($this->owner)->get(route('account.order.invoice', $this->order))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('account.order.invoice', $this->order))->assertForbidden();
});

it('shows the invoice to admins only', function () {
    $this->actingAs($this->owner)->get(route('admin.order.invoice', $this->order))->assertForbidden();
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.order.invoice', $this->order))->assertOk();
});
