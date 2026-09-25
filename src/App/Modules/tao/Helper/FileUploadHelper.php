<?php

namespace App\Modules\tao\Helper;


use App\Modules\tao\Models\SystemUploadfile;
use App\Modules\tao\sdk\aliyun\oss\AliyunDriver;
use App\Modules\tao\sdk\OssDriverInterface;
use App\Modules\tao\sdk\qiniu\QiniuDriver;
use App\Modules\tao\sdk\tencent\cos\QcloudDriver;
use App\Modules\tao\Services\ConfigService;
use OSS\Core\OssException;
use Phalcon\Http\Request\File;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyFormat;


/**
 * 上传组件
 */
class FileUploadHelper
{
    private array $_config;
    private File $_file;
    private array $_options = [
        'hash' => true, // 使用文件 hash 命名
    ];

    /**
     * @param array $config 配置信息 会合并 ConfigService::uploadConfig()
     */
    public function __construct(array $config = [])
    {
        $this->_config = array_merge_deep(ConfigService::uploadConfig(), $config);
    }

    /**
     * 對上傳來源進行校驗
     * @return $this
     */
    public function fromRequest(): static
    {
        if (!AppService::request()->isPost()) {
            throw new BusinessException('非法请求');
        }
        if (!AppService::request()->hasFiles()) {
            throw new BusinessException('必须指定上传文件');
        }
        return $this;
    }

    private function mustGetFile(File $file = null)
    {
        if (is_null($file)) {
            if (empty($this->_file)) {
                $this->_file = AppService::request()->getUploadedFiles()[0];
            }
        } else {
            $this->_file = $file;
        }
        if (empty($this->_file)) {
            throw new BusinessException('必须指定上传文件');
        }
        return $this->_file;
    }

    public function getUploadType(): string
    {
        return $this->_config['driver'] ?? $this->_config['upload_type'];
    }

    public function getFile(): File
    {
        return $this->_file;
    }


    /**
     * 验证图片
     * @param File|null $file
     * @return FileUploadHelper
     * @throws \Exception
     */
    public function validate(File $file = null): self
    {
        $file = $this->mustGetFile($file);
        if (!in_array($file->getExtension(), explode(',', $this->_config['upload_allow_ext']))) {
//            dd('checkType',$file->getType(),$this->_config['upload_allow_mime']);
            throw new BusinessException('不允许上传的指定文件类型', [
                'allow' => $this->_config['upload_allow_ext'],
                'upload' => $file->getExtension(),
            ]);
        }

        $bitSize = $this->getMaxBytes();
        if ($file->getSize() > $bitSize) {
            throw new BusinessException('文件超过了' . MyFormat::humanFileSize($bitSize));
        }
        return $this;
    }

    private function getMaxBytes(): int
    {
        // 单位是 m
        $size = intval($this->_config['upload_allow_size']);
        return ($size > 0 ? $size : 5) * 1048576; // 1024 * 1024
    }

    /**
     * 将文件保存到本地
     * @return SystemUploadfile
     * @throws \Exception
     */
    private function moveToLocal(): SystemUploadfile
    {
        // 上传到当前项目目录
        $subDir = 'upload/' . AppService::context()->getProject('phax') . '/' . date('ymd') . '/';
        $pathUploadDir = AppService::helper()->dirSeparator(PATH_PUBLIC . $subDir);
        if (!file_exists($pathUploadDir)) {
            mkdir($pathUploadDir, 0777, true);
        }
        $sha1 = md5_file($this->_file->getTempName());
        $saveName = $this->_file->getName(); // 保存的文件名
        if ($this->_options['hash']) {
            $saveName = $sha1 . '.' . strtolower($this->_file->getExtension());
        }

        if ($this->_file->moveTo($pathUploadDir . $saveName)) {
            list($width, $height) = getimagesize($pathUploadDir . $saveName);
            $sf = new SystemUploadfile();
            $sf->assign([
                'upload_type' => $this->getUploadType(), // 文件类型
                'summary' => $this->_file->getName(), // 原始文件名
                'url' => '/' . $subDir . $saveName, // 本地访问链接地址(添加 config('app.origin') . 可能会导致移除数据库时无法访问）
                'width' => $width,
                'height' => $height, // 尺寸
                'mime_type' => $this->_file->getType(), // mime 类型
                'file_size' => $this->_file->getSize(), // 文件大小
                'file_ext' => $this->_file->getExtension(), // 文件扩展名
                'sha1' => $sha1, // 文件 hash 值
            ]);
            $sf->tmpSavePath = $pathUploadDir . $saveName;
            return $sf;
        }
        throw new BusinessException('保存本地文件错误', [
            'file' => $pathUploadDir . $saveName,
        ]);
    }


