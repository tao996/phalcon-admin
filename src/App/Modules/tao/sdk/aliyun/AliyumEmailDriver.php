<?php

namespace App\Modules\tao\sdk\aliyun;

use App\Modules\tao\sdk\EmailDriverInterface;
use App\Modules\tao\sdk\SdkHelper;
use Dm\Request\V20151123 as Dm;

/**
 * @link https://help.aliyun.com/document_detail/29460.html
 * @link https://help.aliyun.com/document_detail/29444.html 接口说明
 */
class AliyumEmailDriver implements EmailDriverInterface
{
    private array $config = [
        'accessKeyId' => '', // your access key id
        'accessKeySecret' => '',// your access key secret
        'accountName' => '',// 发信地址
        'fromAlias' => '',// 发信人昵称
        'tagName' => '', // 控制台创建的标签
        "replyToAddress" => 'false', // 是否启用管理控制台中配置好回信地址（状态须验证通过），取值范围是字符串true或者false（不是bool值）
        'addressType' => 1, // 1 为发信地址，0为随机账号
    ];

    private \DefaultAcsClient $client;
    private Dm\SingleSendMailRequest $singleSendMailRequest;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        if (empty($this->config['accessKeyId'])) {
            throw new \Exception('必备填写 accessKeyId');
        } elseif (empty($this->config['accessKeySecret'])) {
            throw new \Exception('必须填写 accessKeySecret');
        } elseif (empty($this->config['accountName'])) {
            throw new \Exception('必须填写发信地址');
        } elseif (empty($this->config['fromAlias'])) {
            throw new \Exception('必须填写发信人昵称');
        }
        SdkHelper::aliyunCore();
// 需要设置对应的region名称，如华东1（杭州）设为cn-hangzhou，新加坡Region设为ap-southeast-1，澳洲Region设为ap-southeast-2。
// 参考文档：https://help.aliyun.com/document_detail/2361895.html
// 新加坡或澳洲region需要设置服务器地址，华东1（杭州）不需要设置。
// $iClientProfile::addEndpoint("ap-southeast-1","ap-southeast-1","Dm","dm.ap-southeast-1.aliyuncs.com");
// $iClientProfile::addEndpoint("ap-southeast-2","ap-southeast-2","Dm","dm.ap-southeast-2.aliyuncs.com");
        $iClientProfile = \DefaultProfile::getProfile(
            'cn-hangzhou', $this->config['accessKeyId'], $this->config['accessKeySecret']);
        $this->client = new \DefaultAcsClient($iClientProfile);
    }

    public function useSingleSendMailRequest(): static
    {

        $request = new Dm\SingleSendMailRequest();
// 新加坡或澳洲region需要设置SDK的版本，华东1（杭州）不需要设置。
// $request->setVersion("2017-06-22");
        $request->setAccountName($this->config['accountName']);
        $request->setFromAlias($this->config['fromAlias']);
        $request->setReplyToAddress($this->config['replyToAddress']);
        if ($this->config['tagName']) {
            $request->setTagName($this->config['tagName']);
        }
        $request->setAddressType($this->config['addressType']);
        $this->singleSendMailRequest = $request;
        return $this;
    }

    /**
     * 设置邮件主题
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): static
    {
        $this->singleSendMailRequest->setSubject($subject);
        return $this;
    }

    public function setHtmlBody(string $html): static
    {
        $this->singleSendMailRequest->setHtmlBody($html);
        return $this;
    }

    /**
     * 可以给多个收件人发送邮件，收件人之间用逗号分开,若调用模板批量发信建议使用BatchSendMailRequest方式
     * @param string|array $address
     * @return $this
     * @throws \Exception
     */
    public function setAddress(string|array $address): static
    {
        if (empty($address)) {
            throw new \Exception('必须填写写件人地址');
        }
        $this->singleSendMailRequest->setToAddress(is_array($address) ? join(',', $address) : $address);
        return $this;
    }

    public function send(): array
    {
        $response = $this->client->getAcsResponse($this->singleSendMailRequest);
        return (array)$response;
    }

    /**
     * 是否发送邮件成功
     * @link https://help.aliyun.com/document_detail/29444.html
     * @param array $response
     * @return bool
     */
    public function isSendSuccess($response): bool
    {
        return isset($response['RequestId']);
    }

    public function useBatchSendMailRequest(): static
    {
        throw new \Exception('暂未实现批量发送功能');
        return $this;
    }

    public function engine(): string
    {
        return 'ali_email';
    }
}