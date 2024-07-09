<?php

use Spatie\LaravelSettings\Settings;

class LicenseSettings extends Settings
{
    public string $productKey;

    public string $localKey;

    public static function group(): string
    {
        return 'license';
    }
}
