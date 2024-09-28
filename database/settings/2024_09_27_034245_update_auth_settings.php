<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class UpdateAuthSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update(
            'auth.method',
            fn(string $method) => ''
        );
    }
};
