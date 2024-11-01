<?php

namespace App\Modules\tao\sdk\aliyun;

use App\Modules\tao\sdk\aliyun\sms\SignatureHelper;
use App\Modules\tao\sdk\SmsDriverInterface;

class AliyumSmsDriver implements SmsDriverInterface
{

    private array $config = [
        'security' => false, // 是否启用 https
        'accessKeyId' => '', // your access key id
        'accessKeySecret' => '',// your access key secret
    ];

    private array $params = [
        'PhoneNumbers' => '', // 短信接收号码
        // https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        'SignName' => '', // 短信签名
        // https://dysms.console.aliyun.com/dysms.htm#/develop/template
        'TemplateCode' => '', // 短信模板 code
        'TemplateParam' => [], // [可选]设置模板参数, 假如模板中存在变量需要替换则为必填项
        'OutId' => '', // 发送短信流水号
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        if (empty($this->config['accessKeyId'])) {
            throw new \Exception('必须指定 accessKeyId');
        }
        if (empty($this->config['accessKeySecret'])) {
            throw new \Exception('必须指定 accessKeySecret');
        }
        $this->params['SignName'] = $config['signName'];
        if (empty($this->params['SignName'])) {
            throw new \Exception('必须填写短信签名');
        }
    }

    public function addPhoneNumber(string $phoneNumber): static
    {
        $this->params['PhoneNumbers'] = $phoneNumber;
        return $this;
    }

    public function addTemplateCode(string $templateCode): static
    {
        $this->params['TemplateCode'] = $templateCode;
        return $this;
    }

    /**
     * 添加模板参数，比如上面的验证码，则必须设置 ['code'=>'验证码']
     * @param array $params
     */
    public function addTemplateParams(array $params): static
    {
        $this->params['TemplateParam'] = $params;
        return $this;
    }

    /**
     * @param array $params 设置发送参数
     * @param bool $emptyTemplateParams 是否允许空的模板参数
     * @return array
     * @throws \Exception
     */
    public function send(array $params = [], bool $emptyTemplateParams = false)
    {
        $this->params = array_merge($this->params, $params);
//        dd($this->config,$this->params);
        if (empty($this->params['PhoneNumbers'])) {
            throw new \Exception('必须填写短信接收号码');
        }

        if (empty($this->params['TemplateCode'])) {
            throw new \Exception('必须填写短信模板');
        }
        if (is_array($this->params['TemplateParam'])) {
            if (!empty($this->params['TemplateParam'])) {
                $this->params['TemplateParam'] = json_encode($this->params['TemplateParam'], JSON_UNESCAPED_UNICODE);
            } else {
                $this->params['TemplateParam'] = '';
            }
        }
        // 验证码模板需要设置 ['code'=>'xxx']
        if (empty($this->params['TemplateParam']) && !$emptyTemplateParams) {
            throw new \Exception('请检查短信模板变量是否设置');
        }

        $helper = new SignatureHelper();
        $content = $helper->request(
            $this->config['accessKeyId'],
            $this->config['accessKeySecret'],
            "dysmsapi.aliyuncs.com",
            array_merge($this->params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            )),
            $this->config['security'],
        );
        return (array)$content;
    }

    /**
     * 检查发送结果是否成功 <pre>
     * 错误示例：array('Message'=>'请检查模板内容与模板参数是否匹配',
     * 'RequestId'=>'27FCCAFF-571A-51EC-8B8B-B6762B44BDDC','Code'=>'isv.SMS_TEMPLATE_ILLEGAL')
     * 正确示例: array('Message'=>'OK','BizId'=>'835601597893877383^0',
     * 'RequestId'=>'B1ED8627-50C5-55F8-97FB-4B253E7177E5','Code'=> 'OK')
     * </pre>
     * @param array $response
     * @return bool
     */
    public function isSendSuccess($response): bool
    {
        return isset($response['Code']) && $response['Code'] == 'OK' || isset($response['BizId']);
    }

    public function engine(): string
    {
        return 'ali_sms';
    }
}