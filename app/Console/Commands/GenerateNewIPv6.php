<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PureDevLabs\Misc\Generator;

class GenerateNewIPv6 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:newIPv6';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a new IPv6 and adding it to Interface';

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
    public function handle(Generator $generator)
    {
        return $generator->run();
    }
}
