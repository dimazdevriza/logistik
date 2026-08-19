<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);
    }

    public function test_user_management_page_renders_successfully()
    {
        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('Manajemen User');
    }

    public function test_user_management_search_filter()
    {
        $targetUser = User::factory()->create(['name' => 'Budi Mandor', 'email' => 'budi@logistik.com']);

        Livewire::test(\App\Livewire\Admin\UserManagement::class)
            ->set('search', 'Budi')
            ->assertSee('Budi Mandor')
            ->call('resetFilters')
            ->assertSet('search', '');
    }
}
