<?php

namespace PureDevLabs;

use Illuminate\Support\Facades\Http;

class BackendApp
{
    const VERSION = '3.2.1';

    public static function Version()
    {
        try
        {
            $json = Http::withoutVerifying()->get('http://puredevlabs.cc/backendMP3proV3/version.json?update=1');
            $data = json_decode($json, true);
            $data['app'] = self::VERSION;
            if (json_last_error() == JSON_ERROR_NONE)
            {
                $version = $data;
            }
            else
            {
                $version = ['app' => self::VERSION, 'backend' => 'n/a'];
            }
        }
        catch (\Illuminate\Http\Client\ConnectionException $e)
        {
            $version = ['app' => self::VERSION, 'backend' => 'n/a'];
        }

        return $version;
    }
}
