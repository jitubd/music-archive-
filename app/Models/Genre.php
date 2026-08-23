<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'drive_folder_id'];

    public function artists(): HasMany
    {
        return $this->hasMany(Artist::class);
    }
}
