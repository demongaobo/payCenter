<?php
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/4/28
 * Time: 15:12
 */
/**
 * api返回消息
 * @param $data
 * @param int $errno
 * @param string $errmsg
 * @return \Illuminate\Http\JsonResponse
 *
 */
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
function apiResponse($data=[], $errno=0, $errmsg='',$convertString = false)
{
    if (empty($errmsg)) {

        $errmsg =  ApiStatus::$errCodes[$errno];
    }
    if($convertString && !empty($data)){
        $data = value2String($data);
    }
    return response()->json(['data'=>$data, 'code'=>$errno, 'msg'=>$errmsg]);
}

function value2String(array $array = [])
{
    if(is_array($array) && $array){
        foreach($array as $key => $val){
            if(is_array($val)){
                $array[$key] = value2String($val);
            }else{
                $array[$key] = strval($val);
            }
        }
    }
    return $array;
}

/**
 * 接口内部返回
 * Author: heaven
 * @param int $errno
 * @param array $data
 * @param string $errmsg
 * @return array
 */
function apiResponseArray($errno=0,$data=[], $errmsg='')
{
    if (empty($errmsg)) {

        $errmsg =  ApiStatus::$errCodes[$errno];
    }
    return ['data'=>$data, 'code'=>$errno, 'msg'=>$errmsg];
}

/**
 * 过滤数组
 * @param	array	$data	    【必须】关联数组
 * @param	array	$filters    【必须】校验器数组（支持管道符）
 * @param	array	$failed_list【可选】不满足过滤条件的元素组成的数组
 * @return  array	返回满足过滤条件的元素组成的数组
 */
function filter_array( $data, array $filters, &$failed_list=[] ){
    $result = [];
    if( !is_array($data) || count($data)==0 ){
        return $result;
    }
    // 循环数据，调用对应校验器，进行过滤
    foreach( $data as $k => $v ){
        // 判断必须
        if( isset($filters[$k]) && $filters[$k] ){
            if( filter_value($v, $filters[$k]) ){
                // 过滤成功，或没有指定过滤器
                $result[$k] = $v;
            }else{// 过滤失败的数据
                $failed_list[$k] = $v;
            }
        }
    }
    return $result;
}

/**
 * 过滤值
 * @param   mixed   $value  【必须】待过滤的值
 * @param   mixed   $filter 【必须】校验规则
 * @return boolean  true：满足过滤条件；false：不满足
 */
function filter_value( $value, $filter ){
    if( is_string($filter) ){
        $filter = explode('|', $filter);
    }
    foreach( $filter as $it ){
        if( !is_callable($it) ){
            throw new \Exception( '[filter] '.$it.' not found' );
        }
        $b = call_user_func( $it, $value );
        // 过滤失败
        if( $b===false || $b===null ){
            return false;
        }
    }
    return true;
}
//-+----------------------------------------------------------------------------
// | 变量值 校验函数
//-+----------------------------------------------------------------------------
// 值必须存在（非空，）
function required($v){
    return !is_null($v);
}
// 可以转换成true
function is_true($v){
    return !!$v;
}
/**
 * 判断价格，单位：元
 * 小数点后两位
 * @param string $price
 * @return boolean
 */
function is_price($price){
    if( !is_numeric($price) ){
        return false;
    }
    if( $price<1 ){
        return !!preg_match('/^0(\.\d{0,2})?$/', ''.$price);
    }
    return !!preg_match('/^[1-9]\d{0,8}(\.\d{0,2})?$/', ''.$price);
}

/**
 * 校验是否为 分页值
 * @param mixed $page
 * @return boolean
 */
function is_page($page){
    if(intval($page) != $page){
        return false;
    }
    if( $page<1 ){// 不可用小于1
        return false;
    }
    return true;
}

/**
 * 校验是否为 分页大小
 * @param mixed $page
 * @return boolean
 */
function is_size($size){
    if(intval($size) != $size){
        return false;
    }
    if( $size<0 ){// 非负数，可以为0
        return false;
    }
    return true;
}

