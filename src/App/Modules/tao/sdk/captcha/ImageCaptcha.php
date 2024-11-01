<?php

namespace App\Modules\tao\sdk\captcha;

/**
 * 生成一个本地验证码
 * @link https://github.com/lifei6671/php-captcha/blob/master/src/CaptchaBuilder.php
 */
class ImageCaptcha
{
    /**
     * @var resource 图片
     */
    private $image;
    /**
     * @var string 随机字符
     */
    protected string $characters = '2346789abcdefghjkmnpqrstuvwxyABCDEFGHJKLMNPQRSTUVWXYZ';
    protected int $charactersLen = 53; // characters 的长度
    protected array $fonts = [
        'actionj',
        'ApothecaryFont',
        'BigBlocko',
        'Bitsumishi',
        'D3Parallelism',
        'DeborahFancyDress',
        'Flim-Flam',
        'Pointy'
    ];

    private array $options = [
        'width' => 150,
        'height' => 50,
        'length' => 4,
        'fontSize' => 30,
        'text' => '', // 显示的文字
        'font' => '', // 字体
        'backColor' => '', // 背景颜色
        'drawLine' => true, // 添加干扰线
        'drawNoise' => true,// 背景噪音
        'noiseLevel' => 30,
        'drawCurve' => true,// 是否启用曲线
    ];

    public function __construct(array $options = [])
    {
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
        $this->options['noiseLevel'] = intval($this->options['width'] * 10 / $this->options['height']);
    }

    public function create(): static
    {
        $this->image = imagecreate($this->options['width'], $this->options['height']);
        list ($red, $green, $blue) = $this->getLightColor();
        $this->options['backColor'] = imagecolorallocate($this->image, $red, $green, $blue);

        imagefill($this->image, 0, 0, $this->options['backColor']);

        $this->options['font'] = __DIR__ . '/fonts/' . $this->fonts[array_rand($this->fonts)] . '.ttf';

        $this->options['drawNoise'] && $this->drawNoise();

        if ($this->options['drawLine']) {
            $square = $this->options['width'] * $this->options['height'];
            $effects = mt_rand(intval($square / 3000), intval($square / 2000));
            for ($e = 0; $e < $effects; $e++) {
                $this->drawLine($this->image, $this->options['width'], $this->options['height']);
            }
        }
        $this->options['drawCurve'] && $this->drawSineLine();

        $codeNx = 0; // 验证码第N个字符的左边距
        $code = []; // 保存验证码

        for ($i = 0; $i < $this->options['length']; $i++) {
            $code[$i] = $this->characters[mt_rand(0, $this->charactersLen - 1)];
            $codeNx += mt_rand(
                $this->options['fontSize'] * 1 - mt_rand(0,15),
                intval($this->options['fontSize'] * 1.1)
            );

            list($red, $green, $blue) = $this->getDeepColor();
            $color = imagecolorallocate($this->image, $red, $green, $blue);
            if ($color === false) {
                $color = mt_rand(50, 200);
            }
            imagettftext(
                $this->image,
                $this->options['fontSize'],
                mt_rand(-40, 40),
                $codeNx,
                intval($this->options['fontSize'] * 1.2),
                $color,
                $this->options['font'],
                $code[$i]
            );
        }

        $this->options['text'] = strtolower(implode('', $code));
        return $this;
    }

    public function output($quality = 1): void
    {
        header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header("content-type: image/png");
        imagepng($this->image, null, $quality);
    }

    public function save($filename, $quality): bool
    {
        return imagepng($this->image, $filename, $quality);
    }

    public function getText()
    {
        return $this->options['text'];
    }

    public function destroy(): void
    {
        @imagedestroy($this->image);
    }

    public function __destruct()
    {
        $this->destroy();
    }

    private function getFontColor(): false|int
    {
        list($red, $green, $blue) = $this->getDeepColor();

        return imagecolorallocate($this->image, $red, $green, $blue);
    }

