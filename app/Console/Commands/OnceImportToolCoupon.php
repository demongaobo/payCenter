<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tools\Modules\Service\ImportCoupon\ImportZujiToTool;

class OnceImportToolCoupon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:OnceImportToolCoupon';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'import data once from zuji-coupon to tool-coupon';

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
        ImportZujiToTool::execute();
        echo "success";
    }
}
