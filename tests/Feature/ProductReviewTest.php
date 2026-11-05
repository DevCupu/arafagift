<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->product = Product::factory()->create(['status' => 'active', 'rating' => 0, 'reviews_count' => 0]);
    $this->user = User::factory()->create();
});

function completedOrderFor(User $user, Product $product, string $status = 'completed'): Order
{
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => $status]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

    return $order;
}

function reviewPayload(array $overrides = []): array
{
    return array_merge(['rating' => 5, 'title' => 'Bagus', 'body' => 'Kemasannya rapi dan sampai tepat waktu.'], $overrides);
}

function makeReview(Product $product, array $overrides = []): ProductReview
{
    return ProductReview::create(array_merge([
        'product_id' => $product->id,
        'author_name' => 'Siti',
        'rating' => 4,
        'body' => 'Isi ulasan contoh yang cukup panjang',
        'status' => 'pending',
    ], $overrides));
}

it('accepts a pending review from a buyer with a completed order', function () {
    $order = completedOrderFor($this->user, $this->product);

    $this->actingAs($this->user)
        ->post(route('product.review.store', $this->product), reviewPayload())
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('product_reviews', [
        'product_id' => $this->product->id,
        'user_id' => $this->user->id,
        'order_id' => $order->id,
        'status' => 'pending',
    ]);
});

it('rejects reviews from non-buyers, unfinished orders and duplicates', function () {
    $this->actingAs($this->user)
        ->post(route('product.review.store', $this->product), reviewPayload())
        ->assertSessionHasErrors('review');

    completedOrderFor($this->user, $this->product, 'pending');
    $this->post(route('product.review.store', $this->product), reviewPayload())->assertSessionHasErrors('review');

    completedOrderFor($this->user, $this->product);
    $this->post(route('product.review.store', $this->product), reviewPayload())->assertSessionHasNoErrors();
    $this->post(route('product.review.store', $this->product), reviewPayload())->assertSessionHasErrors('review');

    expect(ProductReview::count())->toBe(1);
});

it('requires login and a valid rating', function () {
    $this->post(route('product.review.store', $this->product), reviewPayload())->assertRedirect('/login');

    completedOrderFor($this->user, $this->product);
    $this->actingAs($this->user)
        ->post(route('product.review.store', $this->product), reviewPayload(['rating' => 9, 'body' => 'x']))
        ->assertSessionHasErrors(['rating', 'body']);
});

it('only shows approved reviews and never leaks the admin note', function () {
    makeReview($this->product, ['status' => 'approved', 'admin_note' => 'rahasia-internal', 'body' => 'Ulasan yang disetujui admin']);
    makeReview($this->product, ['author_name' => 'Anon', 'body' => 'Ulasan yang masih menunggu']);

    $response = $this->get(route('product', $this->product->slug))->assertOk();

    expect($response->viewData('page')['props']['reviews'])->toHaveCount(1);
    $response->assertDontSee('rahasia-internal');
    $response->assertDontSee('Ulasan yang masih menunggu');
});

it('emits aggregateRating only when approved reviews exist', function () {
    $this->get(route('product', $this->product->slug))->assertDontSee('aggregateRating', false);

    makeReview($this->product, ['status' => 'approved']);
    Cache::flush();

    $this->get(route('product', $this->product->slug))->assertSee('aggregateRating', false);
});

it('lets admin moderate reviews and syncs the product rating', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $a = makeReview($this->product, ['rating' => 5]);
    $b = makeReview($this->product, ['rating' => 4]);

    $this->actingAs($admin)->put(route('admin.reviews.update', $a), ['status' => 'approved'])->assertSessionHasNoErrors();
    $this->put(route('admin.reviews.update', $b), ['status' => 'approved'])->assertSessionHasNoErrors();

    expect((float) $this->product->fresh()->rating)->toBe(4.5)->and($this->product->fresh()->reviews_count)->toBe(2);

    $this->put(route('admin.reviews.update', $b), ['status' => 'rejected']);
    expect((float) $this->product->fresh()->rating)->toBe(5.0)->and($this->product->fresh()->reviews_count)->toBe(1);

    $this->delete(route('admin.reviews.destroy', $a));
    expect($this->product->fresh()->reviews_count)->toBe(0);

    $this->get(route('admin.reviews'))->assertOk();
});

it('keeps moderation admin-only', function () {
    $review = makeReview($this->product);

    $this->actingAs($this->user)->put(route('admin.reviews.update', $review), ['status' => 'approved'])->assertForbidden();
    $this->get(route('admin.reviews'))->assertForbidden();
});