/**
 * url格式判断
 * @param string $string
 * @return boolean
 */
function is_url($string){
    if (!empty($string)) {
        // 验证url格式
        $string = filter_var($string, FILTER_VALIDATE_URL);
        return $string!=false;
    }
    return FALSE;
}

/**
 * 判断值是否是主键自增ID格式
 */
function is_id( $id ){
    if(is_numeric($id) && $id>0 && $id==intval($id)){
        return true;
    }
    return false;
}

/**
 * 判断值是否是时间戳
 */
function is_time( $time ){
    return is_id($time);
}


//-+----------------------------------------------------------------------------
// | 数组 解析成 json 展示
//-+----------------------------------------------------------------------------
/**
 * 判断是否为关联数组
 * @param array $arr
 * @return boolean
 */
function is_assoc_arr( $arr ){
    if( is_array($arr) && count($arr) ){
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    return false;
}
/**
 * @param int $price    价格，单位：分
 * @return string   格式化价格，单位：元
 */
function priceFormat($price){
    $price = max(0,$price);
    return sprintf('%0.2f',$price);
}
/**
 * 打印函数 print_r
 * @param $data 打印数组
 */
function p($data, $exit = '')
{
    echo "<pre>";
    print_r($data);
    if($exit == ""){
        exit;
    }
}
/**
 * 打印函数 var_dump
 * @param $data 打印数组
 */
function v($data, $exit = '')
{
    echo "<pre>";
    var_dump($data);
    if($exit == ""){
        exit;
    }
}


/**
 * 规则：前缀（1位）+年（1位）+月（1位）+日（2位）+时间戳（5位）+微秒（5位）+随机数（1位）
 * Author: heaven
 * @param int $noType 1.分期交易号, 2.退货编号, 3.支付交易, 4.预授权, 5.业务平台退款码 6.goods_no生成方式, 7.还机单编号 8.买断编号,9.续租, 10.代扣协议号
 * @return bool|string

 */
function createNo($noType=1){
    $npreNoType = array(
        //分期交易号
        1 => 'F',
        2 => 'T',
        3 => 'P',
        4 => 'Y',
        5 => 'C',
        6 => 'G',
        7 => 'H',
        8 => 'B',
        9 => 'X',
        10 => 'W',
        'D' => 'D',// 发货单编号
        'R' => 'R',// 收货单编号
        'AD' => 'AD',//预授权支付编号
        'AU' => 'AU',//预授权解除编号
        'RC' => 'RC',//退款清算编号
        'YD' => 'YD',//预定活动接口
        'TY' => 'TY',//预定活动接口
        'YQ' => 'YQ',//分期逾期扣款接口
    );
    $year = array();
    if (!isset($npreNoType[$noType])) {
        return false;
    }
    // 年差值标记符，大写字母集[A-Z]
    for($i=65;$i<91;$i++){
        $year[]= strtoupper(chr($i));
    }
    $orderSn = $npreNoType[$noType].$year[(intval(date('Y')))-2018] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . rand(0, 9);
    return $orderSn;
}

/**
 * 生成 退货编号
 * @return string
 */
function create_return_no(){
    return 'P'.createNo(2);
}


/**
 * 生成 支付单
 * @return string
 */
function creage_payment_no(){
    return 'P'.createNo(3);
}

/**
 * 资金预授权
 * @return string
 */
function creage_fundauth_no(){
    return 'F'.createNo(3);
}

/**
 * 代扣协议编号
 * @return string
 */
function creage_withhold_no(){
    return 'W'.createNo(3);
}

/**
 * 生成 发货编号
 * @return string
 */
function create_delivery_no(){
    return 'P'.createNo('D');
}

/**
 * 生成 收货编号
 * @return string
 */
function create_receive_no(){
    return 'P'.createNo('R');
}

/**
 * 根据key 二维数组分组
 * @param $arr 数组
 * @param $key 按照分组的key
 */
function array_group_by($arr, $key)
{
    $grouped = [];
    foreach ($arr as $value) {
        $grouped[$value[$key]][] = $value;
    }
    // Recursively build a nested grouping if more parameters are supplied
    // Each grouped array value is grouped according to the next sequential key
    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $parms);
        }
    }
    return $grouped;
}

