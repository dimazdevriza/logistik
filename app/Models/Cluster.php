<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function houses(): HasMany
    {
        return $this->hasMany(House::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ClusterExpense::class);
    }
}
