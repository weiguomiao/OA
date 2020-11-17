<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 上传文件地址
    //'uploads_file' => "http://www.project.com/storage/",
    // 静态资源目录
    'static_path' => 'uploads',
    // 文件上传配置
    'upload_file' => [
        // 文件上传类
        'class' => \mytools\resourcesave\LocalSave::class,
        // 文件默认最大size(kb)
        'max_file_size' => 2048,
        // OSS配置
        'ali' => [
            // key
            'accessKeyId' => '',
            // 秘钥
            'accessKeySecret' => '',
            // Region请按实际情况填写
            'endpoint' => '',
            // 存储空间名称
            'bucket' => ''
        ],
    ],
];
