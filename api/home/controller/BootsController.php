<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;


use cmf\controller\RestBaseController;
use think\Db;

class BootsController extends RestBaseController
{
    // api 首页
    public function index()
    {
        $map['order_status'] = 0;
        $orders = Db::table("__ORDER__")->where($map)->count();
        $distribution = Db::table("__DISTRIBUTION__")->where("status > 0")->order("id DESC")->find();
        
        
        if($orders > 0 && $distribution['create_time'] + 600 < time()){
            $emails = '324834500@qq.com,2300092540@qq.com';
            
            $result = $this->send_email($emails, '测试', '测试内容');
            if (empty($result['error'])) {
                $this->success("预警邮件发送成功!");
            } else {
                $this->error("预警邮件发送失败:" . $result['message']);
            }            
        }else{
            $this->error("预警条件不成立！");
        }
    }
    
    
    /**
    * 自定义发送
    * @date: 2019年3月7日 上午9:41:31
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function send_email($address, $subject, $message)
    {
        $smtpSetting = cmf_get_option('smtp_setting');
        $mail        = new \PHPMailer();
        // 设置PHPMailer使用SMTP服务器发送Email
        $mail->IsSMTP();
        $mail->IsHTML(true);
        //$mail->SMTPDebug = 3;
        // 设置邮件的字符编码，若不指定，则为'UTF-8'
        $mail->CharSet = 'UTF-8';
        // 添加收件人地址，可以多次使用来添加多个收件人
        $addressArr = explode(',', $address);
        
        if(is_array($addressArr)){
            foreach ($addressArr as $address){
                $mail->AddAddress($address);
            }
        }else{
            $mail->AddAddress($addressArr);
        }
        
        
        // 设置邮件正文
        $mail->Body = $message;
        // 设置邮件头的From字段。
        $mail->From = $smtpSetting['from'];
        // 设置发件人名字
        $mail->FromName = $smtpSetting['from_name'];
        // 设置邮件标题
        $mail->Subject = $subject;
        // 设置SMTP服务器。
        $mail->Host = $smtpSetting['host'];
        //by Rainfer
        // 设置SMTPSecure。
        $Secure           = $smtpSetting['smtp_secure'];
        $mail->SMTPSecure = empty($Secure) ? '' : $Secure;
        // 设置SMTP服务器端口。
        $port       = $smtpSetting['port'];
        $mail->Port = empty($port) ? "25" : $port;
        // 设置为"需要验证"
        $mail->SMTPAuth    = true;
        $mail->SMTPAutoTLS = false;
        $mail->Timeout     = 10;
        // 设置用户名和密码。
        $mail->Username = $smtpSetting['username'];
        $mail->Password = $smtpSetting['password'];
        // 发送邮件。
        if (!$mail->Send()) {
            $mailError = $mail->ErrorInfo;
            return ["error" => 1, "message" => $mailError];
        } else {
            return ["error" => 0, "message" => "success"];
        }        
    }
}
