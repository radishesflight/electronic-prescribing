<?php

namespace RadishesFlight\ElectronicPrescribing;

use Exception;
use RuntimeException;
use SplObjectStorage;

// 序列号与密码
define('API_CODE', '13311111111');
define('API_KEY', 'test123456');

class ElectronicPrescribingServer
{
    public $obj;

    private function __construct()
    {

    }

    public static function init()
    {
        return new self();
    }


    /**
     * 计算md5
     */
    function md5($input)
    {
        try {
            $md = hash_init('md5');
            hash_update($md, $input);
            return hash_final($md);
        } catch (Exception $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * 生成签名原串
     */
    public function signString($params, $unSignKeys)
    {
        $sb = '';
        $map = [];
        ksort($params);
        foreach ($params as $key => $value) {
            $map[$key] = $value;
        }
        foreach ($unSignKeys as $key) {
            unset($map[$key]);
        }
        foreach ($map as $key => $value) {
            if ($value !== null && $value !== 'null') {
                $sb .= $key . '=' . $value . '&';
            }
        }
        $sb = rtrim($sb, '&');
        return $sb;
    }

    /**
     * 参数签名
     */
    public function sign($params)
    {
        $params['api_code'] = API_CODE;
        $params['timestamp'] = time();
        $signstr = $this->signString($params, ['api_sign']);
        $params['api_sign'] = $this->md5($signstr . $this->md5(API_KEY));
        return $params;
    }
}
