<?php
return [
    'transmission' => [
        'rpc_url' => 'http://localhost:9091/transmission/rpc',  // Transmission RPC地址
        'username' => '',  // 用户名，如果未设置身份验证则留空
        'password' => '',  // 密码，如果未设置身份验证则留空
    ],
    'qbittorrent' => [
        'api_url' => 'http://localhost:8080',  // qBittorrent WebUI地址
        'username' => 'admin',  // 默认用户名
        'password' => 'adminadmin',  // 默认密码
    ]
]; 