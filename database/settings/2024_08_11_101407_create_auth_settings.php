<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateAuthSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('auth.method', 'oauth');
    }
};