/**
 *
 * sql调试
 * Author: heaven
 */
function sql_profiler()
{
    //sql调试
    DB::listen(function ($sql) {
        foreach ($sql->bindings as $i => $binding) {
            if ($binding instanceof \DateTime) {
                $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
            } else {
                if (is_string($binding)) {
                    $sql->bindings[$i] = "'$binding'";
                }
            }
        }
        $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);
        $query = vsprintf($query, $sql->bindings);
        print_r($query);
        echo '<br />';
    });

}

/**
 * @return array|bool|mixed|string
 * 兼容 application/x-www-form-urlencoded、application/json、multipart/form-data等
 */
function apiData()
{
    $data = request()->input();
    if (!$data || empty($data) || count($data) == 0) {
        $data = file_get_contents("php://input");
        return json_decode($data, true);
    }
    return $data;
}


/**
 * 多维对象转换为数组
 * Author: heaven
 * @param $object
 * @return mixed
 */
function objectToArray($object)
{
    //数据处理
    return json_decode(json_encode($object), true);
}

/**
 * 获取一个的类的单一实例
 * @param type $class 类名【注意：区分大小写，默认为ApiStatus】
 * @param type $namespace 命名空间【默认为当前Single所在空间'\App\Lib'】
 * @return obj|false
 */
function get_instance( $class='ApiStatus',$namespace='\App\Lib' ){
    return \App\Lib\Single::getInstance( $class,$namespace );
}
/**
 * 设置全局apiStatus的Code码
 * @param string $code code码
 */
function set_code( $code ){
    return get_instance()->setCode(strval($code));
}
/**
 * 获取被set_code设置的全局apiStatus的Code码
 * @return string $code code码
 */
function get_code( ){
    return ''.get_instance()->getCode();
}
/**
 * 设置全局apiStatus的Msg信息
 * @param string $msg Msg信息
 */
function set_msg( $msg ){
    return get_instance()->setMsg(strval($msg));
}
/**
 * 获取被set_msg设置的全局apiStatus的Msg信息
 * @return string $msg 信息
 */
function get_msg( ){
    return ''.get_instance()->getMsg();
}
function set_apistatus( $code, $msg ){
    return get_instance()->setCode(strval($code))->setMsg(strval($msg));
}

/**
 * 计算逾期天数
 * 公式逾期天数=今天日期-还款日
 */
function getBeoverdue($re_day){
    $time = date('Y-m-d',time());
    $day  = strtotime($time);
    $wday = $re_day;
    return $day>=$wday ? ($day-$wday)/86400 : "";
}

/**
 * 计算逾期条件
 * 返回数组
 */
function getBeoverduetime($type){
    $time = date('Y-m-d',time());
    $day  = strtotime($time);    
    switch($type){
        case 'M0'://未逾期
            $whereArray[]= ['order_goods_instalment.withhold_day', '>=', $day];
            break;
        case 'M1': //逾期0-30
            $whereArray =  [['order_goods_instalment.withhold_day', '>=', $day-30*86400],['order_goods_instalment.withhold_day', '<', $day]];
        break;   
        case 'M2': //逾期31-60
            $whereArray = [['order_goods_instalment.withhold_day', '>=', $day-60*86400],['order_goods_instalment.withhold_day', '<', $day-31*86400]];
        break; 
        case 'M3': //逾期61-90
            $whereArray = [['order_goods_instalment.withhold_day', '>=', $day-90*86400],['order_goods_instalment.withhold_day', '<', $day-61*86400]];
        break;      
        case 'M4': //逾期91-120
            $whereArray = [['order_goods_instalment.withhold_day', '>=', $day-120*86400],['order_goods_instalment.withhold_day', '<', $day-91*86400]];
        break; 
        case 'M5': //逾期121-150
            $whereArray = [['order_goods_instalment.withhold_day', '>=', $day-150*86400],['order_goods_instalment.withhold_day', '<', $day-121*86400]];
        break;                                 
        case 'M6': //逾期大于151
            $whereArray[]= ['order_goods_instalment.withhold_day', '<', $day-151*86400];
        break;   
        default://未逾期
           $whereArray[]= ['order_goods_instalment.withhold_day', '>=', $day];
                        
    }
    return $whereArray;
}

