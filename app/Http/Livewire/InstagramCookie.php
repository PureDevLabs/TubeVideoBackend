<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Storage;

class InstagramCookie extends Component
{
    public $state = [];

    public function mount()
    {
        $this->state = [
            'instaCookie' => (Storage::exists('Instagram/Cookie.txt')) ? Storage::get('Instagram/Cookie.txt') : '',
        ];
    }

    public function UpdateInstagramCookie()
    {
        Storage::put('Instagram/Cookie.txt', $this->state['instaCookie']);

        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.instagram-cookie');
    }
}
