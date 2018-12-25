<?php
class MainScript{
    //redis队列任务的标识
    public $put_adv = 'offer_to_adv';
    public $Put_offer = 'adv_to_offer';
    public $script_list = [];

    public function __construct()
    {
        $keys = redis('keys','*');
        foreach ($keys as $key) {
            if(in_array($key,$this->script_list)){
                continue;
            }

            if(strpos($key,$this->put_adv)){
                $this->putDataAdv($key);
            }else if(strpos($key,$this->put_offer)){
                $this->putDataOffer($key);
            }
            //一个key只启动一个脚本
            $this->script_list[] = $key;
        }
    }

    //启动一个进程,数据上传到广告主
    public function putDataAdv($task_queue_key){
        var_dump(strtr(conf('command.putAdv'),['{PARAM}'=>$task_queue_key]));
        popen(strtr(conf('command.putAdv'),['{PARAM}'=>$task_queue_key]),'r');
    }
    
    //启动一个进程,数据上传到渠道
    public function putDataOffer($task_queue_key){
        var_dump(strtr(conf('command.putOffer'),['{PARAM}'=>$task_queue_key]));
        popen(strtr(conf('command.putOffer'),['{PARAM}'=>$task_queue_key]),'r');
    }
}