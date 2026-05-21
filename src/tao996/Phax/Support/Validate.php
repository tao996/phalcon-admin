<?php

namespace Phax\Support;

use Phax\Helper\MyMvc;

/**
 * 验证器
 */
class Validate
{
    public function __construct(public MyMvc $mvc)
    {
    }

    /**
     * 对验证规则进行拆分 (可查看测试)
     * @param array $rules ['name|用户名' => 'require|min:20|max:20|or:1,2']
     * @return array [0 => ['title' => '用户名','rules' => [['require', []],['min', [20]],['max', [20]],['or', [1, 2]]]]]
     * @throws \Exception
     */
    public function rules(array $rules): array
    {
        if (empty($rules)) {
            throw new \Exception('validate rules must not empty in Validate.check');
        }
        $rows = [];
        foreach ($rules as $fieldInfo => $ruleItems) {
            $rowRules = [];
            foreach (explode('|', $ruleItems) as $rule) { // require|min:20|max:20 ==> ['require', 'min:20', 'max:10']
                $ruleWithParams = explode(':', $rule);
                if (count($ruleWithParams) > 1) {
                    $pos = strpos($rule, ':');
                    $rowRules[] = [substr($rule, 0, $pos), explode(',', substr($rule, $pos + 1))];
                } else {
                    $rowRules[] = [$ruleWithParams[0], []];
                }
            }
            $fInfo = explode('|', $fieldInfo); // 'name|用户名' ==> ['name', '用户名']
            $rows[] = [
                'name' => $fInfo[0],
                'title' => $fInfo[1] ?? '',
                'rules' => $rowRules,
            ];
        }
        return $rows;
    }

