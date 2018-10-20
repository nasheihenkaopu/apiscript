<?php
class RecallMessageScript extends BaseScript{

    public $openid_key = 'tapai.mini:view.record.';

    public function __construct()
    {
        parent::__construct();
        $this->getSendOpenids();
    }

    /**
     * 获取需要发送消息的openid
     */
    public function getSendOpenids(){
        //获取有效的form_id 
        $sql = 'select openid,form_id from vod_form_id where expire_time > ' . time() . ' group by openid order by expire_time ASC';
        $users = mysqlExe($sql);
        logs('获取有效的form_id'.$sql);

        //查询配置
        $sql = 'select day,title,body,page,page_param from vod_wx_recall_message';
        $recall_confs = mysqlExe($sql);
        logs('查询配置'.$sql);
        
        //遍历所有可发送的用户
        foreach($users as $user){
            //判断今天是否发过通知
            if($this->checkSendMessage($user['openid'])){
                continue;
            }
            //获取用户最后一次访问时间
            $last_time = redis('get',$this->openid_key . $user['openid']);
            logs($user['openid'].'最后一次访问时间'.$last_time);
            if($last_time){
                //几天没访问
                $day = floor((time()-$last_time)/86400);
                logs($user['openid'] .'用户'.$day.'天没有访问过');
                //遍历配置,如果未访问的天数等于配置的天数,则发召回消息
                foreach($recall_confs as $recall_conf){
                    if($day == $recall_conf['day']){
                        logs('发送通知');
                        $send_status = $this->sendMessage(
                            json_encode([
                                'data'=>[
                                    'keyword1' => ['value' => $recall_conf['title']],
                                    'keyword2' => ['value' => $recall_conf['body']]
                                ],
                                'page'=>$this->pageFormat($recall_conf['page'],$recall_conf['page_param']),
                                'touser'=>$user['openid'],
                                'template_id'=> conf('wx.template_id'),
                                'form_id'=>$user['form_id']
                            ])
                        );

                        //发送成功,删除form_id
                        if ($send_status === true) {
                            $sql = "delete from vod_form_id where form_id = '{$user['form_id']}'";
                            mysqlExe($sql);
                            $this->sendOpenid($user['openid']);
                        }
                    }
                }
            }
        }
    }

    /**
     * 格式化页面
     */
    public function pageFormat($page_type,$page_param){
        $page = conf('wx.page')[$page_type];
        if (strpos($page, '{post_id}')) {
            $sql = 'select post_class from vod_post where vod_id = ' .$page_param;
            $res = mysqlExe($sql);
            $page = strtr($page, ['{post_id}' => $page_param,'{post_class}'=>$res[0]['post_class']]);
        }
        return $page;
    }

    /**
     * 查看已发送集合中是否有此opendid
     */
    public function checkSendMessage($openid)
    {
        if (redis('Sismember', 'tapai:wechat:message:openid', $openid )) {
            return true;
        } else {
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
}