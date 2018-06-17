<?php

namespace Musnow\Payjs\Test;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Musnow\Payjs\Pay;
use PHPUnit\Framework\TestCase;

class PayTest extends TestCase
{
    protected function tearDown()
    {
        \Mockery::close();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage 必须填写 merchantId 或 merchantKey
     */
    public function testConstructThrowException()
    {
        new Pay();
    }

    public function testConstructOk()
    {
        $pay1 = $this->getPayObj();
        $this->assertInstanceOf(Pay::class, $pay1);

        $client = new Client();
        $pay2 = $this->getPayObj($client);
        $this->assertInstanceOf(Pay::class, $pay2);
    }

    /**
     * 检查签名是否正确
     */
    public function testChecking()
    {
        $wrongSign = '12345';
        $rightSign = '6E946566490723BB5CDF6E92707F4685';

        $data = [
            'foo' => 'bar',
            'sign' => $wrongSign,
        ];
        $pay = $this->getPayObj();
        $this->assertSame(false, $pay->checking($data));

        $data['sign'] = $rightSign;
        $this->assertSame(true, $pay->checking($data));
    }

    public function testRequestOptions()
    {
        $timeout = 10;
        $ssl = false;

        $client = \Mockery::mock(Client::class);
        $client
            ->shouldReceive('request')
            ->with(
                'POST',
                'https://payjs.cn/api/info',
                \Mockery::subset([
                    'timeout' => $timeout,
                    'read_timeout' => $timeout,
                    'connect_timeout' => $timeout,
                    'verify' => $ssl,
                ], false)
            )
            ->andReturn(new Response(200, [], json_encode([
                "return_code" => 1,
                'status' => 1,
                'msg' => 'SUCCESS',
                'return_msg' => 'SUCCESS',
            ], JSON_UNESCAPED_UNICODE)));

        $pay = new Pay([
            'merchantId' => '123',
            'merchantKey' => '123',
            'ssl' => $ssl,
            'timeout' => $timeout,
        ], $client);
        $response = $pay->info();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testQrPay()
    {
        $totalFee = 100;
        $body = "测试订单";
        $attach = "123456789";
        $outTradeNo = uniqid();

        $this->requestTest('qrPay', 'https://payjs.cn/api/native', [
            'form_params' =>
                [
                    'total_fee' => $totalFee,
                    'body' => $body,
                    'attach' => $attach,
                    'out_trade_no' => $outTradeNo,
                ],
        ], [
                'total_fee' => $totalFee,
                'body' => $body,
                'attach' => $attach,
                'out_trade_no' => $outTradeNo,
            ]
        );
    }

    public function testCashier()
    {
        $totalFee = 100;
        $body = "测试订单";
        $attach = "123456789";
        $outTradeNo = uniqid();
        $callbackUrl = 'https://www.baidu.com';

        $this->requestTest('cashier', 'https://payjs.cn/api/cashier', [
            'form_params' =>
                [
                    'total_fee' => $totalFee,
                    'body' => $body,
                    'attach' => $attach,
                    'out_trade_no' => $outTradeNo,
                    'callback_url' => $callbackUrl,
                ],
        ], [
            'TotalFee' => $totalFee,
            'Body' => $body,
            'Attach' => $attach,
            'outTradeNo' => $outTradeNo,
            'callbackUrl' => $callbackUrl,
        ]);
    }

    public function testQuery()
    {
        $payJsOrderId = '12345678';

        $this->requestTest('query', 'https://payjs.cn/api/check', [
            'form_params' =>
                [
                    'payjs_order_id' => $payJsOrderId,
                ],
        ], [
            'PayjsOrderId' => $payJsOrderId,
        ]);
    }

    public function testClose()
    {
        $payJsOrderId = '12345678';

        $this->requestTest('close', 'https://payjs.cn/api/close', [
            'form_params' =>
                [
                    'payjs_order_id' => $payJsOrderId,
                ],
        ], [
            'PayjsOrderId' => $payJsOrderId,
        ]);
    }

    public function testUser()
    {
        $openId = '12345678';

        $this->requestTest('user', 'https://payjs.cn/api/user', [
            'form_params' =>
                [
                    'openid' => $openId,
                ],
        ], [
            'openid' => $openId,
        ]);
    }

    public function testInfo()
    {
        $this->requestTest('info', 'https://payjs.cn/api/info', [
        ], [
        ]);
    }

    /**
     * @param string $payMethod Pay 对象方法
     * @param string $postUrl 请求地址
     * @param array $requestOptions guzzle 请求参数
     * @param array $methodData Pay 对象请求数据
     */
    protected function requestTest($payMethod, $postUrl, $requestOptions, $methodData)
    {
        $client = \Mockery::mock(Client::class);
        $client
            ->shouldReceive('request')
            ->with(
                'POST',
                $postUrl,
                \Mockery::subset($requestOptions, false)
            )
            ->andReturn(new Response(200, [], json_encode([
                "return_code" => 1,
                'status' => 1,
                'msg' => 'SUCCESS',
                'return_msg' => 'SUCCESS',
            ], JSON_UNESCAPED_UNICODE)));

        $pay = $this->getPayObj($client);
        $response = call_user_func_array([$pay, $payMethod], [$methodData]);
        $this->assertInstanceOf(Response::class, $response);
    }

    protected function getPayObj(ClientInterface $client = null)
    {
        if (is_null($client)) {
            $client = new Client();
        }

        $pay = new Pay([
            'merchantId' => '123',
            'merchantKey' => '123',
        ], $client);

        return $pay;
    }
}