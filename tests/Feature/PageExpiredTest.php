<?php

use App\Models\User;

test('page expired logs out authenticated user and redirects to home', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['_token' => 'valid-token'])
        ->post(route('logout'), ['_token' => 'invalid-token']);

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

test('page expired redirects guest to home', function () {
    $response = $this->withSession(['_token' => 'valid-token'])
        ->post(route('payment.verify'), ['_token' => 'invalid-token']);

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});
