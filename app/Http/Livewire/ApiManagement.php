<?php

namespace App\Http\Livewire;

use App\Models\Key;
use Livewire\Component;

class ApiManagement extends Component
{
    public $modelId;
    public $name;
    public $apiKey;

    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }

    public function createApiToken()
    {
        $this->apiKey = sha1($this->name . '_' . rand(0, 100000));
        try
        {
            Key::create($this->modelData());
            $this->reset('name');
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            if (strpos($e->errorInfo[2], 'Duplicate entry') !== false)
            {
                $this->addError('name', 'This API Key Name already exist.');
            }
            else
            {
                $this->addError('name', 'Unknown error.');
            }
        }
    }

    public function manageKey($id)
    {
        return redirect('admin/managekey/' . $id);
    }

    public function deleteKey($id)
    {
        Key::destroy($id);
    }

    public function read()
    {
        return Key::with('management')->get();
    }

    public function modelData()
    {
        return [
            'name' => $this->name,
            'apikey' => $this->apiKey,
        ];
    }

    public function render()
    {
        return view('livewire.api-management', [
            'data' => $this->read(),
        ]);
    }
}
