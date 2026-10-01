<?php

use App\Models\User;

test('inactive legacy accounts cannot authenticate', function () {
    $inactive = User::factory()->create(['role' => 'inactive']);

    $this->post(route('login'), [
        'email' => $inactive->email,
        'password' => 'password',
    ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
