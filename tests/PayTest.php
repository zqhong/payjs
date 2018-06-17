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

    public function testQrPay()
    {
        $totalFee = 100;
        $body = "测试订单";
        $attach = "123456789";
        $outTradeNo = uniqid();

        $ret = [
            "return_code" => 1,
            "return_msg" => "SUCCESS",
            "payjs_order_id" => "201806171022210088xxxxxxx",
            "out_trade_no" => $outTradeNo,
            "total_fee" => $totalFee,
            "qrcode" => "https://payjs.cn/qrcode/xxxxx",
            "code_url" => "weixin://wxpay/bizpayurl?pr=xxxxx",
        ];

        $client = \Mockery::mock(Client::class);
        $client
            ->shouldReceive('request')
            ->with(
                'POST',
                'https://payjs.cn/api/native',
                \Mockery::subset([
                    'form_params' =>
                        [
                            'total_fee' => $totalFee,
                            'body' => $body,
                            'attach' => $attach,
                            'out_trade_no' => $outTradeNo,
                        ],
                ])
            )
            ->andReturn(new Response(200, [], json_encode($ret, JSON_UNESCAPED_UNICODE)));

        $pay = $this->getPayObj($client);
        $qrPayResponse = $pay->qrPay([
            'TotalFee' => $totalFee,
            'Body' => $body,
            'Attach' => $attach,
            'outTradeNo' => $outTradeNo,
        ]);
        $qrPayJson = json_decode((string)$qrPayResponse->getBody(), true);
        $this->assertEquals($ret, $qrPayJson);
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