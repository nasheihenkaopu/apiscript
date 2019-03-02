<?php
class PutAdvScript{
    public $put_adv_api;
    public $callback = 'http://47.75.66.134/advertisers/report?';
    public function __construct($key)
    {
        $data = redis('rpop',$key);
        if(!empty($data)){
            $data = json_decode($data,true);

            //上传到阿里云的数据
            $request_data = array();
            $request_data['request_mark'] = 'to_adv';
            $request_data['request_appname'] = $data['appname'];
            $request_data['request_offerid'] = $data['offerid'];
            $request_data['request_channel'] = $data['channel'];
            $request_data['request_create_time'] = time();

            //查询广告主配置的回调地址
            $sql = "SELECT a.post_link FROM dd_offer o,dd_link l,dd_adv a WHERE o.id = l.offer AND a.id = l.adv AND o.offer_id = {$data['offerid']} AND o.channel = {$data['channel']} AND a.app_name = '{$data['appname']}' limit 1";
            $res = mysqlExe($sql);
            if(empty($res)){
                $request_data['request_link'] = '无配置';
            }

            //拼接上传url
            $this->put_adv_api = $res[0]['post_link'];
            foreach($data as $k=>$v){
                if($k == 'offerid' || $k == 'channel' || $k == 'appname' || $k == 'callback' || $k == 'offer_callback_id'){
                    continue;
                }
                $this->put_adv_api .= '&'.$k.'='.$v;
            }

            //拼接callback
            $this->put_adv_api .= '&callback='.urlencode($this->callback.'offerid='.$data['offerid'].'&channel='.$data['channel'].'&callbackid='.$data['offer_callback_id'].'&appname='.$data['appname']);
            $request_data['request_link'] = $this->put_adv_api;
            $request_data['request_callbackid'] = $data['offer_callback_id'];

            //请求广告主api
            $results = curl($this->put_adv_api,"GET");
            $request_data['request_results'] = $results;

            //上传日志到阿里云
            aliyun_log_put($request_data);
        }
    }
}