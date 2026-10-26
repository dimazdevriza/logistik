<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address'];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    public function tools(): HasMany
    {
        return $this->hasMany(Tool::class);
    }

    public function toolBalances(): HasMany
    {
        return $this->hasMany(ToolWarehouseBalance::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'source_warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'destination_warehouse_id');
    }

    public function hasInventory(): bool
    {
        return $this->materials()->exists() || $this->stockIns()->exists() || $this->toolBalances()->exists() || $this->tools()->exists();
    }
}
