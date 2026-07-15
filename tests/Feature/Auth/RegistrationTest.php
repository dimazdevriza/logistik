<?php

test('registration screen redirects to home', function () {
    $response = $this->get('/register');

    $response->assertRedirect(route('home'));
});

test('new users cannot register via post request', function () {
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(405);
});