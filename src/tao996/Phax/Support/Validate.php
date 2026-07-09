<?php

namespace Phax\Support;

use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;

/**
 * 验证器
 */
class Validate
{
    public function __construct()
    {
    }

    // ============================================================
    // ============================================================

    /**
     * @var array<string, array> 链式调用中注册的字段和规则
     */
    private array $fluentFields = [];

    /**
     * @var string|null 当前正在定义规则的字段名
     */
    private ?string $fluentCurrent = null;

    // ============================================================
    //  链式 API — 入口
    // ============================================================

    /**
     * 设置要验证的字段值，开始链式定义规则
     * @param mixed $value 待验证的值
     * @param string $title 字段显示名称（用于错误消息）
     * @return $this
     */
    public function with(mixed $value, string $title = ''): static
    {
        $name = 'f' . count($this->fluentFields);
        $this->fluentCurrent = $name;
        $this->fluentFields[$name] = [
            'name' => $name,
            'title' => $title,
            'value' => $value,
            'rules' => [],
        ];
        return $this;
    }

    /**
     * 执行链式定义的所有验证规则
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        if (empty($this->fluentFields)) {
            return new ValidationResult();
        }

        // 构建旧格式的 rules 和 data
        $rules = [];
        $data = [];
        foreach ($this->fluentFields as $field) {
            $data[$field['name']] = $field['value'];
            $ruleParts = [];
            foreach ($field['rules'] as [$rule, $params]) {
                if (empty($params)) {
                    $ruleParts[] = $rule;
                } else {
                    $ruleParts[] = $rule . ':' . implode(',', $params);
                }
            }
            if (empty($ruleParts)) {
                continue; // 跳过没有规则的字段
            }
            $key = $field['name'];
            if ($field['title'] !== '') {
                $key .= '|' . $field['title'];
            }
            $rules[$key] = implode('|', $ruleParts);
        }

        if (empty($rules)) {
            return new ValidationResult();
        }

        $messages = $this->getCheckMessages($data, $rules);
        return new ValidationResult($messages);
    }

    // ============================================================
    //  链式 API — 规则方法
    // ============================================================

    private function addRule(string $rule, array $params): static
    {
        if ($this->fluentCurrent === null) {
            throw new BusinessException('必须先调用 with() 再添加验证规则');
        }
        $this->fluentFields[$this->fluentCurrent]['rules'][] = [$rule, $params];
        return $this;
    }

    /** 必填 */
    public function require(): static
    {
        return $this->addRule('require', []);
    }

    public function required(): static
    {
        return $this->addRule('require', []);
    }

    /** 字符串最小长度 */
    public function min(int|string $min): static
    {
        return $this->addRule('min', [(string)$min]);
    }

    /** 字符串最大长度 */
    public function max(int|string $max): static
    {
        return $this->addRule('max', [(string)$max]);
    }

    /** 字符串长度范围 */
    public function len(int|string $min, int|string $max): static
    {
        return $this->addRule('strlen', [(string)$min, (string)$max]);
    }

    /** 数值范围 */
    public function between(int|string $min, int|string $max): static
    {
        return $this->addRule('between', [(string)$min, (string)$max]);
    }

    /** 不在数值范围内 */
    public function notBetween(int|string $min, int|string $max): static
    {
        return $this->addRule('notbetween', [(string)$min, (string)$max]);
    }

    /** 值必须在列表中 */
    public function in(array $values): static
    {
        return $this->addRule('in', $values);
    }

    /** 值不能在列表中 */
    public function notIn(array $values): static
    {
        return $this->addRule('notin', $values);
    }

    /** 电子邮件格式 */
    public function email(): static
    {
        return $this->addRule('email', []);
    }

    /** URL 格式 */
    public function url(): static
    {
        return $this->addRule('url', []);
    }

    /** 纯字母 */
    public function alpha(): static
    {
        return $this->addRule('alpha', []);
    }

    /** 字母数字 */
    public function alnum(): static
    {
        return $this->addRule('alnum', []);
    }

    /** 纯数字字符串 */
    public function digit(): static
    {
        return $this->addRule('digit', []);
    }

    /** 整数 */
    public function int(): static
    {
        return $this->addRule('int', []);
    }

    public function integer(): static
    {
        return $this->addRule('int', []);
    }

    /** 浮点数 */
    public function float(): static
    {
        return $this->addRule('float', []);
    }

    /** 布尔值 */
    public function bool(): static
    {
        return $this->addRule('bool', []);
    }

    public function boolean(): static
    {
        return $this->addRule('bool', []);
    }

    /** 日期格式 */
    public function date(string $format = 'Y-m-d'): static
    {
        return $this->addRule('date', [$format]);
    }

    /** 正则匹配 */
    public function regex(string $pattern): static
    {
        return $this->addRule('regex', [$pattern]);
    }

