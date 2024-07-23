<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\OauthToken;
use Illuminate\Support\Facades\Cache;
use PureDevLabs\Extractors\Youtube;

class OauthTokens extends Component
{
    public $testContent = '';
    public $showModal = false;
    public $state = [];
    protected $rules = [
        'state.accessToken' => 'required',
        'state.refreshToken' => 'required',
        'state.expiryTime' => 'required|numeric'
    ];

    public function mount()
    {
        // $tokens = Cache::get('oauth:tokens', '');
        // $tokens = OauthToken::all();
        // $tokens = json_encode($tokens->toArray());
        // $tokens = json_decode($tokens, true);
        // $randomToken = mt_rand(0, count($tokens) - 1);
        // $tokens = $tokens[$randomToken];
        // dd($tokens['access_token']);
    }

    public function addToken()
    {
        $this->validate();

        $token = new OauthToken;
        $token->access_token = $this->state['accessToken'];
        $token->refresh_token = $this->state['refreshToken'];
        $token->expiry_time = time() + (int)$this->state['expiryTime'];
        $token->created_at = now();
        $token->updated_at = now();
        $token->save();

        $this->reset('state');

        $tokens = OauthToken::all();
        Cache::put('oauth:tokens', json_encode($tokens->toArray()));

        $this->emit('saved');
    }

    public function read()
    {
        return OauthToken::all();
    }

    public function deleteToken($id)
    {
        OauthToken::destroy($id);
        $tokens = OauthToken::all();
        Cache::put('oauth:tokens', json_encode($tokens->toArray()));
    }

    public function testToken($id)
    {
        $token = OauthToken::find($id);
        $youtube = new Youtube();
        $testOutput = $youtube->TestPlayerApiRequest("HMpmI2F2cMs", $token['access_token']);
        $this->testContent = json_encode($testOutput, JSON_PRETTY_PRINT);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.oauth-tokens', [
            'data' => $this->read(),
        ]);
    }
}
