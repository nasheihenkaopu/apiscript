<?php

/**
 * 记录日志
 * @param $log string   写入日志的内容
 */
function logs($log){
    $log_file = ROOT_PATH.'logs'. DIRECTORY_SEPARATOR.date('Y-m-d').'.log';
    if(is_array($log) || is_object($log)){
        $log = json_encode($log);
    }
    $log = date('H:i:s').'-->'.$log. PHP_EOL;
    file_put_contents($log_file,$log, FILE_APPEND);
}

/**
 * 读取env配置
 */
function env($key){
    return getenv($key);
}

/**
 * 执行sql
 * @param $sql string   传入的sql语句
 * @return 执行返回的结果
 */
function mysqlExe($sql){
    //读写分离
    if(strpos($sql,'select')){
        $host = conf('mysql.red.host');
    }else{
        $host = conf('mysql.write.host');
    }
    $mysql = new mysqli(
        $host,
        conf('mysql.username'),
        conf('mysql.password'),
        conf('mysql.database')
    );
    $mysql->set_charset('utf8');
    $res = $mysql->query($sql);
    if($res === true){
        return true;
    }
    if($res === false){
        return false;
    }
    $data = $res->fetch_all(MYSQLI_ASSOC);
    $mysql->close();
    return $data;

}

/**
 * 返回redis对象
 * @return redis对象
 */
function redis($fun,$par, $par2 = false,$par3 = false){
    $redis = new Redis();
    $redis->connect(
        conf('redis.host'),
        conf('redis.port')
    );
    if($pwd = conf('redis.password')){
        $redis->auth(conf('redis.password'));
    }
    if($par3){
        $res = $redis->$fun($par, $par2,$par3);
    }else if($par2){
        $res = $redis->$fun($par, $par2);
    }else{
        $res = $redis->$fun($par);
    }
    $redis->close();
    return $res;

}

/**
 * curl请求
 */

 function curl($url,$method = 'POST',$parameter = ''){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,     //输入URL
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $parameter,
        CURLOPT_HTTPHEADER => array("cache-control: no-cache"),
    ));
    $tmp_result = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    return $tmp_result;
}

/**
 * 读取conf.php中的配置
 */
function conf($key)
{
    $config = require ROOT_PATH . 'conf.php';
    $ca = explode('.', $key);
    $count = count($ca);
    for($i = 0;$i<$count;$i++){
        if($i === 0){
            $res = $config[$ca[$i]];
        }else{
            $res = $res[$ca[$i]];
        }
    }
    return $res;
}