<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 开源项目：https://gitee.com/zoujingli/ThinkAdmin
// | github开源项目：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

return [
    // 数据库调试模式
    'debug'       => (bool) getenv('APP_DEBUG'),
    // 数据库类型
    'type'        => 'mysql',
    // 服务器地址
    'hostname'    => getenv('MYSQL_HOST') ?: '127.0.0.1',
    // 数据库名
    'database'    => getenv('MYSQL_DATABASE') ?: '',
    // 用户名
    'username'    => getenv('MYSQL_USER') ?: '',
    // 密码（必须通过环境变量注入，禁止硬编码）
    'password'    => getenv('MYSQL_PASSWORD') ?: '',
    // 编码
    'charset'     => 'utf8mb4',
    // 端口
    'hostport'    => getenv('MYSQL_PORT') ?: '3306',
    // 主从
    'deploy'      => 0,
    // 分离
    'rw_separate' => false,
];
