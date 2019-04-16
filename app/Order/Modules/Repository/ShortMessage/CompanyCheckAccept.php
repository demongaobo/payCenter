<?php

namespace App\Order\Modules\Repository\ShortMessage;

/**
 * CompanyCheckAccept
 *
 * @author yaodongxu
 */
class CompanyCheckAccept implements ShortMessage {

    private $business_type;
    private $business_no;
    private $data;

    public function setBusinessType( int $business_type ){
        $this->business_type = $business_type;
    }

    public function setBusinessNo( string $business_no ){
        $this->business_no = $business_no;
    }
    public function setData( array $data ){
        $this->data = $data;
    }

    public function getCode($channel_id){
        $class =basename(str_replace('\\', '/', __CLASS__));
        return Config::getCode($channel_id, $class);
    }

    public function notify(){
        // 短息模板
        $code = $this->getCode(Config::CHANNELID_COMPANY);
        if( !$code ){
            return false;
        }

        // 短信参数
        $dataSms =[
            'realName'          => $this->data['username'],
        ];
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($this->data['mobile'], $code, $dataSms);

    }

    // 支付宝内部消息通知
    public function alipay_notify(){
        return true;
    }
}
