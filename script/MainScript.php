<?php
class MainScript{
    //redis队列任务的标识
    public $put_adv = 'offer_to_adv';
    public $put_offer = 'adv_to_offer';
    public $key_prefix = 'zw_';
    public $script_list = [];

    public function __construct()
    {
        while(true){
            $keys = redis('keys',$this->key_prefix.'*');
            foreach ($keys as $key) {
                $length = redis('LLEN',$key);
                $arr_count_list = array_count_values($this->script_list);
            
                if(in_array($key,$this->script_list)){
                    //如果key中积累的任务过多,开启多进程
                    if($length < 500){
                        continue;
                    }
                    //但是,进程数量最多5个
                    if(!empty($arr_count_list[$key]) && $arr_count_list[$key] >= 5){
                        continue;
                    }  
                }
                logs('启动进程:'.$key);
                if(strpos($key,$this->put_adv)){
                    $this->putDataAdv($key);
                }else if(strpos($key,$this->put_offer)){
                    $this->putDataOffer($key);
                }
                //一个key只启动一个脚本
                $this->script_list[] = $key;
            }
            sleep(1);
        }
    }

    //启动一个进程,数据上传到广告主
    public function putDataAdv($task_queue_key){
        // var_dump(strtr(conf('command.putAdv'),['{PARAM}'=>$task_queue_key]));
        $comm = strtr(conf('command.putAdv'),['{PARAM}'=>$task_queue_key]);
        logs('启动进程:'.$comm);
        popen($comm,'r');
    }
    
    //启动一个进程,数据上传到渠道
    public function putDataOffer($task_queue_key){
        // var_dump(strtr(conf('command.putOffer'),['{PARAM}'=>$task_queue_key]));
        $comm = strtr(conf('command.putOffer'),['{PARAM}'=>$task_queue_key]);
        logs('启动进程:'.$comm);
        popen($comm,'r');
    }
}