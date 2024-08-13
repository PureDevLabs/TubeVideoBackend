<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PureDevLabs\Extractors\Youtube;

class GenerateTrustedSession extends Command
{
    const _MAX_TRIES = 5;
    const _SLEEP_TIME = 10;  // In seconds
    private $_tries = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:trustedSession';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate YouTube trusted session credentials';

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
        $this->generate();
        return 0;
    }

    // Private "Helper" Functions
    private function generate()
    {
        $ytsScriptDir = glob("/home/youtube-trusted-session*", GLOB_ONLYDIR);
        $chromium = glob("/usr/bin/chromium*");
        if ($ytsScriptDir !== false && !empty($ytsScriptDir) && $chromium !== false && !empty($chromium))
        {
            $ytsScriptDir = current($ytsScriptDir);
            $bunResponse = [];
            exec('type bun', $bunResponse);
            if (!empty($bunResponse) && preg_match('/^((bun is )(.+))/i', $bunResponse[0]) == 1)
            {
                $bunResponse = [];
                exec("bun run " . $ytsScriptDir . "/index.js", $bunResponse);
                $json = json_decode(implode("", $bunResponse), true);
                if (isset($json['visitorData'], $json['poToken'], $json['basejs']))
                {
                    $this->line(print_r($json, true));
                    Cache::put('trustedSession:visitorData', $json['visitorData']);
                    Cache::put('trustedSession:poToken', $json['poToken']);
                    Cache::put('trustedSession:basejs', $json['basejs']);
                    $response = Http::withOptions(['force_ip_resolve' => 'v' . env('APP_USE_IP_VERSION', 4)])->timeout(4)->get($json['basejs']);
                    $response = ($response->successful() && $response->status() == 200) ? $response->body() : '';
                    Storage::disk('local')->put(Youtube::_BASE_JS, $response);
                    $this->info('Cached trusted session info!');
                }
                else
                {
                    $this->error('Invalid Bun script response or Missing some response info!');
                    $this->_tries++;
                    if ($this->_tries < self::_MAX_TRIES)
                    {
                        $this->line('Waiting ' . self::_SLEEP_TIME . ' seconds to try again...');
                        sleep(self::_SLEEP_TIME);
                        $this->line('Trying again.');
                        $this->generate();
                    }
                }
            }
            else
            {
                $this->error('Bun is not installed or installed incorrectly!');
            }
        }
        else
        {
            $this->error('"youtube-trusted-session" Bun script and/or chromium is not installed!');
        }
    }
}