/**
 * 格式化數字
 * Author: heaven
 * @param $num
 * @return string
 */
function normalizeNum($num)
{
    return is_numeric($num) ? number_format($num, 2, '.', '') : '0.00';
}

//以数组指定一列值为数组key
function array_keys_arrange($array,$value){
    $list = [];
    foreach($array as $key=>$v){
        $list[$v[$value]] = $v;
    }
    return $list;
}

/**
 * 计算扣款日期
 * @param $term 分期
 * @param $day  天
 * @return string
 */
function withholdDate($term, $day, $pre="-"){
    if($term == "" || $day == ""){
        return "";
    }
    $year   = substr($term, 0, 4);
    $month  = substr($term, -2);
    $day    = str_pad($day, 2, "0", STR_PAD_LEFT);
    $_date     =   $year . $pre . $month . $pre . $day ;
    return $_date;
}

/**
 * 将商品规格信息格式转换
 * @param $specs string 商品规格信息【成色:全新;颜色:深空灰;租期:12期;存储:64G;网络:全网通】
 * @return string 转换后的商品规格信息【全新|深空灰|64G|全网通】
 */
function filterSpecs( $specs ){
	if( !is_string($specs) ){
		return '';
	}
	//商品信息解析
	$specsArr = explode(';', $specs);
	$specsStrArr = [];
	foreach ($specsArr as $key => $value) {
		$value = explode(':', $value);
		$specsStrArr[$value[0]]= $value[1];
	}
	if( isset($specsStrArr['租期']) ){
		unset($specsStrArr['租期']);
	}
	return implode('|', $specsStrArr);
}

//-+----------------------------------------------------------------------------
// | 数据导入，临时使用的 支付系统编码函数
//-+----------------------------------------------------------------------------
/**
 * 创建编码
 * <p>规则：前缀（2位）+年（1位）+月（1位）+日（2位）+时间戳（5位）+毫秒（5位）+随机数（1位）</p>
 * @param string $prefix	编码前缀
 * @return string 17位编码
 */
function create_no(string $prefix,$time=NULL){
	// 年差值标记符，大写字母集[A-Z]
    for($i=65;$i<91;$i++){
        $year[]= strtoupper(chr($i));
    }
    $orderSn = $prefix.$year[(intval(date('Y',$time)))-2018] . strtoupper(dechex(date('m',$time))) . date('d',$time) . substr(time(), -5) . substr(microtime(), 2, 5) . rand(0, 9);
    return $orderSn;
}

/**
 * 创建 支付单号
 * @return string
 */
function create_payment_no($time=null){
	return create_no('10',$time);
}

/**
 * 创建 退款单号
 * @return string
 */
function create_refund_no($time=null){
	return create_no('11',$time);
}

/**
 * 创建 资金预授权单号
 * @return string
 */
function create_fundauth_no($time=null){
	return create_no('20',$time);
}

/**
 * 创建 资金预授权 解冻 单号
 * @return string
 */
function create_fundauth_unfreeze_no($time=null){
	return create_no('21',$time);
}

/**
 * 创建 资金预授权 转支付 单号
 * @return string
 */
function create_fundauth_create_pay_no($time=null){
	return create_no('22',$time);
}

/**
 * 创建 代扣协议 单号
 * @return string
 */
function create_withhold_no($time=null){
	return create_no('30',$time);
}

/**
 * 创建 代扣协议 单号
 * @return string
 */
function create_withhold_create_pay_no($time=null){
	return create_no('31',$time);
}
//-+----------------------------------------------------------------------------
// 格式化时间
 function udate($format = 'u', $utimestamp = null) {
	if (is_null($utimestamp))
		$utimestamp = microtime(true);

	$timestamp = floor($utimestamp);
	$milliseconds = round(($utimestamp - $timestamp) * 1000000);

	return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}

