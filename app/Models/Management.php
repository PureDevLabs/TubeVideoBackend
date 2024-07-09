<?php

namespace App\Models;

use App\Models\Key;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Management extends Model
{
    use HasFactory;

    protected $fillable = ['allowed_ip', 'key_id'];

    public function keys()
    {
        return $this->belongsTo(Key::class);
    }
}
