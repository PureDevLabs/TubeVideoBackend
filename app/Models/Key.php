<?php

namespace App\Models;

use App\Models\Management;
use App\Models\KeySettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Key extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'apikey'];

    public function management()
    {
        return $this->hasMany(Management::class);
    }

    public function keySettings()
    {
        return $this->hasOne(KeySettings::class);
    }

    protected static function booted()
    {
        static::created(function(Model $key) {
            $keySettings = new KeySettings();
            $keySettings->key_id = $key->id;
            $keySettings->save();
        });
    }
}