/**
 * 原子计数器
 * Author: heaven
 * @param $keys 键名
 * @param $expireTime 设置的过期时间
 */
function redisIncr($keys, $expireTime)
{
        //限制用户提交的次数
        if (empty($keys) || empty($expireTime)) return false;
        $feddAuto = Redis::incr($keys);
        Redis::expire($keys, $expireTime);
        return $feddAuto;

}

//拼接url
function createLinkstringUrlencode($para)
{
    $arg = "";
    while (list ($key, $val) = each($para)) {
        $arg .= $key . "=" . urlencode($val) . "&";
    }
    $arg = substr($arg, 0, count($arg) - 2);
    if (get_magic_quotes_gpc()) {
        $arg = stripslashes($arg);
    }
    return $arg;
}


/**
 * 获取短连接
 * @param $url URL
 * @param $shortUrl 返回的短连接
 */
function createShortUrl($url){

    $header = ['Content-Type: application/json'];

    $data['longURL'] = $url;
    $shortUrl  =  env('SHORT_URL');

    $info = \App\Lib\Curl::post($shortUrl, json_encode($data), $header);
    $info = json_decode($info,true);

    \App\Lib\Common\LogApi::info('[sendMessage]生成短连接：' , ['url'=>$url,'info'=>$shortUrl,'data'=>$info?$info:'']);

    if(!is_array($info)){
        return false;
    }
    return $info['shortURL'];

}

//获取用户IP地址
function getIp()
{

    if(!empty($_SERVER["HTTP_CLIENT_IP"]))
    {
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    }
    else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
    {
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }
    else if(!empty($_SERVER["REMOTE_ADDR"]))
    {
        $cip = $_SERVER["REMOTE_ADDR"];
    }
    else
    {
        $cip = '';
    }
    preg_match("/[\d\.]{7,15}/", $cip, $cips);
    $cip = isset($cips[0]) ? $cips[0] : 'unknown';
    unset($cips);

    return $cip;
}

/**
 * 获取短连接
 * @param $url URL
 * @param $shortUrl 返回的短连接
 */
function createWithholdDay($term, $day){
    if($day < 10){
        $day = '0' . $day;
    }

    return strtotime($term.$day);

}
/**
 * redis设置
 * @param string $key 需要设置的键名【必须】
 * @param string $value 设置的值 【必须】
 * @param int $expireTime 设置的过期时间 【必须】
 */
function redis_set( $key, $value, $expireTime )
{
	//key值和过期时间必须
	if( empty($key)|| empty($value) || empty($expireTime) ){
		return false;
	}
	if( is_array($value) ){
		$value = json_encode($value);
	}
	$redis = Redis::setex($key, $expireTime, $value);
	if( !$redis ){
		return false;
	}
	return true;
}
/**
 * redis获取
 * @param string $key 需要获取的键名【必须】
 */
function redis_get( $key )
{
	return Redis::get($key);
}


/**
 * 全球唯一标识符 格式：xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx (8-4-4-4-12)
 * @param string	$hyphen  连字符号，默认为 "-"
 * @return string
 */
function guid(string $hyphen='-')
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000); //optional for php 4.2.0 and up.随便数播种，4.2.0以后不需要了。
        $charid = strtoupper(md5(uniqid(rand(), true))); //根据当前时间（微秒计）生成唯一id.
        $uuid = '';
        $uuid .= substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);
        return $uuid;
    }
}
    /**
     * 计算中英文长度
     * @param null $string
     * @return int
     */

    function zy_strlen($string = null) {
        // 将字符串分解为单元
        preg_match_all("/./us", $string, $match);
        // 返回单元个数
        return count($match[0]);
}

/**
 * date日期转为年月日类型
 */
function date2nyr( $date ){
	$time = strtotime($date);
	return date('Y',$time) . '年' . date('m',$time) . '月' . date('d',$time) . '日';
}
