<?php

use Spatie\LaravelSettings\Settings;

class AuthSettings extends Settings
{
    public string $method;

    public static function group(): string
    {
        return 'auth';
    }
}
