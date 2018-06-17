<?php
/**
 * Created by PhpStorm.
 * User: lixia
 * Date: 2017/12/25
 * Time: 17:53
 */

namespace Musnow\Payjs;

class Pay
{
    private $ssl = true;
    private $requestUrl = 'https://payjs.cn/api/';
    private $MerchantID;
    private $MerchantKey;
    private $NotifyURL = null;
    private $AutoSign = true;
    private $ToObject = true;

    /**
     * Payjs constructor
     * @param $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * 扫码支付
     *
     * @param array $data
     * @return string
     */
    public function qrPay(array $data = [])
    {
        return $this->merge('native', [
            'total_fee' => $data['TotalFee'],
            'body' => $data['Body'],
            'attach' => @$data['Attach'],
            'out_trade_no' => $data['outTradeNo']
        ]);
    }

    /**
     * 收银台支付
     *
     * @param array $data
     * @return string
     */
    public function cashier(array $data = [])
    {
        return $this->merge('cashier', [
            'total_fee' => $data['TotalFee'],
            'body' => $data['Body'],
            'attach' => @$data['Attach'],
            'out_trade_no' => $data['outTradeNo'],
            'callback_url' => @$data['callbackUrl']
        ]);
    }

    /**
     * 订单查询
     *
     * @param array $data
     * @return string
     */
    public function query(array $data = [])
    {
        return $this->merge('check', [
            'payjs_order_id' => $data['PayjsOrderId']
        ]);
    }

    /**
     * 关闭订单
     *
     * @param array $data
     * @return string
     */
    public function close(array $data = [])
    {
        return $this->merge('close', [
            'payjs_order_id' => $data['PayjsOrderId']
        ]);
    }

    /**
     * 获取用户资料
     *
     * @param array $data
     * @return string
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
     * @return string
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
        return strtoupper(md5(urldecode(http_build_query($data)) . '&key=' . $this->MerchantKey));
    }

    /**
     * 预处理数据
     *
     * @param string $method
     * @param array $data
     * @return mixed
     */
    protected function merge($method, array $data = [])
    {
        if ($this->AutoSign) {
            if (!array_key_exists('payjs_order_id', $data)) {
                $data['mchid'] = $this->MerchantID;
                if (!empty($this->NotifyURL)) {
                    $data['notify_url'] = $this->NotifyURL;
                }
                if (is_null(@$data['attach'])) {
                    unset($data['attach']);
                }
            }
            $data['sign'] = $this->sign($data);
        }
        return $this->curl($method, $data);
    }

    /**
     * 发送 curl 请求
     *
     * @param $method
     * @param $data
     * @param array $options
     * @return string|boolean 失败返回 false，成功则返回 string
     */
    protected function curl($method, array $data, $options = [])
    {
        $url = $this->requestUrl . $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        if (!$this->ssl) {
            // https请求 不验证证书和host
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $curlResult = curl_exec($ch);
        curl_close($ch);

        if ($curlResult) {
            if ($this->ToObject) {
                return json_decode($curlResult);
            } else {
                return $curlResult;
            }
        } else {
            return false;
        }
    }
}
