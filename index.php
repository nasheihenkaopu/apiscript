<?php

date_default_timezone_set('Asia/Shanghai');
header("Content-Type: text/html;charset=utf-8");

//PhpScript 目录
defined('ROOT_PATH') or define('ROOT_PATH',__DIR__. DIRECTORY_SEPARATOR);

//自动加载
function __autoload($class_name)
{

    //获取当前目录下所有文件夹
    $dir_handle = opendir(ROOT_PATH);
    $dir_map = array();
    while ($dir = readdir($dir_handle)) {
        if (!strpos($dir, '.')) {
            $dir_map[] = $dir;
        }
    }
    closedir($dir_handle);
    //判断传入的类名是哪个文件夹下的文件
    $class_name_lower = strtolower($class_name);
    $dir = ROOT_PATH;
    foreach ($dir_map as $dir_name) {
        if (strpos($class_name_lower, $dir_name)) {
            $dir .= $dir_name . '/';
            break;
        }
    }
    //加载相应的文件
    $file = $dir . $class_name . '.php';
    if (file_exists($file)) {
        require $file;
    } else {
        echo "找不到文件" . $file;
        exit;
    }
}

// 加载环境变量配置文件
if (is_file(ROOT_PATH . '.env')) {  //D:\xampp\htdocs\mytp\tp\.env
    $env = parse_ini_file(ROOT_PATH . '.env', true);    //解析env文件,name = PHP_KEY
    foreach ($env as $key => $val) {
        $name = strtoupper($key);
        if (is_array($val)) {
            foreach ($val as $k => $v) {    //如果是二维数组 item = PHP_KEY_KEY
                $item = $name . '_' . strtoupper($k);
                putenv("$item=$v");
            }
        } else {
            putenv("$name=$val");
        }
    }
}

if (!isset($argv[1])) {
    echo '请输入参数!';
    exit;
} else {
    $class = $argv[1];
}

//公共方法
require 'CommonFunction.php';

while(true){

    //规定时间运行 ,格式 '起始时间-结束时间' 单位:时,每10分钟判断一次,不设置时间,则一直运行
    if (isset($argv[2])) {
        $run_time = explode('-', $argv[2]);
        $hours = date('H');
        if (!($run_time[0] <= $hours && $run_time[1] >= $hours)) {
            sleep(60*10);
            continue;
        }
    }

    new $class();
    usleep(500000);
}
    