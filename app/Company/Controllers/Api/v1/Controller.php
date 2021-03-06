<?php
namespace App\Company\Controllers\Api\v1;
use App\Lib\ApiStatus;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;

    public function __construct() {
	}

    /**
     * 参数验证函数
     * Author: heaven
     * @param $rules 验证的规则
     * @param $params 验证的参数
     * @return array
     */
    protected function  validateParams($rules, $params)
    {

        if (empty($params)) return  apiResponseArray(ApiStatus::CODE_20001,[]);

        if (isset($params['params']) && is_string($params['params'])) {
            $params = json_decode($params['params'], true);
        } else if (isset($params['params']) && is_array($params['params'])) {
            $params = $params['params'];
        } else if (!isset($params['params']) &&  is_string($params)) {
            $params = json_decode($params, true);
        }

        $validator = app('validator')->make($params, $rules);

        if ($validator->fails()) {
            return apiResponseArray(ApiStatus::CODE_20001,[], $validator->errors()->first());
        }

        return apiResponseArray(ApiStatus::CODE_0, $params);
    }


    /**
     * 队列处理成功，返回该函数
     * Author: heaven
     * @param int $type
     * @return string
     */
    public function innerOkMsg($msg='成功'){
        $returnData = array('status'=>'ok','msg'=>$msg);
        return response()->json($returnData)->send();

    }

    /**
     *
     * 消费处理失败，返回该函数，处理失败，队列可能会开启重试机制
     * Author: heaven
     * @param int $type
     * @return string
     */
    protected function innerErrMsg($msg=''){
        $returnData = array('status'=>'error','msg'=>$msg);
        return response()->json($returnData)->send();

    }
}