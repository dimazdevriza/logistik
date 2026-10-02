<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('allocation page replaces the separate request and dispatch pages', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('logistik.alokasi'))
        ->assertOk()
        ->assertSee('Buat alokasi')
        ->assertDontSee('Permintaan Barang')
        ->assertDontSee('Riwayat Permintaan');

    $this->get(route('logistik.alokasi'))->assertOk()->assertSee('Buat alokasi');
    $this->assertFalse(Route::has('logistik.dispatches'));
    $this->assertFalse(Route::has('logistik.requests'));
    $this->assertFalse(Route::has('mandor.requests'));
});

test('inactive legacy accounts cannot access the allocation page', function () {
    $inactive = User::factory()->create(['role' => 'inactive']);

    $this->actingAs($inactive)
        ->get(route('logistik.alokasi'))
        ->assertForbidden();

    $this->assertFalse(Route::has('logistik.dispatches'));
});

test('house costs stay in admin navigation and are hidden from logistics navigation', function () {
    $logistics = User::factory()->create(['role' => 'logistik']);

    $this->actingAs($logistics)
        ->get(route('logistik.alokasi'))
        ->assertOk()
        ->assertDontSee('Biaya Rumah');

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('logistik.alokasi'))
        ->assertOk()
        ->assertSee('Biaya Rumah');
});
