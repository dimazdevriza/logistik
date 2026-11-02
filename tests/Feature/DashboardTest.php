<?php

use App\Models\Cluster;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Portal Kontrol')
        ->assertSee('Royal Village')
        ->assertSee('Unit terdaftar')
        ->assertDontSee('Status Server')
        ->assertDontSee('Active');
});

test('inactive legacy accounts cannot enter the application', function () {
    $inactive = User::factory()->create(['role' => 'inactive']);

    $this->actingAs($inactive)
        ->get(route('dashboard'))
        ->assertForbidden();

    $this->get(route('logistik.alokasi'))->assertForbidden();
    $this->get(route('profile.edit'))->assertForbidden();
});

test('logistik user sees assigned cluster indicator in sidebar footer', function () {
    $cluster = Cluster::create(['name' => 'Cluster Zamrud']);
    $logistik = User::factory()->create(['role' => 'logistik', 'cluster_id' => $cluster->id]);

    $this->actingAs($logistik)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cluster Zamrud');

    $unassigned = User::factory()->create(['role' => 'logistik', 'cluster_id' => null]);

    $this->actingAs($unassigned)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Belum ditugaskan');
});
