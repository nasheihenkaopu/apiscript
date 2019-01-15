<?php
class PutAdvScript{
    public $api = "http://chance.chance-ad.com/track/9164214c/59C3A0546923A287F781391C0392927B?campaignId=eo5Pp7AO5TS2&os=iOS&s2s=1&ip=221.220.224.221&idfa=D7FB0659-7D74-46A5-8393-EFFCDC9CB1C2&callback=http%3a%2f%2f47.75.66.134%2fadvertisers%2freport";
    public function __construct($key)
    {
        $data = redis('rpop',$key);
        if(!empty($data)){
            logs($data);
            logs(curl($this->api,"GET"));
        }
        
    }
}