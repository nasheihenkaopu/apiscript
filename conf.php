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
        'port' => '3306',
        'database' => env('ALY_CONNECTION_DB'),
        'username' => env('ALY_CONNECTION_USER'),
        'password' => env('ALY_CONNECTION_PASSWORD'),
 
    ],
    'redis' => [
        'host' => env('REDIS_HOST'),
        'port' => env('REDIS_PORT'),
        'password' => env('REDIS_PASSWORD')
    ],
    'command'=>[
        'putAdv'=>strtr(env('COMMAND_PUT_ADV'),['{ROOT_PATH}'=>ROOT_PATH]),
        'putOffer'=>strtr(env('COMMAND_PUT_OFFER'),['{ROOT_PATH}'=>ROOT_PATH])
    ]
);