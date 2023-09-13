<?php

namespace App\Console\Commands;

use App\Services\ThirdPartyAPIs\VtPassApis;
use Illuminate\Console\Command;

class Testing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:testing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $vtPass = resolve(VtPassApis::class);
        $response = $vtPass->validateSmartCard([
            'billersCode' => 1212121212,
            'serviceID' => 'dstv'
        ]);
        dd($response);
    }
}
