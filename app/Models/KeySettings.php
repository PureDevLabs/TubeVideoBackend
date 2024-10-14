<?php

namespace App\Models;

use App\Models\Key;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeySettings extends Model
{
    use HasFactory;

    protected $fillable = ['max_video_duration', 'key_id'];

    public function key()
    {
        return $this->belongsTo(Key::class);
    }
}
