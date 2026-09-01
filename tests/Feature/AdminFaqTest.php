<?php

use App\Models\Faq;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin add a faq', function () {
    $this->actingAs($this->admin)->post(route('admin.content.faqs.store'), [
        'question' => 'Apakah bisa COD?',
        'answer' => 'Saat ini belum tersedia, hanya transfer/QRIS.',
    ])->assertSessionHasNoErrors();

    expect(Faq::where('question', 'Apakah bisa COD?')->exists())->toBeTrue();
});

it('lets an admin update a faq', function () {
    $faq = Faq::create(['question' => 'Q lama', 'answer' => 'A lama', 'sort_order' => 0]);

    $this->actingAs($this->admin)->put(route('admin.content.faqs.update', $faq), [
        'question' => 'Q baru',
        'answer' => 'A baru',
    ])->assertSessionHasNoErrors();

    expect($faq->fresh()->question)->toBe('Q baru');
});

it('lets an admin delete a faq', function () {
    $faq = Faq::create(['question' => 'Q', 'answer' => 'A', 'sort_order' => 0]);

    $this->actingAs($this->admin)->delete(route('admin.content.faqs.destroy', $faq))
        ->assertSessionHasNoErrors();

    expect(Faq::find($faq->id))->toBeNull();
});

it('lets an admin reorder faqs', function () {
    $first = Faq::create(['question' => 'Q1', 'answer' => 'A1', 'sort_order' => 0]);
    $second = Faq::create(['question' => 'Q2', 'answer' => 'A2', 'sort_order' => 1]);

    $this->actingAs($this->admin)->patch(route('admin.content.faqs.reorder'), [
        'order' => [$second->id, $first->id],
    ])->assertSessionHasNoErrors();

    expect($second->fresh()->sort_order)->toBe(0)
        ->and($first->fresh()->sort_order)->toBe(1);
});

it('blocks non-admin users from managing faqs', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $faq = Faq::create(['question' => 'Q', 'answer' => 'A', 'sort_order' => 0]);

    $this->actingAs($user)->post(route('admin.content.faqs.store'), [
        'question' => 'x', 'answer' => 'y',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('admin.content.faqs.destroy', $faq))->assertForbidden();
});