    /**
     *  画曲线
     */
    protected function drawSineLine(): void
    {
        $px = $py = 0;

        // 曲线前部分
        $A = mt_rand(1, intval($this->options['height'] / 2));                  // 振幅
        $b = mt_rand(intval(-$this->options['height'] / 4), intval($this->options['height'] / 4));   // Y轴方向偏移量
        $f = mt_rand(intval(-$this->options['height'] / 4), intval($this->options['height'] / 4));   // X轴方向偏移量
        $T = mt_rand($this->options['height'], $this->options['width'] * 2);  // 周期
        $w = (2 * M_PI) / $T;

        $px1 = 0;  // 曲线横坐标起始位置
        $px2 = mt_rand($this->options['width'] / 2, $this->options['width'] * 0.8);  // 曲线横坐标结束位置

        $color = imagecolorallocate($this->image, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $this->options['height'] / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($this->options['fontSize'] / 5);
                while ($i > 0) {
                    imagesetpixel($this->image, intval($px + $i), intval($py + $i), $color);
                    $i--;
                }
            }
        }

        // 曲线后部分
        $A = mt_rand(1, intval($this->options['height'] / 2));                  // 振幅
        $f = mt_rand(intval(-$this->options['height'] / 4), intval($this->options['height'] / 4));   // X轴方向偏移量
        $T = mt_rand($this->options['height'], $this->options['width'] * 2);  // 周期
        $w = (2 * M_PI) / $T;
        $b = $py - $A * sin($w * $px + $f) - $this->options['height'] / 2;
        $px1 = $px2;
        $px2 = $this->options['width'];

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $this->options['height'] / 2;  // y = Asin(ωx+φ) + b
                $i = (int)($this->options['fontSize'] / 5);
                while ($i > 0) {
                    imagesetpixel($this->image, intval($px + $i), intval($py + $i), $color);
                    $i--;
                }
            }
        }
    }

    /**
     * Draw lines over the image
     */
    protected function drawLine($image, $width, $height, $color = null): void
    {
        if ($color === null) {
            $color = imagecolorallocate($image, mt_rand(100, 255), mt_rand(100, 255), mt_rand(100, 255));
        }
        if (mt_rand(0, 1)) { // Horizontal
            $Xa = mt_rand(0, $width / 2);
            $Ya = mt_rand(0, $height);
            $Xb = mt_rand($width / 2, $width);
            $Yb = mt_rand(0, $height);
        } else { // Vertical
            $Xa = mt_rand(0, $width);
            $Ya = mt_rand(0, $height / 2);
            $Xb = mt_rand(0, $width);
            $Yb = mt_rand($height / 2, $height);
        }
        imagesetthickness($image, mt_rand(1, 3));
        imageline($image, $Xa, $Ya, $Xb, $Yb, $color);
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    private function drawNoise(): void
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < $this->options['noiseLevel']; $i++) {
            list($red, $green, $blue) = $this->getLightColor();

            //杂点颜色
            $noiseColor = imagecolorallocate($this->image, $red, $green, $blue);
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring(
                    $this->image,
                    5,
                    mt_rand(-10, $this->options['width']),
                    mt_rand(-10, $this->options['height']),
                    $codeSet[mt_rand(0, 29)],
                    $noiseColor
                );
            }
        }
    }

    /**
     * 随机浅颜色
     * @return int[]
     */
    private function getLightColor(): array
    {
        return [
            200 + mt_rand(1, 55),
            200 + mt_rand(1, 55),
            200 + mt_rand(1, 55)
        ];
    }

    /**
     * 获取随机颜色
     * @return array
     */
    private function getRandColor(): array
    {
        $red = mt_rand(1, 254);
        $green = mt_rand(1, 254);

        if ($red + $green > 400) {
            $blue = 0;
        } else {
            $blue = 400 - $green - $red;
        }
        return [$red, $green, $blue];
    }

    /**
     * 获取随机深色
     * @return array
     */
    private function getDeepColor(): array
    {
        list($red, $green, $blue) = $this->getRandColor();
        $increase = 30 + mt_rand(1, 254);

        $red = min(abs(min(255, $red - $increase)),255);
        $green = min(abs(min(255, $green - $increase)),255);
        $blue = min(abs(min(255, $blue - $increase)),255);

        return [$red, $green, $blue];
    }

}