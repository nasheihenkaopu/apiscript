<?php
class BaseScript{
    public function __construct()
    {
        $this->getWxToken();
    }

    /**
     * 获取微信小程序token
     */
    public function getWxToken()
    {
        //如果请求过,且不到两个小时则不再请求token
        if(env('wx_app_token') && env('wx_app_token_time')){
            if(time() - env('wx_app_token_time') < env('wx_app_token_expires') - 600){
                return;
            }
        }
        $get_token_api = strtr(conf('wx.get_token_api'), ['{MY_APPID}' => conf('wx.app_id'), '{MY_APPSECRET}' => conf('wx.secret')]);
        $res = json_decode(curl($get_token_api));
        putenv('wx_app_token='.$res->access_token);    //token
        putenv('wx_app_token_time='.time());           //这次请求的时间
        putenv('wx_app_token_expires='.$res->expires_in);  //token有效期
    }

    /**
     * 发送微信模板消息
     */
    public function sendMessage($message){
        $app_token = env('wx_app_token');

        if($app_token && $message){
            $send_message_api = strtr(conf('wx.send_notice_api'), ['{MY_TOKEN}' =>$app_token]);
            $res = json_decode(curl($send_message_api,'POST',$message));
            if($res->errcode == 0){
                logs('发送成功');
                logs($message);
                return true;
            }else{
                logs('发送失败');
                logs($message);
                logs($res);
                return false;
            }
        }else{
            logs('缺少参数apptoken->'.$app_token.'message->'.$message);
            return false;
        }
    }
}