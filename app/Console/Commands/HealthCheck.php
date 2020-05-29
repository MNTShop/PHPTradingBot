<?php

namespace App\Console\Commands;

use App\Price;
use App\BithumbTradeHelper;
use \Bg\Sdk\REST\Request\ServerTimeRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use JakubOnderka\PhpConsoleColor\ConsoleColor;
use Mockery\Exception;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'tests for various checkups';

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
     * @return mixed
     */
    public function handle()
    {
        // can talk to Bithumb
        $bithumb = BithumbTradeHelper::getBithumb();
        try {
            if(!$bithumb->getResponse(new ServerTimeRequest())->isError()){
                $this->info('Bithumb is ok server timestamp'.$bithumb->response->getData());
            }
            else{
                throw new Exception($bithumb->response->getMessage());
            }
        } catch (\Exception $e) {
            $this->error('failed to communicate with Bithumb : ' . $e->getMessage());
        }

        // signals Daemon running


        // orders Daemon running


        // Cache config is correct
        try {
            Cache::put('health', time(), now()->addMinutes(5));
            $this->info('Redis is ok');
        } catch (\Exception $exception) {
            $this->error('Redis is not working : ' . $exception->getMessage());
        }
    }
}
