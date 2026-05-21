<?php

namespace App\Modules\tao\Helper\Libs;

use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Encryption\Security\JWT\Exceptions\UnsupportedAlgorithmException;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use Phalcon\Encryption\Security\JWT\Signer\Hmac;
use Phalcon\Encryption\Security\JWT\Token\Enum;


/**
 * @link https://docs.phalcon.io/5.0/en/encryption-security-jwt
 */
class JwtLibHelper
{
    private Builder $builder;
    private Hmac $signer;

    /**
     * @throws UnsupportedAlgorithmException
     * @throws ValidatorException
     */
    public function __construct(public array $config)
    {
        if (empty($this->config['secret'])){
            throw new \Exception('jwt secret is empty');
        }
        if (empty($this->config['expire'])){
            throw new \Exception('jwt expire is empty');
        }
        if (empty($this->config['iss'])){
            throw new \Exception('jwt iss is empty');
        }

        $this->config = array_merge(['hmac' => 'sha512', 'subject' => 'jwt'], $this->config);


        $this->signer = new Hmac($this->config['hmac'] ?? 'sha512');
        $builder = new Builder($this->signer);

        $now = new \DateTimeImmutable();
        $issued = $now->getTimestamp();
        $notBefore = $now->modify('-1 minute')->getTimestamp();
        $expires = $this->config['expire'] ? (int)$this->config['expire'] + $issued : $now->modify(
            '+2 days'
        )->getTimestamp();
        $rand = md5($this->config['secret'] ?? filectime(__FILE__));
        // 必须是一个混合的字符串，否则会抛出 too weak
        $passphrase = substr($rand, 0, 9) . 'P&' . substr($rand, 10, 9) . 'H&' . substr($rand, 20, 10);
        $builder->setExpirationTime($expires) // exp 过期时间
        ->setIssuer($this->config['iss']) // iss 签发者
        ->setIssuedAt($issued) // iat 签发时间
        ->setNotBefore($notBefore) // nbf 生效时间点
        ->setSubject($this->config['subject'])
            ->setContentType('application/json')
            ->setPassphrase($passphrase);

        $this->builder = $builder;
    }

    /**
     *  jti 指明 JWT 唯一 ID，用于避免重放攻击
     * @param string $id
     * @return $this
     */
    public function setId(string $id): static
    {
        $this->builder->setId($id);
        return $this;
    }

    /**
     * 指定 aud 签收者（必须是一个字符串，否则 Invalid Audience）
     * @param string|array $audience
     * @return $this
     */
    public function setAudience(string|array $audience): static
    {
        $this->builder->setAudience($audience);
        return $this;
    }

    /**
     * 生成 token 字符串
     * @param array $claims
     * @return string
     */
    public function getToken(array $claims = []): string
    {
        foreach ($claims as $key => $value) {
            $this->builder->addClaim($key, $value);
        }
        return $this->builder->getToken()->getToken();
    }

    /**
     * @throws ValidatorException
     * @throws \Exception
     */
    public function parser($token): array
    {
        $parser = new \Phalcon\Encryption\Security\JWT\Token\Parser();
        $tokenObject = $parser->parse($token);
        $validator = new \Phalcon\Encryption\Security\JWT\Validator($tokenObject, 100);
        $validator
            ->set(Enum::EXPIRATION_TIME, time())
            ->set(Enum::ISSUER, $this->builder->getIssuer())
            ->set(Enum::ISSUED_AT, $this->builder->getIssuedAt())
            ->set(Enum::NOT_BEFORE, $this->builder->getNotBefore())
            ->set(Enum::AUDIENCE, $this->builder->getAudience())
            ->set(Enum::SUBJECT, $this->builder->getSubject());
        $validator->validateSignature($this->signer, $this->builder->getPassphrase());

        $tokenObject->validate($validator);
        if ($errors = $validator->getErrors()) {
            throw new \Exception($errors[0]);
        }

        return $tokenObject->getClaims()->getPayload();
    }

}