    /**
     * 分析规则及其参数 （可查看测试），数据来自 $this->rules 中的 rules 的每一项
     * @param string $rule 规则名称
     * @param array $params 规则所在参数
     * @return array|string[] [ 0=>i18n的key, 1=>验证的类, 2?=>参数, 'with'?=>数据来自其它]
     * @throws \Exception
     */
    public function getCallerValidation(string $rule, array $params): array
    {
        switch (strtolower($rule)) { // 全部都是小写
            case 'accepted': // yes, on, 1 通常用在服务条款
            case 'accept':
                return ['accepted', Validation\AcceptedValidation::class];
            case 'after':
                return [
                    'after',
                    Validation\AfterValidation::class,
                    ['date' => $params[0]]
                ];
            case 'alnum': // 字母数字字符
            case 'alphanum':
                return ['alnum', \Phalcon\Filter\Validation\Validator\Alnum::class];
            case 'alpha': // 纯字母
                return ['alpha', \Phalcon\Filter\Validation\Validator\Alpha::class];
            case 'before':
                return [
                    'before',
                    Validation\BeforeValidation::class,
                    ['date' => $params[0]],
                ];

            case 'between': // between:1,10
                return [
                    'between',
                    \Phalcon\Filter\Validation\Validator\Between::class,
                    array_combine(['minimum', 'maximum'], $params)
                ];
            case 'boolean':
            case 'bool':
                return ['bool', Validation\BoolValidation::class];

            case 'cnphone':// 中国大陆手机号码
            case 'cnmobile':
                return ['cnPhone', Validation\MobileCnValidation::class];
            case 'phone':
            case 'mobile':
                return ['phone', Validation\PhoneValidation::class];

            case 'confirm': // 'repassword'=>'confirm:password' 等于指定的字段的值
            case '=':
                return [
                    'confirm',
                    \Phalcon\Filter\Validation\Validator\Confirmation::class,
                    array_combine(['with'], $params),
                ];
            case 'creditcard':
                return ['creditCard', \Phalcon\Filter\Validation\Validator\CreditCard::class];
            case 'date':
                return [
                    'date',
                    \Phalcon\Filter\Validation\Validator\Date::class,
                    array_combine(['format'], $params ?: ['Y-m-d']),
                ];
            case 'different': // 不等于指定字段的值 'name'=>'require|different:account'
            case 'neq':
                return [
                    'different',
                    Validation\DifferentValidation::class,
                    'with' => $params[0]
                ];
            case 'digit': // 纯数字（不包含负数和小数），height, width
            case 'number':
                return ['digit', \Phalcon\Filter\Validation\Validator\Digit::class];
            case 'email':
                return ['email', \Phalcon\Filter\Validation\Validator\Email::class];
            case 'equal':
            case 'identical': // 等于指定的值
            case 'eq':
                return [
                    'equal',
                    \Phalcon\Filter\Validation\Validator\Identical::class,
                    array_combine(['accepted'], $params ?: [true])
                ];
            case 'expire': // 验证当前操作（注意不是某个值）是否在某个有效日期之内
                return [
                    'expire',
                    Validation\ExpireValidation::class,
                    array_combine(['min', 'max'], $params)
                ];

            case 'filemine': // 文件类型 mine:image/jpeg,image/png
            case 'fm':
                return [
                    'file.mine',
                    \Phalcon\Filter\Validation\Validator\File\MimeType::class,
                    [
                        'types' => $params ?: [
                            'image/jpg',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/bmp'
                        ]
                    ]
                ];
            case 'fileresolution':// 文件尺寸 fr:800x600
            case 'fr':
                return [
                    'file.resolution',
                    \Phalcon\Filter\Validation\Validator\File\Resolution\Equal::class,
                    array_combine(['resolution'], $params)
                ];
            case 'filemaxresolution':// 文件最大尺寸 frmax:800x600
            case 'frmax':
                return [
                    'file.maxResolution',
                    \Phalcon\Filter\Validation\Validator\File\Resolution\Max::class,
                    ['resolution' => $params[0], 'included' => true,],
                ];
            case 'fileminresolution':// 文件最小尺寸 frmin:800x600
            case 'frmin':
                return [
                    'file.minResolution',
                    \Phalcon\Filter\Validation\Validator\File\Resolution\Min::class,
                    ['resolution' => $params[0], 'included' => true,]
                ];
            case 'filesize': // 文件大小 fsize:2M
            case 'fs':
                return [
                    'file.size',
                    \Phalcon\Filter\Validation\Validator\File\Size\Equal::class,
                    ['size' => $params[0], 'included' => true]
                ];
            case 'filemaxsize':
            case 'fsmax':
                return [
                    'file.maxSize',
                    \Phalcon\Filter\Validation\Validator\File\Size\Max::class,
                    ['size' => $params[0], 'included' => true]
                ];
            case 'fileminsize':
            case 'fsmin':
                return [
                    'file.minSize',
                    \Phalcon\Filter\Validation\Validator\File\Size\Min::class,
                    ['size' => $params[0], 'included' => true]
                ];
            case 'float': // 数字字符串（正负小数）,常用于 price, amount
            case 'double':
            case 'price':
                return ['float', \Phalcon\Filter\Validation\Validator\Numericality::class];
            case 'idcard':
            case 'card':
                return ['idCard', Validation\IdCardValidation::class];
            case 'in':
            case 'inclusionin':
                return [
                    'in',
                    \Phalcon\Filter\Validation\Validator\InclusionIn::class,
                    ['domain' => $params]
                ];
            case 'integer':
            case 'int':
                return ['int', Validation\IntValidation::class];
            case 'id':
                return ['id', Validation\IdValidation::class];
            case 'ip':
                return [
                    'ip',
                    \Phalcon\Filter\Validation\Validator\Ip::class,
                    ['version' => \Phalcon\Filter\Validation\Validator\IP::VERSION_4 | \Phalcon\Filter\Validation\Validator\Ip::VERSION_6]
                ];
            case 'mac':// mac 地址
                return ['mac', Validation\MacValidation::class];


            case 'notbetween':
                return [
                    'notBetween',
                    Validation\NotBetweenValidation::class,
                    array_combine(['min', 'max'], $params)
                ];

            case 'notin':
            case 'exclusionin':
                return [
                    'notin',
                    \Phalcon\Filter\Validation\Validator\ExclusionIn::class,
                    ['domain' => $params]
                ];


            case 'regex': // regex:/\+1 [0-9]+/
                return [
                    'regex',
                    \Phalcon\Filter\Validation\Validator\Regex::class,
                    array_combine(['pattern'], $params)
                ];

            case 'require':
            case 'required':
                return ['require', \Phalcon\Filter\Validation\Validator\PresenceOf::class];

            case 'strlen':
            case 'len':
                return [
                    'strlen',
                    \Phalcon\Filter\Validation\Validator\StringLength::class,
                    array_combine(['min', 'max'], $params) + ['includedMaximum' => true, 'includedMinimum' => true]
                ];
            case 'strlenmax':
            case 'slmax':
                return [
                    'strlenMax',
                    \Phalcon\Filter\Validation\Validator\StringLength\Max::class,
                    ['max' => $params[0], 'included' => true]
                ];
            case 'strlenmin':
            case 'slmin':
                return [
                    'strlenMin',
                    \Phalcon\Filter\Validation\Validator\StringLength\Min::class,
                    ['min' => $params[0], 'included' => true]
                ];
            case 'uniqueness':
            case 'unique': // 模型唯一 Models\Customers
                // https://docs.phalcon.io/5.0/en/filter-validation#uniqueness
                return [
                    'unique',
                    \Phalcon\Filter\Validation\Validator\Uniqueness::class,
                    ['model' => new $params[0], 'attribute' => $params[1]]
                ];
            case 'url':
                return ['url', \Phalcon\Filter\Validation\Validator\Url::class];


            case 'zip': // 6 位数的邮政编号
                return ['zip', Validation\ZipValidation::class];


            default:
                throw new \Exception('un support validation rule of ' . $rule);
        }
    }

