<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateLicenseSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('license.productKey', '');
        $this->migrator->add('license.localKey', '');
    }
}
