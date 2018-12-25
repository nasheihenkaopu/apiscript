<?php
class PutAdvScript{
    public function __construct($key)
    {
        $data = redis('rpop',$key);
        if(empty($data)){
            return false;
        }
        logs($data);
    }
}