<?php

use think\facade\Env;

return [
    'default' => Env::get('filesystem.driver', 'public'),
    // 磁盘列表
    'disks' => [
        'local' => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            // 磁盘类型
            'type' => 'local',
            // 磁盘路径
            'root' => app()->getRootPath() . 'public/' . config('conf.static_path'),
            // 磁盘路径对应的外部URL路径
            'url' => '/' . config('conf.static_path'),
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
    ],
];
