<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OauthToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RefreshOAuthTokens extends Command
{
    // Constants
    const _EXPIRY_OFFSET = 3600;  // In seconds
    const _USERAGENT = "Android TV";
    const _CLIENT_ID = "861556708454-d6dlm3lh05idd8npek18k6be8ba3oc68.apps.googleusercontent.com";
    const _CLIENT_SECRET = "SboVhoG9s0rNafixCSGGKXAT";
    const _SLEEP_TIME = 5;  // In seconds

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:OAuthTokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh any OAuth Tokens that will expire soon.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tokens = OauthToken::all()->toArray();
        $now = time();
        $updatedTokens = [];

        foreach ($tokens as $token)
        {
            if ($token['expiry_time'] - $now <= self::_EXPIRY_OFFSET)
            {
                $postData = [
                    'client_id' => self::_CLIENT_ID,
                    'client_secret' => self::_CLIENT_SECRET,
                    'refresh_token' => $token['refresh_token'],
                    'grant_type' => 'refresh_token'
                ];
                $response = Http::asForm()->withUserAgent(self::_USERAGENT)->post('https://oauth2.googleapis.com/token', $postData);
                if ($response->status() == 200)
                {
                    $json = json_decode($response, true);
                    if (isset($json['access_token'], $json['expires_in']))
                    {
                        $newToken = OauthToken::find($token['id']);
                        $newToken->access_token = "Bearer " . $json['access_token'];
                        $newToken->expiry_time = time() + (int)$json['expires_in'];
                        $newToken->save();
                        $updatedTokens[] = [
                            'old_token' => $token['access_token'],
                            'new_token' => $json['access_token']
                        ];
                    }
                }
                sleep(self::_SLEEP_TIME);
            }
        }

        if (!empty($updatedTokens))
        {
            $tokens = OauthToken::all();
            Cache::put('oauth:tokens', json_encode($tokens->toArray()));

            $this->line('[' . date('Y-m-d H:i:s') . ']');
            $this->line('The following Access Tokens were refreshed:');
            $this->table(
                ['Old Access Token', 'New Access Token'],
                $updatedTokens
            );
            $this->newLine();
        }
        else
        {
            $this->line('[' . date('Y-m-d H:i:s') . ']');
            $this->line('No Access Tokens were refreshed.');
            $this->newLine();
        }

        return 0;
    }
}
