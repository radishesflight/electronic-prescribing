<?php

namespace RadishesFlight\ElectronicPrescribing;

use Exception;
use RuntimeException;

class ElectronicPrescribingServer
{
    public  $host;
    public  $apiCode = '';
    public  $apiKey = '';
    public  $params = [];

    public function __construct($host, $apiCode, $apiKey)
    {
        $this->host = $host;
        $this->apiCode = $apiCode;
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * 计算md5
     */
    private function md5($input)
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
    private function signString($params, $unSignKeys)
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
        $params['api_code'] = $this->apiCode;
        $microtime = microtime(true);
        $milliseconds = round($microtime * 1000);
        $params['timestamp'] = $milliseconds;
        $signstr = $this->signString($params, ['api_sign', 'file']);
        $params['api_sign'] = $this->md5($signstr . $this->md5($this->apiKey));
        return $params;
    }

    public function curl($url, $data)
    {
        $queryString = http_build_query($data);
        //$params数组处理成变量$string形式的字符串
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $queryString,
            CURLOPT_HTTPHEADER => [
                "Content-Type:application/x-www-form-urlencoded",
                "Accept:application/json",
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return json_decode($response, true);
        }
    }

    /**
     * @param $data
     * [
     * 'ywid'=>mt_rand(11111,99999),
     * 'status'=>1,
     * 'patient_name'=>'缪文国',
     * 'patient_age'=>'29',
     * 'patient_sex'=>'男',
     * 'patient_phone'=>'13589887777',
     * 'patient_idcard'=>'530321199409070016',
     * 'rx_item_json'=>'[{"project_code":"20220804536170","project_name":"鲑鱼降钙素鼻喷剂","standard":"第三⽅规格","total":"1","batchnumber":"批号","manufacturer_code":"⼚家编码","manufacturer_name":"⼚家名称"}]',
     * 'disease_json'=>'[{"icd_code":"编码","icd_name":"名称"}]',
     * ]
     * @param bool $isReturnObj
     * 创建处⽅订单
     */

    public function createRxinfo($data, bool $isReturnObj = true)
    {
        $data = $this->sign($data);
        $this->params = $this->curl($this->host . '/createRxinfo.json', $data);
        if ($this->params['success']===false){
            throw new RuntimeException($this->params['message']);
        }
        if ($isReturnObj) {
            return $this;
        }
        return $this->params;
    }

    /**
     * @param array $data
     * 获取处⽅订单信息
     */
    public function getRxinfo(array $data = [])
    {
        $data = empty($data) ? [
            'rx_id' => $this->params['list'][0]['rx_id'],
            'ywid' => $this->params['list'][0]['ywid'],
        ] : $data;
        $data = $this->sign($data);
        return $this->curl($this->host . '/getRxinfo.json', $data);
    }


    /**
     * @param $data
     * 获取平台处⽅pdf和图⽚下载地址
     */
    public function getRxFileUrl($data=[])
    {
        $data = array_merge([
            'rx_id' => $this->params['list'][0]['rx_id']??0,
            'ywid' => $this->params['list'][0]['ywid']??0,
            'style' => 1,//⽂档样式，1 横版,公章顶部居中 2 横版,公章右下 5 竖版,公章顶部居中 6 竖版,公章右下
        ],$data);
        $data = $this->sign($data);
        return $this->curl($this->host . '/getRxFileUrl', $data);
    }

    /**
     * 删除处方订单信息
     * @param array $data
     */
    public function deleteRxinfo(array $data = [])
    {
        $data = empty($data) ? [
            'rx_id' => $this->params['list'][0]['rx_id'],
            'ywid' => $this->params['list'][0]['ywid'],
        ] : $data;
        $data = $this->sign($data);
        return $this->curl($this->host . '/deleteRxinfo.json', $data);
    }

    /**
     * @param $data
     * 获取交互id,⽤于聊天及⽂件上传
     */
    public function getTempId($data)
    {
        $data = empty($data) ? [
            'call_url' => $this->params['list'][0]['call_url'],
        ] : $data;
        $data = $this->sign($data);
        return $this->curl($this->host . '/getTempId.json', $data);
    }


    /**
     * @param $data
     * 跳转到平台图⽂或视频沟通⻚⾯
     */
    public function goToRxChat($data = [])
    {
        $data = array_merge([
            'rx_id' => $this->params['list'][0]['rx_id']??0,
            'ywid' => $this->params['list'][0]['ywid']??0,
            'model' => 0,
            'control' => 0,//⻚⾯控制 0 默认 2 隐藏title(只对移动端⽣效)
            'client' => 1,//客户端类型 0 PC端(电脑⽹⻚) 1 移动端(⼩程序或⼿机⽹⻚)
        ], $data);
        $data = $this->sign($data);
        return  $this->host . '/goToRxChat.html?'.http_build_query($data);
    }


    /**
     * @param string $message
     * @param array $data
     * 发送聊天内容
     */
    public function createChat(array $data = [], string $message = '')
    {
        $data = empty($data) ? [
            'temp_id' => $this->params['list'][0]['temp_id'],
            'chat_text' => $message,
        ] : $data;
        $data = $this->sign($data);
        return $this->curl($this->host . '/createChat.json', $data);
    }

    /**
     * @param $file
     * @param string $file_url
     * @param array $data
     * 聊天⽂件上传
     */
    public function createChatFile(array $data = [], $file = null, string $file_url = '')
    {
        $data = empty($data) ? [
            'temp_id' => $this->params['list'][0]['temp_id'],
            'file' => $file,//⽂件(不参与签名),与file参数互斥,⼆者必须传⼀
            'file_url' => $file_url,//⽂件(不参与签名),与file参数互斥,⼆者必须传⼀
        ] : $data;
        $data = $this->sign($data);
        return $this->curl($this->host . '/createChatFile.json', $data);
    }

    /**
     * @param array $data
     * 获取聊天记录列表
     */
    public function queryChatLogList(array $data = [])
    {
        $data = empty($data) ? [
            'temp_id' => $this->params['list'][0]['temp_id'],
        ] : $data;
        $data = $this->sign($data);
        return $this->curl($this->host . '/queryChatLogList.json', $data);
    }

    /**
     * @param array $data ["keyword"=>"",//患者关键词，姓名/⼿机号/身份证 可为空 "rx_type"=>"",//类型 0 线上(⻔店扣费) 1线下 2 线上(总部扣费 "chinese_flag"=>"",//中药处⽅标识 1 是 0否 "rx_num"=>""//唯⼀标识，业务id或序列号]
     * 分⻚查询未完成处⽅列表
     */
    public function queryRxInfoPage(array $data = [])
    {
        $data = $this->sign($data);
        return $this->curl($this->host . '/queryRxInfoPage.json', $data);
    }

    /**
     * @param array $data ["keyword"=>"",//患者关键词，姓名/⼿机号/身份证 可为空 "rx_type"=>"",//类型 0 线上(⻔店扣费) 1线下 2 线上(总部扣费 "chinese_flag"=>"",//中药处⽅标识 1 是 0否 "rx_num"=>""//唯⼀标识，业务id或序列号]
     * 分⻚查询已完成处⽅列表
     */
    public function queryRxInfoHisPage(array $data = [])
    {
        $data = $this->sign($data);
        return $this->curl($this->host . '/queryRxInfoHisPage.json', $data);
    }

    /**
     * @param array $data
     * @return mixed|string
     * 药品字典对码匹配
     */
    public function medicineMatching(array $data=[])
    {
        $data = $this->sign($data);
        return $this->curl($this->host . '/medicineMatching.json', $data);
    }
}