    /**
     * 验证不通过则抛出异常
     * @param array $data 待检查的数据
     * @param array $rules 验证规则，示例 ['name|用户名'=>'required|min:2|max:10']
     * <pre>
     * require/required                 必须填写
     * email                            电子邮件
     * alnum/alphanum                   字母数字字符
     * alpha                            字母
     * between:min,max                  在 [min, max] 之间
     * notbetween:min, max              不在 [min, max] 之间
     * boolean/bool                     布尔值
     * confirm/=                        等于指定的字段的值，示例 =:password
     * identical/equal/eq               等于指定值，示例 eq:agree
     * different/neq                    不等于指定的值，示例 neq:agree
     * creditCard                       信用卡
     * date:Y-m-d                       指定格式的日期
     * digit/number                     纯数字（不包含负数和小数），height, width
     * float/double/price               数字字符串（正负小数）,常用于 price, amount
     * int/integer                      整数
     * id                               大于 0 的整数，通常用于主键
     * in/inclusionin                   在指定的值中，示例 in:a,b,c,d
     * notin/exclusionin                不在指定的值中，示例 notin:a,b,c,d
     * mime/image/img                   文件类型，示例 mine:image/jpeg,image/png
     * fr/resolution                    文件尺寸 fr:800x600
     * frmax/resolutionmax              文件最大尺寸 frmax:800x600
     * frmin/resolutionmin              文件最小尺寸 frmin:800x600
     * fs/fsize                         文件大小 fsize:2M
     * fsmax
     * fsmin
     * ip                               IP 地址，支持 IP4, IP6
     * regex                            正则表达式 regex:/\+1 [0-9]+/
     * len/strlen                       字符串长度，示例 len:0,20
     * lenmax/strlenmax/max             字符串最大长度，示例 lenmax:20
     * lenmin/strlenmin/min             字符串最小长度，示例 lenmin:0
     * unique/uniqueness                模型唯一，示例 unique:__CLASS__ 或者 unique:__CLASS__,attr
     * url                              URL 地址，默认为 url:query，可指定为 url:path
     * cnmobile|cnphone                 中国大陆手机号
     * idcard|card                      中国大陆身份证号
     * zip                              中国大陆邮政编号
     * mac|macaddr                      MAC 地址
     * after                            在指定时间之后，支持 strtotime 参数，如 after:20231005
     * before                           在指定时间之前
     * expire                           验证当前操作（注意不是某个值）是否在某个有效日期之内，示例 expire:20230101,20231231
     * </pre>
     * @param array $messages 验证消息, 示例 ['name.require'=>'姓名不能为空', 'name.max'=>'姓名不得超过20位']
     * @throws \Exception
     */
    public function check(array $data, array $rules = [], array $messages = []): void
    {
        if ($rst = $this->getCheckMessages($data, $rules, $messages)) {
            throw new \Exception(join("<br/>", $rst));
        }
    }

    /**
     * 返回错误验证信息
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return array|null
     * @throws \Exception
     */
    public function getCheckMessages(array $data, array $rules = [], array $messages = []): ?array
    {
        $v = new \Phalcon\Filter\Validation();
        foreach ($this->rules($rules) as $item) {
            foreach ($item['rules'] as $row) {
                $rr = $this->getCallerValidation($row[0], $row[1]);
                $arguments = [];
                if (isset($rr['with'])) {
                    $arguments['with'] = $data[$rr['with']];
                } elseif (isset($rr[2])) {
                    $arguments = $rr[2];
                }
                $message = $messages[$item['name'] . '.' . $row[0]] ?? $this->mvc->__($rr[0]);
                if ($item['title']) {
//                    $arguments['field'] = $item['title'];
                    $message = str_replace(':field', $item['title'], $message);
                }
                $arguments['message'] = $message;
                $v->add($item['name'], new $rr[1] ($arguments));
            }
        }
        return $this->getMessages($v->validate($data));
    }

    public function getMessages(\Phalcon\Messages\Messages $messages): ?array
    {
        $rows = [];
        foreach ($messages as $m) {
            $rows[] = $m->getMessage();
        }
        return $rows ?: null;
    }

    public function isPhone(string $phone): bool
    {
        if (!empty($phone)) {
            return Validation\MobileCnValidation::match($phone);
        }
        return false;
    }

    /**
     * @throws \Exception
     */
    public function mustPhone(string $phone): void
    {
        if (!$this->isPhone($phone)) {
            throw new \Exception($this->mvc->__('cnPhone', ['field' => '']));
        }
    }

    public function isEmail(string $email): bool
    {
        if (!empty($email)) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }
        return false;
    }

    /**
     * @throws \Exception
     */
    public function mustEmail(string $email): void
    {
        if (!$this->isEmail($email)) {
            throw new \Exception($this->mvc->__('email', ['field' => '']));
        }
    }


    private function hosts(): array
    {
        static $hosts = null;
        if (is_null($hosts)) {
            $hosts = $this->mvc->config()->path('app.hosts')->toArray();
        }
        return $hosts;
    }

    public function hostValidate(string $url): void
    {
        if (empty($url)) {
            return;
        }
        $hosts = self::hosts();
        $host = parse_url($url, PHP_URL_HOST);
        if (!empty($hosts) && !in_array($host, $hosts)) {
            throw new \Exception('host not allow :' . $host);
        }
    }

    public function hostsValidate(array $urls): void
    {
        if (empty($urls)) {
            return;
        }
        $hosts = self::hosts();
        $hasHosts = !empty($hosts);
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($hasHosts && !in_array($host, $hosts)) {
                throw new \Exception('host not allow :' . $host);
            }
        }
    }
}