<?php

namespace Tests\Feature;

use App\Livewire\Admin\HouseCostDetail;
use App\Livewire\Admin\HouseCosts;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Logistik\Categories;
use App\Livewire\Logistik\Clusters;
use App\Livewire\Logistik\Dispatches;
use App\Livewire\Logistik\HouseDetail;
use App\Livewire\Logistik\HouseFinish;
use App\Livewire\Logistik\Houses;
use App\Livewire\Logistik\MaterialLog;
use App\Livewire\Logistik\Materials;
use App\Livewire\Logistik\Suppliers;
use App\Livewire\Logistik\ToolLog;
use App\Livewire\Logistik\Tools;
use App\Livewire\Logistik\WarehouseDetail;
use App\Livewire\Logistik\Warehouses;
use App\Models\House;
use App\Models\Material;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TableSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_active_material_column_shows_an_arrow_and_sorting_toggles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $warehouse = Warehouse::firstOrFail();

        Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Alpha Material',
            'code' => 'MAT-ALPHA',
            'stock' => 20,
        ]);
        Material::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Zulu Material',
            'code' => 'MAT-ZULU',
            'stock' => 5,
        ]);

        $component = Livewire::test(Materials::class)
            ->assertSet('sort', 'name_asc')
            ->assertSeeInOrder(['Alpha Material', 'Zulu Material']);

        $this->assertSame(1, substr_count($component->html(), 'table-sort-arrow'));
        $this->assertSame(1, substr_count($component->html(), 'aria-sort='));

        $component->call('sortBy', 'stock')
            ->assertSet('sort', 'stock_asc')
            ->assertSeeInOrder(['Zulu Material', 'Alpha Material'])
            ->call('sortBy', 'stock')
            ->assertSet('sort', 'stock_desc')
            ->assertSeeInOrder(['Alpha Material', 'Zulu Material']);

        $component->call('sortBy', 'stock; DROP TABLE materials')
            ->assertSet('sort', 'stock_desc');
    }

    public function test_each_query_backed_table_accepts_its_sortable_columns(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $warehouse = Warehouse::firstOrFail();
        $house = House::factory()->create();

        $components = [
            [Categories::class, [], 'name', 'name_asc'],
            [Suppliers::class, [], 'phone', 'phone_asc'],
            [Clusters::class, [], 'houses', 'houses_asc'],
            [Warehouses::class, [], 'materials', 'materials_asc'],
            [Houses::class, [], 'cost', 'cost_asc'],
            [Tools::class, [], 'category', 'category_asc'],
            [MaterialLog::class, [], 'name', 'name_asc'],
            [ToolLog::class, [], 'name', 'name_asc'],
            [UserManagement::class, [], 'role', 'role_asc'],
            [Dispatches::class, [], 'requester', 'requester_asc'],
            [HouseCosts::class, [], 'year_total', 'year_total_asc'],
            [HouseCostDetail::class, ['house' => $house], 'material', 'material_asc'],
            [WarehouseDetail::class, ['warehouse' => $warehouse], 'category', 'category_asc'],
            [HouseDetail::class, ['house' => $house], 'material', 'material_asc'],
            [HouseFinish::class, ['house' => $house], 'total', 'total_asc'],
        ];

        foreach ($components as [$class, $parameters, $field, $expected]) {
            Livewire::test($class, $parameters)
                ->call('sortBy', $field)
                ->assertSet('sort', $expected)
                ->assertHasNoErrors();
        }
    }

    public function test_dashboard_activity_sort_is_whitelisted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'logistik']));

        $this->get(route('dashboard', ['activity_sort' => 'quantity_desc']))
            ->assertOk()
            ->assertSee('aria-sort="descending"', false);

        $this->get(route('dashboard', ['activity_sort' => 'created_at desc; DROP TABLE users']))
            ->assertOk()
            ->assertSee('aria-sort="descending"', false);
    }
}
