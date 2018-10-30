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
            redis('set', $token_key, $res->access_token);
            redis('Expire', $token_key, 6000);
            return $res->access_token;
        }
    }

    /**
     * 发送微信模板消息
     */
    public function sendMessage($message){
        $app_token = self::getWxToken();

        if($app_token && $message){
            $message_json = json_encode($message);
            $send_message_api = strtr(conf('wx.send_notice_api'), ['{MY_TOKEN}' =>$app_token]);
            //请求发送模板消息的api
            $res = json_decode(curl($send_message_api,'POST', $message_json));
            if($res->errcode == 0){
                logs('发送成功');
                logs($message);
                //删除form_id
                $sql = "delete from vod_form_id where form_id = '{$message['form_id']}'";
                if (mysqlExe($sql)) {
                    logs('删除form_id成功->' .$message['form_id']);
                } else {
                    logs('删除form_id失败->' .$message['form_id']);
                }
                //一个openid,24小时内只能发送一次
                self::sendOpenid($message['touser']);
                return true;
            }else if($res->errcode == 40001){
                /**{"errcode":40001,"errmsg":"invalid credential, access_token is invalid or not latest hint: [KnmweA0112vr57!]"}
                 * 未找到原因,官方社区也有此问题,无解,未到时效,且同一个token有时有效,有时无效,如果出现此问题,重试2次,还不行,放弃
                 * */
                logs('发送失败,token出错');
                logs($res);
                for($i = 0;$i<2;$i++){
                    sleep(1);
                    $res = json_decode(curl($send_message_api, 'POST', $message_json));
                    if($res->errcode == 0){
                        logs('重试第'.$i.'次发送成功');
                        logs($message);
                        return true;
                    }else{
                        logs('重试第'.$i.'次发送失败');
                        logs($message);
                        logs($res);
                        logs('token->'.$app_token);
                    }
                }
                return false;
            }else if($res->errcode == 41028 || $res->errcode == 41029){
                //{"errcode":41028,"errmsg":"invalid form id hint: [p.ewjA05223932]"}
                logs('发送失败,form_id出错');
                logs($res);
                $sql = "delete from vod_form_id where form_id = '{$message['form_id']}'";
                if(mysqlExe($sql)){
                    logs('删除form_id->'.$message['form_id'].'成功');
                }else{
                    logs('删除form_id->'.$message['form_id'].'失败');
                }
                return false;
            }else if($res->errcode == 40003){
                //{"errcode":40003,"errmsg":"invalid openid hint: [gCCsqa09373950]"}
                logs('发送失败,openid出错');
                logs($res);
                $sql = "delete from vod_form_id where openid = '{$message['touser']}'";
                if (mysqlExe($sql)) {
                    logs('删除openid->' . $message['touser'] . '成功');
                } else {
                    logs('删除openid->' . $message['touser'] . '失败');
                }
                return false;
            }else{
                logs('发送失败,未知原因');
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

    /**
     * 发送openid到集合
     */
    public function sendOpenid($openid)
    {
        $is = redis('EXISTS', 'tapai:wechat:message:openid');
        if ($is) {
            redis('sadd', 'tapai:wechat:message:openid', $openid);
        } else {
            redis('sadd', 'tapai:wechat:message:openid', $openid);
            $time = strtotime(date('Y-m-d', strtotime('+1 day')));    //设置过期时间
            redis('EXPIREAT', 'tapai:wechat:message:openid', $time);
        }
    }

    /**
     * @param $redis_key 每次调用,传入的key自增1
     */
    public function statistics($redis_key){
        redis('INCR',$redis_key);
    }
}