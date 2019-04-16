<?php
namespace App\Lib\Email;


/**
 * 发送邮件接口（默认为腾讯企业邮箱）
 */
class SendEmailApi {
    private $host = 'smtp.exmail.qq.com';//保存链接域名邮箱的服务器地址（默认腾讯企业邮箱）
    private $port = 465;//链接域名邮箱的服务器地址
    private $fromName = '拿趣用';//发件人姓名（昵称）可为任意内容
    private $userName = 'nqy@nqyong.com';//发件人smtp登录账号
    private $password = 'H67ZpPhHJu6zkYyT';//发件人smtp登密码
    private $from = 'nqy@nqyong.com';//发件人邮箱地址
    private $address = array();//收件人邮箱地址
    private $subject = '';//邮件主题
    private $body = '';//邮件正文
    private $attachment = array();//邮件附件
    
    /**
     * 设置链接域名邮箱的服务器地址(默认为腾讯企业邮箱)
     * 和$this->setPort结合使用
     * @param string $host
     */
    public function setHost( $host ) {
        $this->host = strval( $host );
    }    
    /**
     * 设置ssl连接smtp服务器的远程服务器端口号(默认为465:腾讯企业邮箱的端口号)
     * 和$this->setHost结合使用
     * @param int $port
     */
    public function setPort( $port ) {
        $this->host = intval( $port );
    }
    /**
     * 设置发件人姓名（昵称）可为任意内容（默认：回收宝-北京移动事业部）
     * @param string $name
     */
    public function setFromName( $name ) {
        $this->fromName = $name;
    }
    /**
     * 设置smtp登录的账号 
     * @param string $username
     */
    public function setUsername( $username ) {
        $this->userName = $username;
    }
    /**
     * 设置smtp登录的密码 
     * @param string $password
     */
    public function setPassword( $password ) {
        $this->password = $password;
    }
    /**
     * 设置发件人邮箱地址
     * @param string $name
     */
    public function setFromAdd( $address ) {
        $this->from = $address;
    }
    /**
     * 设置收件人邮箱地址（地址）
     * @param array $address 【必须是二维数组】
     * array(
     *      array(
     *          'address' => '',【规定键】
     *          'name' => '',【规定键】【可选】
     *      )
     * )
     */
    public function setSendAdd( $address ) {
        $this->address = $address;
    }
    /**
     * 设置邮件标题
     * @param string $subject
     */
    public function setSubject( $subject ) {
        $this->subject = $subject;
    }
    /**
     * 设置邮件内容(支持H5内容)
     * @param string $body
     */
    public function setBody( $body ) {
        $this->body = $body;
    }
    /**
     * 设置添加附件
     * @param array $attachment 【必须是二维数组】
     * array(
     *      array(
     *          'path' => '',【必须】//文件路径（绝对目录）
     *          'name' => '',【可选】//接收人看到的附件件名称
     *      )
     * )
     */
    public function setAttachment( $attachment ) {
        $i = 0;
        foreach ($attachment as $value) {
            if ( file_exists($value['path']) ){
                $this->attachment[$i]['path'] = $value['path'];
                $this->attachment[$i]['name'] = ( isset( $value['name'] ) ? $value['name']: '');
                $i++;
            }
        }
    }
    
    
    public function send(  ) {
        //邮件发送
		date_default_timezone_set('PRC');
		ignore_user_abort();
		set_time_limit(0);
		$mail = new PHPMailer(); 
		//是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
		//	$mail->SMTPDebug = 3;
		//使用smtp鉴权方式发送邮件
		$mail->isSMTP();
		//smtp需要鉴权 这个必须是true
		$mail->SMTPAuth=true;
		//链接域名邮箱的服务器地址 
		$mail->Host = $this->host;
		//设置ssl连接smtp服务器的远程服务器端口号
		$mail->Port = $this->port;
		//设置使用ssl加密方式登录鉴权
		$mail->SMTPSecure = 'ssl';
		//设置发件人的主机域 可有可无 默认为localhost 内容任意，建议使用你的域名,这里为默认localhost 
		$mail->Hostname = 'localhost';
		$mail->CharSet = 'UTF-8';
		//设置发件人姓名（昵称）可为任意内容
		$mail->FromName = $this->fromName;
		//smtp登录的账号 
		$mail->Username = $this->userName;
		//smtp登录的密码
		$mail->Password = $this->password;
		//设置发件人邮箱地址
		$mail->From = $this->from;
		//邮件正文是否以html方式发送
		$mail->isHTML(true); 
		//设置收件人邮箱地址（可多次设置，设置多个）
		foreach ( $this->address as $address) {
			$mail->addAddress( $address['address'], ( isset( $address['name'] ) ? $address['name'] : '' ) );            
		}
		//添加该邮件的主题
		$mail->Subject = $this->subject;  
		//添加邮件正文
		$mail->Body = $this->body;
		//为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） ；第二参数为在邮件附件中该附件的名称  （可多次设置，设置多个）
		//获取当前服务器根目录
		foreach ( $this->attachment as $value) {
			$mail->addAttachment( $value['path'], $value['name'] );
		}
		$status = $mail->send();
		if($status) {
				\App\Lib\Common\LogApi::error( '发送邮件成功'.date('Y-m-d H:i:s') );
		} else {
				\App\Lib\Common\LogApi::error( '发送邮件失败，错误信息未：'.$mail->ErrorInfo );
		}
			return $status;
	}
}
