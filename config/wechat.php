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
// | gitee 代码仓库3：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库3：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

return [
    // 微信开放平台接口
    'service_url' => 'https://demo.thinkadmin.top',
    // 小程序支付参数
    'miniapp'     => [
        'appid'      => getenv('WECHAT_APPID') ?: '',
        'appsecret'  => getenv('WECHAT_APPSECRET') ?: '',
        'mch_id'     => getenv('WECHAT_MCH_ID') ?: '',
        'mch_key'    => getenv('WECHAT_MCH_KEY') ?: '',
        'ssl_p12'    => __DIR__ . '/cert/1332187001_20181030_cert.p12',
        'cache_path' => env('runtime_path') . 'wechat' . DIRECTORY_SEPARATOR,
    ],
];
