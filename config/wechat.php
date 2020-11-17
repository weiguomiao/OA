<?php
/**
 * 微信小程序配置文件
 */
return [
    //微信小程序
    'mini_program' => [
        'app_id' => 'wxc298ca53b7edc2fc',         // AppID
        'secret' => 'b1ff2c2be8dfd7ffd337fdc0ddb0b7ec',     // AppSecret

        // 下面为可选项
        // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
        'response_type' => 'array',
        'log' => [
            'level' => 'debug',
            'file' => __DIR__ . '/../runtime/log/wechat.log',
        ],
    ],
    //微信公众号
    'official_account' => [
        'app_id' => 'wx1731ad785a5a49b3',
        'secret' => '4a351224e500023ab8ed02c6f66a963e',
        // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
        'response_type' => 'array',
        'token' => 'ME9pwuXqUj6aldYz',
        'aes_key' => 'zWDiP7QFYLG7BRHIzgFzzFLD16pNe0GSEBu7UGXwzpZ'
    ],
];
