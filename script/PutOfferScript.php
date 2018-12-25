<?php
class PutOfferScript{
    public function __construct($key)
    {
        $data = redis('rpop',$key);
        if(empty($data)){
            return false;
        }
        log($data);
    }
}