    /** 等于另一字段的值 */
    public function confirm(string $field): static
    {
        return $this->addRule('confirm', [$field]);
    }

    /** 不等于另一字段的值 */
    public function different(string $field): static
    {
        return $this->addRule('different', [$field]);
    }

    /** 等于指定值 */
    public function equal(string $value): static
    {
        return $this->addRule('equal', [$value]);
    }

    /** 接受（yes/on/1） */
    public function accepted(): static
    {
        return $this->addRule('accepted', []);
    }

    /** 手机号码（通用） */
    public function phone(): static
    {
        return $this->addRule('phone', []);
    }

    /** 中国大陆手机号码 */
    public function cnPhone(): static
    {
        return $this->addRule('cnphone', []);
    }

    /** IP 地址 */
    public function ip(): static
    {
        return $this->addRule('ip', []);
    }

    /** 在指定日期之后 */
    public function after(string $date): static
    {
        return $this->addRule('after', [$date]);
    }

    /** 在指定日期之前 */
    public function before(string $date): static
    {
        return $this->addRule('before', [$date]);
    }

    /**
     * 通用规则（当没有专用方法时使用）
     * @param string $name 规则名称（与 getCallerValidation 中的名称一致）
     * @param mixed ...$params 规则参数
     * @return $this
     */
    public function rule(string $name, ...$params): static
    {
        return $this->addRule($name, $params);
    }

