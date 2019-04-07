<?php
return [
        'debug' => false,
        'sms_verify' => true,
        'hour_fc' => 2,
        'day_fc' => 10,
        'auth_expire_time' => 600,
        'app_secret' => '123456',
        'db' => [
            'mysql' => [
                'db' => 'mysql',
                'server' => '127.0.0.1',
                'port' => 3306,
                'database' => 'localpark',
                'user' => 'root',
                'pwd' => '123456',
                'tablepre' => 'pro_'
            ]
        ]
];
