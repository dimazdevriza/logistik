<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('allocation and dispatch pages replace the separate request pages', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('logistik.alokasi'))
        ->assertOk()
        ->assertSee('Buat alokasi')
        ->assertDontSee('Permintaan Barang')
        ->assertDontSee('Riwayat Permintaan');

    $this->get(route('logistik.dispatches'))->assertOk()->assertSee('Cari alokasi');
    $this->get(route('logistik.alokasi'))->assertOk()->assertSee('Buat alokasi');
    $this->assertFalse(Route::has('logistik.requests'));
    $this->assertFalse(Route::has('mandor.requests'));
});

test('inactive legacy accounts cannot access allocation or dispatch pages', function () {
    $inactive = User::factory()->create(['role' => 'inactive']);

    $this->actingAs($inactive)
        ->get(route('logistik.alokasi'))
        ->assertForbidden();

    $this->get(route('logistik.dispatches'))->assertForbidden();
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
