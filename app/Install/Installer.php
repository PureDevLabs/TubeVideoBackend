<?php

namespace App\Install;

use Exception;
use App\Install\Checker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class Installer extends Checker
{

    public function index()
    {
        $environment = file_exists(base_path() . '/.env');

        if (!$environment || !$this->checkDbConnection())
        {
            $data = [
                'status' => false
            ];
            return view('installer.index', compact('data'));
        }
        else
        {
            return redirect()->route('checker');
        }
    }


    public function check()
    {
        $data = [
            'php_version' => $this->checkPhpVersion(),
            'php_exec' => $this->isExecEnabled(),
            'php_proc_open' => $this->isProcOpenEnabled(),
            'php_popen' => $this->isPopenEnabled(),
            'php_putenv' => $this->isPutenvEnabled(),
            'ioncube_installed' => $this->isIoncubeLoaded(),
            'check_db' => $this->checkDbConnection(),
            'check_dbTable' => $this->checkDbTable(),
            'redis_connect' => $this->isRedisReady()
        ];

        return view('installer.check', compact('data'));
    }

    public function migrate()
    {
        if (!$this->checkDbTable())
        {
            Artisan::call('cache:clear');
            Artisan::call('migrate', [
                '--force' => true
            ]);
            Artisan::call('db:seed', [
                '--force' => true
            ]);
            return redirect()->route('checker');
        }
    }

    public function getEnvContent()
    {
        if (file_exists(base_path() . '/.env'))
        {
            touch(base_path() . '/.env');
        }
        else
        {
            copy(base_path() . '/.env.example', base_path() . '/.env');
        }
        return file_get_contents(base_path() . '/.env');
    }

    public function saveConfig(Request $input)
    {
        $env = $this->getEnvContent();
        $dbName = $input->get('database');
        $dbHost = $input->get('hostname');
        $dbUsername = $input->get('username');
        $dbPassword = $input->get('password');
        $redisHost = $input->get('redis_host');
        $redisPort = $input->get('redis_port');
        $redisPassword = $input->get('redis_password');

        $databaseSetting = 'DB_HOST=' . $dbHost . '
DB_DATABASE=' . $dbName . '
DB_USERNAME=' . $dbUsername . '
DB_PASSWORD="' . $dbPassword . '"
REDIS_HOST=' . $redisHost . '
REDIS_PASSWORD="' . $redisPassword . '"
REDIS_PORT=' . $redisPort . '
APP_URL="' . request()->getSchemeAndHttpHost() . '"
APP_ENV=production
APP_DEBUG=false
';

        // @ignoreCodingStandard
        $rows       = explode("\n", $env);
        $unwanted   = "DB_HOST|DB_DATABASE|DB_USERNAME|DB_PASSWORD|APP_URL|REDIS_HOST|REDIS_PASSWORD|REDIS_PORT";
        $cleanArray = preg_grep("/$unwanted/i", $rows, PREG_GREP_INVERT);

        $cleanString = implode("\n", $cleanArray);


        $env = $cleanString . $databaseSetting;
        try
        {
            $dbh = new \PDO('mysql:host=' . $dbHost, $dbUsername, $dbPassword);

            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // First check if database exists
            $stmt = $dbh->query('CREATE DATABASE IF NOT EXISTS ' . $dbName . ' CHARACTER SET utf8 COLLATE utf8_general_ci;');
            // Save settings in session
            $_SESSION['db_username'] = $dbUsername;
            $_SESSION['db_password'] = $dbPassword;
            $_SESSION['db_name']     = $dbName;
            $_SESSION['db_host']     = $dbHost;
            $_SESSION['db_success']  = true;
            $message = 'Database settings correct';

            try
            {
                file_put_contents(base_path() . '/.env', $env);
            }
            catch (Exception $e)
            {
                $message = 'Error: could not write to environment file';
                $data = [
                    'status' => false,
                    'errorMsg' => 'Error: could not write to environment file'
                ];
                return view('installer.index', compact('data'));
            }

            return redirect()->route('checker');
        }
        catch (\PDOException $e)
        {
            $data = [
                'error' => true,
                'status' => false,
                'errorMsg' => 'DB Error: ' . $e->getMessage()
            ];
            return view('installer.index', compact('data'));
        }
        catch (\Exception $e)
        {
            $data = [
                'error' => true,
                'status' => false,
                'errorMsg' => 'DB Error: ' . $e->getMessage()
            ];
            return view('installer.index', compact('data'));
        }
    }

    public function completeSetup()
    {
        Artisan::call('key:generate', [
            '--force' => true
        ]);

        return redirect()->route('dashboard');
    }
}
