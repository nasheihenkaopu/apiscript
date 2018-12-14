<?php
class InvitationMessageScript extends BaseScript{
    //从redis获取到的数据
    public $data;
    //微信发送通知的参数
    public $message;
    //任务key
    public $task_key = 'tapai:wechat:invite.message:queue';

    const M = [
        //获取金币时的模板消息
        1 => 'getGold',
        //被邀请人评论的模板消息
        2 => 'comment',
    ];

    public function __construct()
    {
        //获取任务
        $this->data = json_decode(redis('lpop', $this->task_key));
        if (!$this->data) {
            return false;
        }

        //消息内容处理
        $func = self::M[$this->data->type];
        if($this->$func() === false){
            logs('invitation信息处理错误');
            logs($this->data);
            return false;
        }

        if ($this->message) {
            $send_status = parent::sendMessage($this->message);
        } else {
            logs('缺少参数message');
            logs($this->data);
        }
    }

    public function getGold(){
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

        //查询用户昵称
        $sql = "select name from vod_user where uid = {$this->data->array_uid} and uid != 0 limit 1";
        $user_name = mysqlExe($sql);
        if(!$user_name){
            logs('没查询到user_name');
            return false;
        }

        $this->message['data'] = [
            'keyword1' => ['value' =>$user_name[0]['name']],
            'keyword2' => ['value' =>$this->data->content]
        ];
        $this->message['page'] = $this->pageFormat(5);
        $this->message['touser'] = $form_id_res[0]['openid'];
        $this->message['template_id'] = conf('wx.template.invitaion_get_gold');
        $this->message['form_id'] = $form_id_res[0]['form_id'];
    }

    public function comment(){
        
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
}