    /**
     * 将上传到本地的文件再次上传到云
     * @param OssDriverInterface $driver
     * @param string $dir 保存的目录
     * @param bool $rmLocal 是否移除本地文件
     * @return SystemUploadfile
     * @throws \Exception
     */
    private function ossUpload(OssDriverInterface $driver, string $dir, bool $rmLocal = true): SystemUploadfile
    {
        $f = $this->moveToLocal(); // 保存到本地
        $names = [$dir];
        $names[] = basename($f->url);
        $objectName = join('/', $names);
        $f->url = $driver->uploadFile($objectName, $f->tmpSavePath);
        if ($rmLocal) {
            if (file_exists($f->tmpSavePath)) {// 移除本地文件
                if (unlink($f->tmpSavePath)) {
                    $f->tmpSavePath = '';
                }
            }
        }
        return $f;
    }
    const string DRIVER_ALIOSS = 'alioss';
    const string DRIVER_QNOSS = 'qnoss';
    const string DRIVER_TXCOS = 'txcos';

    const string DRIVER_LOCAL = 'local';

    private function getOssDriver(string $driver, array $config): OssDriverInterface
    {
        switch ($driver) {
            case self::DRIVER_ALIOSS:
                return new AliyunDriver([
                    'alioss_access_key_id' => $config['alioss_access_key_id'],
                    'alioss_access_key_secret' => $config['alioss_access_key_secret'],
                    'alioss_endpoint' => $config['alioss_endpoint'],
                    'alioss_bucket' => $config['alioss_bucket'],
                    'alioss_domain' => $config['alioss_domain'],
                ]);
            case self::DRIVER_QNOSS: // 七牛云
                return new QiniuDriver([
                    'qnoss_access_key' => $config['qnoss_access_key'],
                    'qnoss_secret_key' => $config['qnoss_secret_key'],
                    'qnoss_bucket' => $config['qnoss_bucket'],
                    'qnoss_domain' => $config['qnoss_domain'],
                ]);
            case self::DRIVER_TXCOS: // 腾讯云
                return new QcloudDriver([
                    'txcos_secret_id' => $config['txcos_secret_id'],
                    'txcos_secret_key' => $config['txcos_secret_key'],
                    'txcos_region' => $config['txcos_region'],
                    'txcos_bucket' => $config['txcos_bucket'],
                    'schema' => 'https',
                ]);
            default:
                throw new BusinessException('不支持的云上传类型');
        }
    }

    /**
     * @throws OssException
     */
    public function save(File $file = null): SystemUploadfile
    {
        $this->mustGetFile($file);
        $uploadType = $this->getUploadType();
        if (empty($uploadType)) {
            throw new BusinessException('未指定上传存储方式');
        }
        switch ($uploadType) {
            case self::DRIVER_LOCAL:
                return $this->moveToLocal();
            default:
                $oss = $this->getOssDriver($uploadType, $this->_config);
                return $this->ossUpload($oss, $this->_config['oss_dir'] ?: AppService::context()->getProject('phax'));
        }
    }

    /**
     * 服务端签名直传：签发**限定前缀**的直传凭证，由客户端直传云存储（不过服务器带宽）。
     *
     * 安全四件套（调用方按需收紧）：
     *  - `$prefix` 限定 scope（`bucket:prefix` + `isPrefixalScope`）→ 只能写这个前缀下的对象，防越权写别人目录；
     *  - 短时效（默认 600s）；
     *  - `insertOnly` 默认 1 → 防覆盖（内容寻址 key（sha256）重复上传即同一对象）；
     *  - 通过 `$policy` 追加 `fsizeLimit` / `mimeLimit` 等 put policy 限制大小与类型。
     *
     * 直传完成后**必须**回调后端登记（校验归属 / size / sha256 后再落库），不要盲信客户端。
     *
     * @param string $prefix 限定前缀（scope），如 `worksheet/<uuid>/assets/`；空串 = 不限定
     * @param int $expire 有效期（秒）
     * @param array $policy 追加的七牛 put policy，如 `['fsizeLimit' => 50 * 1024 * 1024, 'mimeLimit' => 'image/*']`
     * @return array{driver:string,bucket:string,prefix:string,token:string,expire:int,domain:string,insert_only:bool}
     * @link https://developer.qiniu.com/kodo/manual/put-policy
     */
    public function serverToken(string $prefix = '', int $expire = 600, array $policy = []): array
    {
        $driver = $this->getUploadType();
        if ($driver === 'local') {
            throw new BusinessException('当前为本地存储，无需直传凭证');
        }
        if ($driver !== 'qnoss') {
            throw new BusinessException('暂只支持七牛云的直传凭证', ['driver' => $driver]);
        }
        /** @var QiniuDriver $oss */
        $oss = $this->getOssDriver($driver, $this->_config);
        $bucket = $oss->getBucket();
        $insertOnly = array_key_exists('insertOnly', $policy) ? (int)$policy['insertOnly'] : 1;
        $token = $oss->getAuth()->uploadToken(
            $prefix === '' ? $bucket : $bucket . ':' . $prefix,
            null,
            $expire,
            array_merge(
                ['insertOnly' => $insertOnly],
                $prefix === '' ? [] : ['isPrefixalScope' => 1],
                $policy
            )
        );
        return [
            'driver' => $driver,
            'bucket' => $bucket,
            'prefix' => $prefix,
            'token' => $token,
            'expire' => time() + $expire,
            'domain' => $oss->getDomain(),
            'insert_only' => $insertOnly === 1,
        ];
    }

}