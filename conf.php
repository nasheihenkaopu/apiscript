<?php
return array(
    'mysql' => [
        'driver' => 'mysql',

        'read' => [
            'host' => env('ALY_CONNECTION_READ')
        ],
        'write' => [
            'host' => env('ALY_CONNECTION_WRITE')
        ],
            //'sticky'    => true,
        'port' => '3306',
        'database' => env('ALY_CONNECTION_DB'),
        'username' => env('ALY_CONNECTION_USER'),
        'password' => env('ALY_CONNECTION_PASSWORD'),
        'unix_socket' => '',
            //'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'vod_',
        'strict' => true,
        'engine' => null,
    ],
    'redis' => [
        'host' => env('REDIS_HOST'),
        'port' => env('REDIS_PORT'),
        'password' => env('REDIS_PASSWORD')
    ],
    'wx' => [
        //获取wx小程序token的api
        'get_token_api' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={MY_APPID}&secret={MY_APPSECRET}',
        //发送模板消息的api
        'send_notice_api' => 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={MY_TOKEN}',
        'template_id' => env('WX_TEMPLATE_ID'),
        'app_id' => env('WX_APPID'),
        'secret' => env('WX_SECRET'),
        //页面对应
        'page'=>[
            //首页
            1=>'pages/index/index',
            //帖子详情页
            2=> 'pages/index/index?post_id={post_id}',
            //消息列表页
            3=> 'pages/index/index?pcode=message',
        ]
    ]
);