    /**
     * 对验证规则进行拆分 (可查看测试)
     * @param array $rules ['name|用户名' => 'require|min:20|max:20|or:1,2']
     * @return array [0 => ['title' => '用户名','rules' => [['require', []],['min', [20]],['max', [20]],['or', [1, 2]]]]]
     */
    public function rules(array $rules): array
    {
        if (empty($rules)) {
            throw new BusinessException('validate rules must not empty in Validate.check');
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
            $fInfo = explode('|', $fieldInfo, 2); // 'name|用户名' ==> ['name', '用户名']
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
                    count($params) >= 2 ? array_combine(['minimum', 'maximum'], [$params[0], $params[1]]) : []
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
                    ['with' => $params[0] ?? '']
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
                    count($params) >= 2 ? array_combine(['min', 'max'], [$params[0], $params[1]]) : []
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
                    (count($params) >= 2 ? array_combine(['min', 'max'], [$params[0], $params[1]]) : []) + ['includedMaximum' => true, 'includedMinimum' => true]
                ];
            case 'max':
            case 'strlenmax':
            case 'slmax':
                return [
                    'strlenMax',
                    \Phalcon\Filter\Validation\Validator\StringLength\Max::class,
                    ['max' => $params[0], 'included' => true]
                ];
            case 'min':
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
                $uniqueArgs = ['model' => new $params[0]];
                if (isset($params[1])) {
                    $uniqueArgs['attribute'] = $params[1];
                }
                return [
                    'unique',
                    \Phalcon\Filter\Validation\Validator\Uniqueness::class,
                    $uniqueArgs
                ];
            case 'url':
                return ['url', \Phalcon\Filter\Validation\Validator\Url::class];


            case 'zip': // 6 位数的邮政编号
                return ['zip', Validation\ZipValidation::class];


            default:
                throw new BusinessException('不支持的验证规则' . $rule);
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
     */
    public function check(array $data, array $rules = [], array $messages = []): void
    {
        if ($rst = $this->getCheckMessages($data, $rules, $messages)) {
            throw new BusinessException(join("<br/>", $rst));
        }
    }

    public static function checkData(array $data, array $rules = [], array $messages = []): void
    {
        $obj = new Validate();
        $obj->check($data, $rules, $messages);
    }

    /**
     * 返回错误验证信息
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return array|null
     */
    public function getCheckMessages(array $data, array $rules = [], array $messages = []): ?array
    {
        $v = new \Phalcon\Filter\Validation();
        foreach ($this->rules($rules) as $item) {
            foreach ($item['rules'] as $row) {
                $rr = $this->getCallerValidation($row[0], $row[1]);
                $arguments = $rr[2] ?? [];
                // 使用显式 __() 调用，使翻译 key 可被静态提取工具（如 xgettext）发现
                $message = $messages[$item['name'] . '.' . $row[0]]
                    ?? match ($rr[0]) {
                        'accepted' => __('validate.accepted', ':field 的值必须为 yes,on 或者 1'),
                        'after' => __('validate.after', ':field 不能晚于 :date'),
                        'alnum' => __('validate.alnum', ':field 只能包含字母数字'),
                        'alpha' => __('validate.alpha', ':field 只能包含字母'),
                        'before' => __('validate.before', ':field 不能早于 :date'),
                        'between' => __('validate.between', ':field 值必须在 :min 和 :max 之间'),
                        'bool' => __('validate.bool', ':field 值只能是 true 或者 false'),
                        'cnPhone' => __('validate.cnPhone', ':field 不是一个有效的 +86 手机号码'),
                        'phone' => __('validate.phone', ':field 不是一个有效的手机号码'),
                        'confirm' => __('validate.confirm', ':field 值不匹配'),
                        'creditCard' => __('validate.creditCard', '不是一个有效的信用卡'),
                        'date' => __('validate.date', ':field 不是一个有效的日期'),
                        'different' => __('validate.different', ':field 值不能等于 :with'),
                        'digit' => __('validate.digit', ':field 必须是一个数字'),
                        'email' => __('validate.email', ':field 不是一个有效的电子邮箱地址'),
                        'equal' => __('validate.equal', ':field 必须等于指定的值 :accepted'),
                        'expire' => __('validate.expire', ':field 必须在指定的时间范围内 :min ~ :max'),
                        'file.mine' => __('validate.file.mine', '文件类型错误 :types'),
                        'file.resolution' => __('validate.file.resolution', '文件尺寸必须为 :resolution'),
                        'file.maxResolution' => __('validate.file.maxResolution', '文件最大尺寸为 :resolution'),
                        'file.minResolution' => __('validate.file.minResolution', '文件最小尺寸为 :resolution'),
                        'file.size' => __('validate.file.size', '文件大小必须为 :size'),
                        'file.maxSize' => __('validate.file.maxSize', '文件大小最多为 :size'),
                        'file.minSize' => __('validate.file.minSize', '文件大小最少为 :size'),
                        'float' => __('validate.float', ':field 必须是一个浮点/整数数字'),
                        'idCard' => __('validate.idCard', ':field 不是一个有效的中国身份证号'),
                        'in' => __('validate.in', ':field 值必须在指定范围内 :domain'),
                        'int' => __('validate.int', ':field 必须是一个整数'),
                        'id' => __('validate.id', ':field 必须是一个整数'),
                        'ip' => __('validate.ip', ':field 不是一个有效的 IP 地址'),
                        'mac' => __('validate.mac', ':field 不是一个有效的 Mac 地址'),
                        'notBetween' => __('validate.notBetween', ':field 值不能在 :min 和 :max 之间'),
                        'notin' => __('validate.notin', ':field 值不能在指定范围内 :domain'),
                        'regex' => __('validate.regex', ':field 与指定的正则表达式 :pattern 不匹配'),
                        'require' => __('validate.require', ':field 值不能为空'),
                        'strlen' => __('validate.strlen', ':field 字符数必须在 :min 到 :max 之间'),
                        'strlenMax' => __('validate.strlenMax', ':field 最多 :max 个字符'),
                        'strlenMin' => __('validate.strlenMin', ':field 至少 :min 个字符'),
                        'unique' => __('validate.unique', ':field 的值已被占用'),
                        'url' => __('validate.url', ':field 不是一个有效的 url 地址'),
                        'zip' => __('validate.zip', ':field 不是一个有效的邮政编码'),
                        default => __('validate.' . $rr[0], ':field 校验未通过'),
                    };
                if ($item['title']) {
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

    public static function isPhone(string $phone): bool
    {
        if (!empty($phone)) {
            return Validation\MobileCnValidation::match($phone);
        }
        return false;
    }

    public static function mustPhone(string $phone): void
    {
        if (!self::isPhone($phone)) {
            throw new BusinessException(__('validate.cnPhone', ':field 不是一个有效的 +86 手机号码', ['field' => $phone]));
        }
    }

    public static function isEmail(string $email): bool
    {
        if (!empty($email)) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }
        return false;
    }


    public static function mustEmail(string $email): void
    {
        if (!self::isEmail($email)) {
            throw new BusinessException(__('validate.email', ':field 不是一个有效的电子邮箱地址', ['field' => $email]));
        }
    }


    private static function hosts(): array
    {
        static $hosts = null;
        if (is_null($hosts)) {
            $hosts = AppService::config()->getArray('app.hosts');
        }
        return $hosts;
    }

    public static function hostValidate(string $url): void
    {
        if (empty($url)) {
            return;
        }
        $hosts = self::hosts();
        $host = parse_url($url, PHP_URL_HOST);
        if (!empty($hosts) && !in_array($host, $hosts)) {
            throw new BusinessException(__('validate.host', '不允许的域名 :host', ['host' => $host]));
        }
    }

    public static function hostsValidate(array $urls): void
    {
        if (empty($urls)) {
            return;
        }
        $hosts = self::hosts();
        $hasHosts = !empty($hosts);
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($hasHosts && !in_array($host, $hosts)) {
                throw new BusinessException(__('validate.host', '不允许的域名 :host', ['host' => $host]));
            }
        }
    }
}