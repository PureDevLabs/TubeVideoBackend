<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlacklistUrl extends Model
{
    use HasFactory;

    protected $fillable = ['url'];

    public function extractor(): BelongsTo
    {
        return $this->belongsTo(Extractor::class);
    }
}
