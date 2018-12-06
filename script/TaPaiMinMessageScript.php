<?php
class TaPaiMinMessageScript extends BaseScript{
    public $message;
    // public $user_list_api = 'https://api.tapai.net.cn/V1/mini/sign/notify/users/';
    public $user_list_api = 'https://api.tapai.tv/V1/mini/sign/notify/users/';
    public $index = 1;

    public function __construct()
    {
        while(true){
            $data = json_decode(curl($this->user_list_api.$this->index,'GET'));
            if($data->Code != 0){
                logs('请求接口出错-->'.$data->Msg);
                exit;
            }
            if($data->Data->notify_users){
                foreach($data->Data->notify_users  as $user){
                    $current_time = time();
                    $sql = "select openid,form_id from vod_form_id where uid = {$user->user_id} and expire_time > {$current_time} limit 1";
                    $user_mini = mysqlExe($sql);
                    if($user_mini){
                        $this->message['data'] = [
                            'keyword1' => ['value' => "第{$user->sign_day}签到提醒"],
                            'keyword2' => ['value' => "你有{$user->un_get_gold}金币待领取,别中断了哦"]
                        ];
                        $this->message['page'] = $this->pageFormat(4);
                        $this->message['touser'] = $user_mini[0]['openid'];
                        $this->message['template_id'] = conf('wx.template.sign');
                        $this->message['form_id'] = $user_mini[0]['form_id'];
                        // $this->message['emphasis_keyword'] = 'keyword1.DATA'
                        if($this->sendMessage($this->message)){
                            logs($user->user_id.'发送成功');
                        }else{
                            logs($user->user_id.'发送失败');
                        }
                    }else{
                        logs("{$user->user_id}木有form_id");
                    }
                }
                $this->index += 1;
            }else{
                break;
            }
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
}