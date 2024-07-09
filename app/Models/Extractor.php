<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Extractor extends Model
{
    use HasFactory;

    public function blacklist_urls(): HasMany
    {
        return $this->hasMany(BlacklistUrl::class);
    }
}
