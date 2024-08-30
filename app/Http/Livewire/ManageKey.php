<?php

namespace App\Http\Livewire;

use App\Models\Key;
use Livewire\Component;
use App\Models\Management;
use Illuminate\Support\Facades\Cache;

class ManageKey extends Component
{
    public $keyId;
    public $allowed_ip;
    public $apikey_id;
    public $apiKey;

    public function rules()
    {
        return [
            'allowed_ip' => 'required',
            'key_id' => 'required',
        ];
    }

    public function allowIP()
    {
        $this->apikey_id = $this->keyId;

        try
        {
            Management::create($this->modelData());
            $this->reset('allowed_ip');
            Cache::store('permaCache')->forget('apiKeys');
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            if (strpos($e->errorInfo[2], 'Cannot add or update a child row: a foreign key constraint fails') !== false)
            {
                $this->addError('allowed_ip', 'This API Key does not exist.');
            }
            else
            {
                $this->addError('allowed_ip', 'Unknown error.');
            }
        }
    }

    public function modelData()
    {
        return [
            'allowed_ip' => $this->allowed_ip,
            'key_id' => $this->apikey_id,
        ];
    }

    public function read()
    {
        return Key::with('management')->where('id', $this->keyId)->get();
    }

    public function deleteIP($id)
    {
        Management::destroy($id);
        Cache::store('permaCache')->forget('apiKeys');
    }

    public function render()
    {
        return view('livewire.manage-key', [
            'data' => $this->read(),
        ]);
    }
}
