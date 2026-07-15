<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function tools(): HasMany
    {
        return $this->hasMany(Tool::class);
    }
}
