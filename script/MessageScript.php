<?php
class MessageScript extends BaseScript
{
    const M = [
        1 => 'likeMessage',
        2 => 'commentMessage',
        3 => 'replyMessage'
    ];

    //从redis获取到的任务数据
    public $data;
    //微信发送通知的参数
    public $message;

    public function __construct()
    {
        //此脚本运行时间
        $this->runTime();

        parent::__construct();
        //获取任务
        $this->data = json_decode(redis('rpop', 'tapai:wechat:message:queue'));
        if (!$this->data) {
            return false;
        }
        logs('获取到任务');
        logs($this->data);
        $func = self::M[$this->data->type];
        if($this->$func() === false){
            logs('信息处理错误');
            logs($this->data);
            return false;
        }
        //检查今天是否发送过
        if ($this->checkSendMessage()) {
            //检查发送参数
            if ($this->message) {
                $send_status = $this->sendMessage(env('app_token'), json_encode($this->message));
                 //发送成功,删除form_id,并把openid设置到redis
                if ($send_status ===  true) {
                    $sql = "delete from vod_form_id where form_id = '{$this->message['form_id']}'";
                    mysqlExe($sql);
                    $this->sendOpenid();
                }
            } else {
                logs('缺少参数message');
                logs($this->data);
            }

        } else {
            logs('24小时内已经发过消息');
            logs($this->data);
        }
    }

    /**
     * 点赞通知
     */
    public function likeMessage()
    {
        //查询配置的标题,内容,页面
        $sql = "select title,body,page from vod_wx_push_auto where type = {$this->data->type} and status = 1 and number = {$this->data->number} limit 1";
        $push_auto_res = mysqlExe($sql);
        if (!$push_auto_res) {
            logs('没有配置');
            return false;
        }

        //先删除过期的form_id
        $sql = 'delete from vod_form_id where expire_time < ' . time();
        mysqlExe($sql);

        //查询接收者openid,form_id
        $sql = "select openid,form_id FROM vod_form_id WHERE uid = {$this->data->uid} and uid != 0 ORDER BY expire_time ASC LIMIT 1";
        $form_id_res = mysqlExe($sql);
        if (!$form_id_res) {
            logs('没有查询到openid');
            return false;
        }
        $this->message['data'] = [
            'keyword1' => ['value' => $push_auto_res[0]['title']],
            'keyword2' => ['value' => $push_auto_res[0]['body']]
        ];
        $this->message['page'] = $this->pageFormat($push_auto_res[0]['page']);
        $this->message['touser'] = $form_id_res[0]['openid'];
        $this->message['template_id'] = conf('wx.template_id');
        $this->message['form_id'] = $form_id_res[0]['form_id'];
    }

    /**
     * 评论通知
     */
    public function commentMessage()
    {
        //查询配置的标题,内容,页面
        $sql = "select title,body,page from vod_wx_push_auto where type = {$this->data->type} and status = 1 and number = {$this->data->number} limit 1";
        $push_auto_res = mysqlExe($sql);
        if (!$push_auto_res) {
            logs('没有配置');
            return false;
        }

        //查询评论者名称
        $sql = "select name from vod_user where uid = {$this->data->from_uid}";
        $reply_user = mysqlExe($sql);
        if (!$reply_user) {
            logs('没这个用户->' . $this->data->from_uid);
            return false;
        }

        //先删除过期的form_id
        $sql = 'delete from vod_form_id where expire_time < ' . time();
        mysqlExe($sql);

        //查询接收者openid,form_id
        $sql = "select openid,form_id FROM vod_form_id WHERE uid = {$this->data->uid} and uid != 0 ORDER BY expire_time ASC LIMIT 1";
        $form_id_res = mysqlExe($sql);
        if (!$form_id_res) {
            return false;
        }
        $this->message['data'] = [
            'keyword1' => ['value' => $push_auto_res[0]['title']],
            'keyword2' => ['value' => $reply_user[0]['name'] . '评论了你:' . mb_substr($this->data->content, 0, 50)]
        ];
        $this->message['page'] = $this->pageFormat($push_auto_res[0]['page']);
        $this->message['touser'] = $form_id_res[0]['openid'];
        $this->message['template_id'] = conf('wx.template_id');
        $this->message['form_id'] = $form_id_res[0]['form_id'];
    }

    /**
     * 回复通知
     */
    public function replyMessage()
    {
        //查询配置的标题,内容,页面
        $sql = "select title,body,page from vod_wx_push_auto where type = 2 and status = 1 and number = {$this->data->number} limit 1";
        $push_auto_res = mysqlExe($sql);
        if (!$push_auto_res) {
            logs('没有配置');
            return false;
        }

        //查询回复者id
        $sql = "select name from vod_user where uid = {$this->data->from_uid}";
        $comment_user = mysqlExe($sql);
        if (!$comment_user) {
            return false;
        }

        //先删除过期的form_id
        $sql = 'delete from vod_form_id where expire_time < ' . time();
        mysqlExe($sql);

        //查询接收者openid,form_id
        $sql = "select openid,form_id FROM vod_form_id WHERE uid = {$this->data->uid} and uid != 0 ORDER BY expire_time ASC LIMIT 1";
        $form_id_res = mysqlExe($sql);
        if (!$form_id_res) {
            return false;
        }
        $this->message['data'] = [
            'keyword1' => ['value' => $push_auto_res[0]['title']],
            'keyword2' => ['value' => $comment_user[0]['name'] . '回复了你:' . mb_substr($this->data->content, 0, 50)]
        ];
        $this->message['page'] = $this->pageFormat($push_auto_res[0]['page']);
        $this->message['touser'] = $form_id_res[0]['openid'];
        $this->message['template_id'] = conf('wx.template_id');
        $this->message['form_id'] = $form_id_res[0]['form_id'];
    }

    /**
     * 发送openid到集合
     */
    public function sendOpenid()
    {
        $is = redis('EXISTS', 'tapai:wechat:message:openid');
        if ($is) {
            redis('sadd', 'tapai:wechat:message:openid', $this->message['touser']);
        } else {
            redis('sadd', 'tapai:wechat:message:openid', $this->message['touser']);
            $time = strtotime(date('Y-m-d', strtotime('+1 day')));    //设置过期时间
            redis('EXPIREAT', 'tapai:wechat:message:openid', $time);
        }
    }

    /**
     * 查看已发送集合中是否有此opendid
     */
    public function checkSendMessage()
    {
        if (redis('Sismember', 'tapai:wechat:message:openid', $this->message['touser'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 页面处理
     */

    public function pageFormat($page_type){
        $page = conf('wx.page')[$page_type];
        if(strpos($page,'{post_id}')){
            $page = strtr($page,['{post_id}'=>$this->data->vod_id]);
        }
        if(strpos($page,'{post_class}')){
            $page = strtr($page,['{post_class}'=>$this->data->post_class]);
        }
        return $page;
    }

    /**
     * 脚本运行时间,22点-7点
     */
    public function runTime(){
        if(date('H') == 22){
            sleep(60*60*9);
        }
    }
}