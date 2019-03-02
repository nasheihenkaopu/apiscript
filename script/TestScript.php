<?php
class TestScript{

    public function __construct(){
        $data = [];
        $data['request_mark'] = 'adv_to_offer';
        $data['request_link'] = 'wwww.baidu.com';
        $data['request_create_time']  = time();
        $data['request_results'] = 'success';
        $data['request_appname'] = 'appname';
        $data['request_offerid'] = 'offerid';
        $data['request_channel'] = 'channel';
        $data['request_callback_id'] = '';
        aliyun_log_put($data);
    }
}