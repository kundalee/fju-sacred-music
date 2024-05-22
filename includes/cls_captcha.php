<?php if (!defined('IN_DUS')) die('No direct script access allowed');

use PHPImageWorkshop\ImageWorkshop as ImageWorkshop;

/**
 * 驗證碼圖片類
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

class captcha
{
    // 存在 session 中的名稱
    public $session_word = 'captcha_word';

    // 背景圖片所在目錄
    public $folder = 'data/captcha';

    // 背景圖片的檔案名稱及背景顏色
    public $themes = array(
        array('captcha_bg01.gif', '#000000'),
        array('captcha_bg02.gif', '#000000'),
        array('captcha_bg03.gif', '#000000')
    );

    // 圖片的寬度
    public $bg_width = 0;

    // 圖片的高度
    public $bg_height = 0;

    // 驗證碼產生類型(0 英數混合, 1 數字, 2 英文)
    public $code_type = 0;

    // 驗證碼字體大小
    public $font_size = 16;

    // 是否加入混淆曲線
    public $use_curve = false;

    // 混淆曲線數量
    public $curve_num = 2;

    // 是否加入雜點
    public $use_noise = false;

    // 驗證碼中使用的字元
    private $code_set = '';

    // 驗證碼圖片
    private $image_layer = null;

    /**
     * 建構子
     *
     * @access    public
     *
     * @param     string     $folder      資料夾路徑
     * @param     integer    $bgWidth     背景圖片寬度
     * @param     integer    $bgHeight    背景圖片高度
     * @param     boolean    $curve       是否加入混淆曲線
     * @param     boolean    $noise       是否加入雜點
     *
     * @return    void
     */
    public function __construct($folder = '', $bgWidth = 0, $bgHeight = 0, $curve = false, $noise = false)
    {
        if (!empty($folder)) {

            $this->folder = $folder;
        }

        $this->bg_width = !empty($bgWidth) ? $bgWidth : (4 * $this->font_size * 1.5 + $this->font_size * 1.5);
        $this->bg_height = !empty($bgHeight) ? $bgHeight : ($this->font_size * 2);
        $this->use_curve = $curve;
        $this->use_noise = $noise;

        // 檢查是否支持 GD
        return (function_exists('imagecreatetruecolor') || function_exists('imagecreate'));
    }

    /**
     * 檢查驗證碼是否和 session 中的一致
     *
     * @access    public
     *
     * @param     string     $text    驗證碼
     *
     * @return    boolean
     */
    public function checkWord($text)
    {
        $recorded = isset($_SESSION[SHOP_SESS][$this->session_word])
                  ? base64_decode($_SESSION[SHOP_SESS][$this->session_word])
                  : '';
        $given = $this->encryptsWord(strtoupper($text));

        return preg_match('/' . $given . '/', $recorded);
    }

    /**
     * 生成隨機的驗證碼
     *
     * @access    public
     *
     * @param     integer    $length    驗證碼長度
     * @param     integer    $type      驗證碼產生類型(0 英數混合, 1 數字, 2 英文)
     *
     * @return    string
     */
    public function generateWord($length = 4, $type = null)
    {
        !is_null($type) && $this->code_type = $type;

        switch ($this->code_type) {

            // 數字
            case 1:

                $this->code_set = '123456789';
                break;

            // 英文
            case 2:

                $this->code_set = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            // 英數混合
            default:

                $this->code_set = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        }

        return substr(str_shuffle($this->code_set), 5, $length);
    }

    /**
     * 產生圖片並輸出到瀏覽器
     *
     * @access    public
     *
     * @param     string     $text    驗證碼
     *
     * @return    mix
     */
    public function generateImage($text = false)
    {
        if (!$text) {

            $text = $this->generateWord();
        }

        // 記錄驗證碼到 session
        $this->recordWord($text);

        // 驗證碼長度
        $letters = strlen($text);

        shuffle($this->themes);

        $theme = current($this->themes);

        // 設置背景
        if (!($theme[0] === null || $theme[0] == 'transparent' || preg_match('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $theme[0])) &&
            file_exists($this->folder . $theme[0])) {

            $this->image_layer = ImageWorkshop::initFromPath($this->folder . $theme[0]);
            $this->image_layer->resizeBackground($this->bg_width, $this->bg_height);

        } else {

            $theme[0] !== null && $theme[0] = ltrim($theme[0], '#');

            $this->image_layer = ImageWorkshop::initVirginLayer($this->bg_width, $this->bg_height, $theme[0]);
        }

        // 產生干擾像素
        if (true == $this->use_noise) {

            $this->generateNoise();
        }

        $fontColor = ltrim($theme[1], '#');

        // 驗證碼使用隨機字體
        $fonts = glob($this->folder . 'fonts/*.ttf');
        shuffle($fonts);

        $fontPath = current($fonts);
        if (file_exists($fontPath)) {

            $fontRotation = mt_rand(-5, 5); // 字體角度

            // 計算 TrueType 文字範圍的像素大小
            $dimension = imagettfbbox($this->font_size, 0, $fontPath, $text);

            $positionX = ($this->bg_width - $dimension[4]) / 2;
            $positionY = ($this->bg_height - $dimension[5]) / 2;

            $this->image_layer->write($text, $fontPath, $this->font_size, $fontColor, $positionX, $positionY, $fontRotation);

        } else {

            $positionX = ($this->bg_width - (imagefontwidth(5) * $letters)) / 2;
            $positionY = ($this->bg_height - imagefontheight(5)) / 2;

            $this->image_layer->writeText($text, 5, $fontColor, $positionX, $positionY);
        }

        // 產生混淆曲線
        if (true == $this->use_curve) {

            $this->generateCurve();
        }

        $image = $this->image_layer->getImage();

        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

        // HTTP/1.1
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0, max-age=0', false);

        // HTTP/1.0
        header('Pragma: no-cache');
        header('Content-type: image/png');
        imagepng($image);

        $this->image_layer->delete();

        return true;
    }

    /**
     * 產生一條由兩條連在一起構成的隨機正弦函數曲線作干擾線
     * 正弦函數 (y = Asin(ωx+φ) + b)
     * A：決定峰值 即縱向拉伸壓縮的倍數)
     * b：表示波形在 Y 軸的位置關係或縱向移動距離 (上加下減)
     * φ：決定波形與 X 軸位置關係或橫向移動距離 (左加右減)
     * ω：決定週期 (最小正週期 T = 2π / ∣ω∣)
     *
     * @access    protected
     *
     * @return    void
     */
    protected function generateCurve()
    {
        $image = $this->image_layer->getImage();

        for ($line = 0; $line < $this->curve_num; $line++) {

            $curveColor = imagecolorallocate(
                  $image,
                  mt_rand(150, 225),
                  mt_rand(150, 225),
                  mt_rand(150, 225)
            );

            $a = mt_rand(1, $this->bg_height / 2); // 振幅
            $b = mt_rand(-$this->bg_height / 4, $this->bg_height / 4); // Y 軸方向偏移量
            $f = mt_rand(-$this->bg_height / 4, $this->bg_height / 4); // X 軸方向偏移量
            $t = $this->bg_width < $this->bg_height // 週期
               ? mt_rand($this->bg_width * 1.5, $this->bg_height * 2)
               : mt_rand($this->bg_height * 1.5, $this->bg_width * 2);
            $w = (2 * M_PI) / $t;

            $px1 = 0; // 曲線橫坐標起始位置
            $px2 = mt_rand($this->bg_width / 2, $this->bg_width * 0.667); // 曲線橫坐標結束位置
            for ($px = $px1; $px <= $px2; $px = $px + 0.9) {

                if ($w != 0) {

                    $py = $a * sin($w * $px + $f) + $b + $this->bg_height / 2; // y = Asin(ωx + φ) + b
                    $i = intval($this->font_size / 4);

                    while ($i > 0) {

                        imagesetpixel($image, $px + $i, $py + $i, $curveColor);
                        $i--;
                    }
                }
            }

            $a = mt_rand(1, $this->bg_height / 2); // 振幅
            $f = mt_rand(-$this->bg_height / 4, $this->bg_height / 4); // X 軸方向偏移量
            $t = $this->bg_height < $this->bg_height // 週期
               ? mt_rand($this->bg_width * 1.5, $this->bg_height * 2)
               : mt_rand($this->bg_height * 1.5, $this->bg_width * 2);
            $w = (2 * M_PI) / $t;
            $b = $py - $a * sin($w * $px + $f) - $this->bg_height / 2;
            $px1 = $px2;
            $px2 = $this->bg_width;
            for ($px = $px1; $px <= $px2; $px = $px + 0.9) {

                if ($w != 0) {

                    $py = $a * sin($w * $px + $f) + $b + $this->bg_height / 2; // y = Asin(ωx + φ) + b
                    $i = intval($this->font_size / 4);

                    while ($i > 0) {

                        imagesetpixel($image, $px + $i, $py + $i, $curveColor);
                        $i--;
                    }
                }
            }
        }
    }

    /**
     * 建立雜點
     *
     * @access    protected
     *
     * @return    void
     */
    protected function generateNoise()
    {
        $image = $this->image_layer->getImage();

        for ($i = 0; $i < 10; $i++) {

            // 雜點顏色
            $noiseColor = imagecolorallocate(
                  $image,
                  mt_rand(150, 225),
                  mt_rand(150, 225),
                  mt_rand(150, 225)
            );

            for ($j = 0; $j < 5; $j++) {

                // 雜點為隨機的文字
                imagestring(
                    $image,
                    1,
                    mt_rand(-10, $this->bg_width),
                    mt_rand(-10, $this->bg_height),
                    $this->code_set[mt_rand(0, (strlen($this->code_set) - 1))],
                    $noiseColor
                );
            }

        }
    }

    /**
     * RGB 顏色反轉
     *
     * @access    protected
     *
     * @param     integer     $r    紅色
     * @param     integer     $g    綠色
     * @param     integer     $b    藍色
     *
     * @return    array
     */
    protected function rgbInverse($r, $g, $b)
    {
        if (is_array($r) && sizeof($r) == 3) {

            list($r, $g, $b) = $r;
        }

        $r = 255 - intval($r);
        $g = 255 - intval($g);
        $b = 255 - intval($b);

        return array($r, $g, $b);
    }

    /**
     * 對需要記錄的字串進行加密
     *
     * @access    private
     *
     * @param     string     $text    原始字串
     *
     * @return    string
     */
    private function encryptsWord($text)
    {
        return substr(md5($text), 1, 10);
    }

    /**
     * 將驗證碼保存到 session
     *
     * @access    private
     *
     * @param     string     $text    原始字串
     *
     * @return    void
     */
    private function recordWord($text)
    {
        $_SESSION[SHOP_SESS][$this->session_word] = base64_encode($this->encryptsWord($text));
    }
}