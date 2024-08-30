<?php

namespace App\Install;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Checker
{
    public function checkPhpVersion()
    {
        return version_compare(PHP_VERSION, '7.4.0') >= 0 && version_compare(PHP_VERSION, '8.2.0') < 0;
    }

    public function checkDbConnection()
    {
        try
        {
            DB::connection()->getPdo();
            return true;
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    public function checkDbTable()
    {
        try
        {
            $users = DB::table('users')->get();
            if (!$users->isEmpty())
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    public function isExecEnabled()
    {
        return (function_exists('exec')) ? true : false;
    }

    public function isProcOpenEnabled()
    {
        return (function_exists('proc_open')) ? true : false;
    }

    public function isPutEnvEnabled()
    {
        return (function_exists('putenv')) ? true : false;
    }

    public function isPopenEnabled()
    {
        return (function_exists('popen')) ? true : false;
    }

    public static function isRedisReady()
    {
        $isReady = true;
        try
        {
            $redis = Redis::connection();
            $redis->ping();
            $redis->disconnect();
        }
        catch (\Exception $e)
        {
            $isReady = false;
        }

        return $isReady;
    }
}
