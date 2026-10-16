<?php

use App\Models\User;

test('guests and authenticated users can view the guidebook with droyal logo and real screenshots', function () {
    $this->get(route('logistik.guide'))
        ->assertOk()
        ->assertSee('Buku Panduan')
        ->assertSee('images/logo-light.png', false)
        ->assertSee('images/guide/mobile-menu.png', false)
        ->assertSee('images/guide/alokasi-mobile.png', false);

    $user = User::factory()->create(['role' => 'logistik']);
    $this->actingAs($user)
        ->get(route('logistik.guide'))
        ->assertOk()
        ->assertSee('Panduan Sistem')
        ->assertSee('Buka Alokasi Lapangan');
});

test('guests can view print layout and download a4 pdf guidebook', function () {
    $this->get(route('logistik.guide.print'))
        ->assertOk()
        ->assertSee('Buku Panduan')
        ->assertSee('images/logo-dark.png', false)
        ->assertSee('images/guide/dashboard-desktop.png', false)
        ->assertSee('images/guide/mobile-menu.png', false);

    $this->get(route('logistik.guide.download'))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=buku-panduan-logistik-droyal-village.pdf');
});

