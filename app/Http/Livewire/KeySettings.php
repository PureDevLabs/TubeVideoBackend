<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\KeySettings as KeySettingsModel;
use Illuminate\Support\Facades\Cache;

class KeySettings extends Component
{
    public $state = [];
    public $keyId;
    protected $rules = [
        'state.max_video_duration' => 'required'
    ];

    public function mount()
    {
        $keySettings = KeySettingsModel::firstWhere('key_id', $this->keyId);
        if (is_null($keySettings))
        {
            $keySettings = KeySettingsModel::create([
                'key_id' => $this->keyId
            ]);
        }
        $this->state['max_video_duration'] = $keySettings->max_video_duration;
    }

    public function update()
    {
        $this->validate();

        $keySettings = KeySettingsModel::firstWhere('key_id', $this->keyId);
        $keySettings->max_video_duration = $this->state['max_video_duration'];
        $keySettings->save();

        Cache::store('permaCache')->forget('keySettings');

        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.key-settings');
    }
}
