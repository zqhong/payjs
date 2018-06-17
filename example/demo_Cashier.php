<?php
/**
 * Created by PhpStorm.
 * User: lixia
 * Date: 2018/4/18
 * Time: 19:24
 */
require __DIR__ . '/../vendor/autoload.php';

// 官方文档：https://payjs.cn/help/api-lie-biao/shou-yin-tai-zhi-fu.html
$config = [
    'merchantId' => '',                      //商户号
    'merchantKey' => '',                     //密钥
    'notifyUrl' => 'https://www.baidu.com/', //notify地址 接收微信支付异步通知的回调地址。必须为可直接访问的URL，不能带参数、session验证、csrf验证。留空则不通知 需要保留最后的斜杠
];

$payjs = new \Musnow\Payjs\Pay($config);

$data = [
    'total_fee' => 1,          //金额，单位 分
    'body' => '测试订单',       //订单标题
    'attach' => '测试订单',    //用户自定义数据，在notify时会原样返回
    'out_trade_no' => time(),   //商户订单号，需要保证唯一
    'callback_url' => '',      //用户支付成功后，前端跳转地址。留空则支付后关闭webview
];

$ret = $payjs->cashier($data);  //收银台模式
print_r($ret);                  //返回数据
