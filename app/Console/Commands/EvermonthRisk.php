<?php

namespace App\Console\Commands;

use App\Order\Modules\OrderExcel\CronRisk;
use Illuminate\Console\Command;

class EvermonthRisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:EvermonthRisk';

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
        CronRisk::everMonth();
        echo "success";
    }
}
