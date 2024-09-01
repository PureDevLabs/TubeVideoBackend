<?php

namespace App\Http\Livewire;

use AuthSettings;
use Livewire\Component;

class Settings extends Component
{
    public $state = [];

    public function mount()
    {
        $this->state = [
            'authMethod' => app(AuthSettings::class)->method
        ];
    }

    public function UpdateAuthMethod(AuthSettings $settings)
    {
        $settings->method = $this->state['authMethod'];

        $settings->save();
        $settings->refresh();

        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
