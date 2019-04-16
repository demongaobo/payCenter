<?php

namespace App\Console\Commands;

use App\Order\Controllers\Api\v1\CronController;
use App\Order\Controllers\Api\v1\WithholdController;
use App\Order\Modules\Service\CronOperate;
use Illuminate\Console\Command;

class crontabOrderDiscuss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:crontabOrderDiscuss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
       CronOperate::cronOrderDiscuss();
    }

}
