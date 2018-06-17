<?php

namespace Musnow\Payjs;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

class Pay
{
    /**
     * payjs base url
     *
     * @var string
     */
    protected $baseUrl = 'https://payjs.cn/api/';

    /**
     * 超时时间，单位：秒
     *
     * @var int
     */
    protected $timeout = 2;

    /**
     * 是否检查 ssl 证书
     *
     * @var bool
     */
    protected $ssl = true;

    /**
     * 商户号
     *
     * @var string
     */
    protected $merchantId;

    /**
     * 接口通信密钥
     *
     * @var string
     */
    protected $merchantKey;

    /**
     * 回调地址
     *
     * @var string
     */
    protected $notifyUrl;

    /**
     * HTTP Client
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * @param array $config
     * @param ClientInterface|null $client
     */
    public function __construct(array $config = [], ClientInterface $client = null)
    {
        foreach ($config as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }

        if ($client instanceof ClientInterface) {
            $this->client = $client;
        } else {
            $this->client = new Client();
        }

        // 检查，merchantId 和 merchantKey 是必须项
        if (empty($this->merchantId) || empty($this->merchantKey)) {
            throw new \RuntimeException("必须填写 merchantId 或 merchantKey");
        }
    }

    /**
     * 扫码支付
     *
     * @param array $data
     * @return Response
     */
    public function qrPay(array $data = [])
    {
        return $this->merge('native', [
            'total_fee' => $data['total_fee'],
            'body' => $data['body'],
            'attach' => @$data['attach'],
            'out_trade_no' => $data['out_trade_no']
        ]);
    }

    /**
     * 收银台支付
     *
     * @param array $data
     * @return Response
     */
    public function cashier(array $data = [])
    {
        return $this->merge('cashier', [
            'total_fee' => $data['total_fee'],
            'body' => $data['body'],
            'attach' => @$data['attach'],
            'out_trade_no' => $data['out_trade_no'],
            'callback_url' => @$data['callback_url']
        ]);
    }

    /**
     * 订单查询
     *
     * @param array $data
     * @return Response
     */
    public function query(array $data = [])
    {
        return $this->merge('check', [
            'payjs_order_id' => $data['payjs_order_id']
        ]);
    }

    /**
     * 关闭订单
     *
     * @param array $data
     * @return Response
     */
    public function close(array $data = [])
    {
        return $this->merge('close', [
            'payjs_order_id' => $data['payjs_order_id']
        ]);
    }

    /**
     * 获取用户资料
     *
     * @param array $data
     * @return Response
     */
    public function user(array $data = [])
    {
        return $this->merge('user', [
            'openid' => $data['openid']
        ]);
    }

    /**
     * 获取商户资料
     *
     * @return Response
     */
    public function info()
    {
        return $this->merge('info');
    }

    /**
     * 验证notify数据
     *
     * @param array $data
     * @return bool
     */
    public function checking(array $data = [])
    {
        $beSign = $data['sign'];
        unset($data['sign']);
        if ($this->sign($data) == $beSign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 数据签名
     *
     * @param array $data
     * @return string
     */
    protected function sign(array $data)
    {
        ksort($data);
        return strtoupper(md5(urldecode(http_build_query($data)) . '&key=' . $this->merchantKey));
    }

    /**
     * 预处理数据
     *
     * @param string $method
     * @param array $data
     * @return Response
     */
    protected function merge($method, array $data = [])
    {
        if (!array_key_exists('payjs_order_id', $data)) {
            $data['mchid'] = $this->merchantId;
            if (!empty($this->notifyUrl)) {
                $data['notify_url'] = $this->notifyUrl;
            }
            if (is_null(@$data['attach'])) {
                unset($data['attach']);
            }
        }
        $data['sign'] = $this->sign($data);

        return $this->request($method, $data);
    }

    /**
     * 发送 HTTP 请求
     *
     * @param string $method
     * @param array $postData
     * @return Response
     */
    protected function request($method, array $postData)
    {
        $url = trim($this->baseUrl, '/') . '/' . $method;

        $options = [
            'form_params' => $postData,
            'timeout' => $this->timeout,
            'read_timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
        ];
        if ($this->ssl === false) {
            $options['verify'] = false;
        }

        return $this->client->request('POST', $url, $options);
    }
}
