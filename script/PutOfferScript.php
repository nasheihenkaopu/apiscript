<?php
class PutOfferScript{
    public function __construct($key)
    {
        $data = redis('rpop',$key);
        if(!empty($data)){
            $data = json_decode($data,true);

            //上传到阿里云的数据
            $request_data = array();
            $request_data['request_mark'] = 'to_offer';
            $request_data['request_appname'] = $data['appname'];
            $request_data['request_offerid'] = $data['offerid'];
            $request_data['request_channel'] = $data['channel'];
            $request_data['request_callbackid'] = $data['callbackid'];
            $request_data['request_create_time'] = time();

            //查询渠道配置的回调地址
            $sql = 'select callback from dd_offer_callback where id = ' . $data['callbackid'];
            $res = mysqlExe($sql);
            if(empty($res)){
                $request_data['request_link'] = '无配置';
                return;
            }
            $request_data['request_link'] = $res[0]['callback'];

            $results = curl($res[0]['callback'],"GET");
            $request_data['request_results'] = $results;

            //上传日志到阿里云
            aliyun_log_put($request_data);
        }
    }
}