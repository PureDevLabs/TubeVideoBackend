<?php

namespace App\Http\Livewire;

use LicenseSettings;
use Livewire\Component;

class Settings extends Component
{
    public $state = [];

    public function mount()
    {
        $this->state = [
            'productKey' => app(LicenseSettings::class)->productKey,
            'localKey' => app(LicenseSettings::class)->localKey
        ];
    }

    public function UpdateLicenseInformation(LicenseSettings $settings)
    {
        $settings->productKey = $this->state['productKey'];
        $settings->localKey = $this->state['localKey'];

        $settings->save();
        $settings->refresh();

        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
