<?php
class BaseScript{

    /**
     * 获取微信小程序token
     */
    public function getWxToken()
    {
        //access_token在redis的key
        $token_key = 'tapai:mini:access.token';

        $app_access_token = redis('get', $token_key);
        if($app_access_token){
            return $app_access_token;
        }else{
            $get_token_api = strtr(conf('wx.get_token_api'), ['{MY_APPID}' => conf('wx.app_id'), '{MY_APPSECRET}' => conf('wx.secret')]);
            $res = json_decode(curl($get_token_api));
            redis('set', $token_key,$res->access_token);
            redis('Expire',$token_key,6000);
            return $res->access_token; 
        }
    }

    /**
     * 发送微信模板消息
     */
    public function sendMessage($message){
        $app_token = $this->getWxToken();

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
                logs('token->' . $app_token);
                return false;
            }
        }else{
            logs('缺少参数apptoken->'.$app_token.'message->'.$message);
            return false;
        }
    }
}