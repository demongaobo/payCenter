<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Lib\Excel;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Repository\OrderRiskRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderUserCertified;

class updateBuyPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateBuyPrice';

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

        $data = \App\Warehouse\Modules\Func\Excel::read("app/Console/Commands/editPrice.xlsx");
        unset($data[1]);
        if(!empty($data)){

            foreach ($data as $cel) {
                if (!isset($cel['A']) || !isset($cel['B'])) continue;
                $b = OrderGoods::query()->where('order_no','=',$cel['A'])->update(['buyout_price'=>$cel['B']]);
                if(!$b){
                    LogApi::error("updateBuyPrice:".$cel['A']);
                }
            }
            echo "success";die;

        }

    }

}
