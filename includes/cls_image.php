<?php if (!defined('IN_DUS')) die('No direct script access allowed');

use PHPImageWorkshop\ImageWorkshop as ImageWorkshop;

/**
 * 圖片處理類別
 * ========================================================
 *
 *
 * ========================================================
 * Date: 2016-09-13 17:36
 */
class cls_image
{
    public $gd_version = -1;

    public $error = array();

    protected $properties = array(
        'width' => 0, // 圖片的寬度
        'height' => 0, // 圖片的高度
        'bits' => null, // 圖片的顏色位元
        'channels' => null, // 圖片通道值
        'mime_type' => null, // 圖片的媒體類型
        'bgcolor' => '#FFFFFF', // 背景顏色
        'root_path' => '', // 根目錄
        'data_dir' => 'data',
        'images_dir' => 'images',
        'file_ext' => '', // 檔案副檔名
        'full_path' => '' // 影像完整路徑
    );

    private $type_maping = array(
        IMAGETYPE_GIF => 'image/gif',
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_BMP => 'image/bmp'
    );

    private $image = null; // 原始圖片內容

    private $layer;

    private $img_type = 0;

    /**
     * 建構子
     *
     * @access    public
     *
     * @return    void
     */
    public function __construct($color = '')
    {
        if (defined('ROOT_PATH')) {

            $this->root_path = ROOT_PATH;
        }
        if (defined('DATA_DIR')) {

            $this->setDataDir(DATA_DIR);
        }
        if (defined('DATA_DIR')) {

            $this->setImagesDir(IMAGE_DIR);
        }
        if ($color != '') {

            $this->setBgColor($color);
        }
    }

    /**
     * 解構子
     *
     * @access    public
     *
     * @return    void
     */
    public function __destruct()
    {
        if ($this->image !== null) {

            imagedestroy($this->image);

            // 如果發生錯誤，刪除圖片
            if (!empty($this->error)) {

                @unlink($this->root_path . $this->full_path);
            }
        }
    }

    /**
     * 設置私有屬性值
     *
     * @access    public
     *
     * @return    void
     */
    public function __set($k, $v)
    {
        $this->properties[$k] = $v;
    }

    /**
     * 取得私有屬性值
     *
     * @access    public
     *
     * @return     void
     */
    public function __get($k)
    {
        if (array_key_exists($k, $this->properties)) {

            return $this->properties[$k];
        }
    }

    /**
     * 紀錄錯誤訊息
     *
     * @access    private
     *
     * @param     string    $msg    錯誤訊息
     * @param     string    $no     錯誤代碼
     *
     * @return    void
     */
    private function logErrorMsg($msg, $no)
    {
        $this->error[] = array('error' => $msg, 'errno' => $no);
    }

    /**
     * 設定資料存放位置
     *
     * @access    public
     *
     * @param     string    $dir    存放資料夾名稱
     *
     * @return    object
     */
    public function setDataDir($dir) {

        $this->properties['data_dir'] = $dir;

        return $this;
    }

    /**
     * 設定圖片存放位置
     *
     * @access    public
     *
     * @param     string    $dir    存放資料夾名稱
     *
     * @return    object
     */
    public function setImagesDir($dir)
    {
        $this->properties['images_dir'] = $dir;

        return $this;
    }

    /**
     * 設定背景顏色
     *
     * @access    public
     *
     * @param     string    $bgcolor    背景顏色 (十六進位色碼)
     *
     * @return    object
     */
    public function setBgColor($bgcolor)
    {
        $this->properties['bgcolor'] = $bgcolor;

        return $this;
    }

    /**
     * 上傳圖片
     *
     * @access    public
     *
     * @param     array      $upload       檔案上傳陣列
     * @param     integer    $dir          上傳資料夾
     * @param     integer    $imgName      圖片名稱
     * @param     boolean    $checkType    是否檢查圖片格式
     *
     * @return    object
     */
    public function uploadImage($upload, $dir = '', $imgName = '', $checkType = true)
    {
        /* 判別上傳狀態 */
        switch ($upload['error']) {
            // 上傳成功
            case 0:
                // 驗證檔案格式
                if ($checkType === true) {

                    if (!$this->checkImageType($upload['type'])) {

                        $errorMsg = '不是允許的圖片格式';
                        $errorNo = ERR_INVALID_IMAGE_TYPE;
                        $this->logErrorMsg($errorMsg, $errorNo);

                        return $this;
                    }
                }
                break;

            // 檔案大小超出了伺服器上傳限制
            case 0:

                $errorMsg = sprintf('圖片超出上傳檔案限制 (最大值: %s), 無法上傳。', ini_get('upload_max_filesize'));
                $errorNo = ERR_UPLOAD_SERVER_SIZE;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;

            // 檔案僅部分被上傳
            case 3:

                $errorMsg = '檔案僅部分被上傳。';
                $errorNo = ERR_UPLOAD_PARTIAL;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;

            // 沒有找到要上傳的檔案
            case 4:

                $errorMsg = '沒有找到要上傳的檔案。';
                $errorNo = ERR_UPLOAD_NO_FILE;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;

            // 伺服器臨時檔案遺失
            case 5:

                $errorMsg = '伺服器臨時檔案遺失。';
                $errorNo = ERR_UPLOAD_TEMP_MISS;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;

            // 檔案寫入到暫存資料夾錯誤
            case 6:

                $errorMsg = '檔案寫入到暫存資料夾錯誤。';
                $errorNo = ERR_UPLOAD_NO_TMP_DIR;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;

            // 無法寫入硬碟
            case 7:

                $errorMsg = '無法寫入硬碟。';
                $errorNo = ERR_UPLOAD_CANT_WRITE;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;

            // 擴充元件使檔案上傳停止
            case 8:

                $errorMsg = '擴充元件使檔案上傳停止。';
                $errorNo = ERR_UPLOAD_EXTENSION;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;

            // 未知狀態
            default:

                $errorMsg = '未知的上傳失敗。';
                $errorNo = ERR_UPLOAD_UNKNOWN;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
                break;
        }

        /* 沒有指定目錄默認為根目錄 images */
        if (empty($dir)) {

            /* 創建當月目錄 */
            $dir = $this->root_path . $this->data_dir . '/' . $this->images_dir . '/' . date('Ym') . '/';

        } else {

            /* 創建指定目錄 */
            $dir = $this->root_path . $this->data_dir . '/' . $dir . '/';
        }

        /* 如果目標目錄不存在，則創建它 */
        if (!file_exists($dir)) {

            if (!$this->makeDir($dir)) {

                $errorMsg = sprintf('目錄 % 不存在或不可寫', $dir);
                $errorNo = ERR_DIRECTORY_READONLY;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
            }
        }

        if (!empty($imgName)) {

            $imgName = $dir . $imgName; // 將圖片定位到正確地址

        } else {

            $imgName = $dir . $this->uniqueName($dir) . $this->getFileType($upload['name']);
        }

        if ((isset($upload['error']) && $upload['error'] > 0) || !move_uploaded_file($upload['tmp_name'], $imgName)) {

            $errorMsg = sprintf('檔案 %s 上傳失敗。', $upload['name']);
            $errorNo = ERR_UPLOAD_FAILURE;
            $this->logErrorMsg($errorMsg, $errorNo);

        } else {

            $this->open($imgName);
        }

        return $this;
    }

    /**
     * 開啟一個圖片檔案
     *
     * @access    public
     *
     * @param     string    $fileName     原始圖片檔案名稱，包含完整路徑
     *
     * @return    object
     */
    public function open($fileName)
    {
        // 檢查檔案是否存在
        $fileStatus = false;

        if (false === strpos($fileName, '://')) {

            $fileStatus = file_exists($fileName);

        } else {

            if (function_exists('curl_init')) {

                $ch = curl_init($fileName);

                curl_setopt($ch, CURLOPT_NOBODY, true);

                $chResult = curl_exec($ch);

                if (false !== $chResult) {

                    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($statusCode == 200) {

                        $fileStatus = true;
                    }
                }

                curl_close($ch);
            }
        }

        // 檢查檔案是否存在
        if ($fileStatus) {

            $sourceImageAttr = @getimagesize($fileName);
            if ($sourceImageAttr === false) {

                return $this;
            }

            // 取得圖像的寬度，高度和格式
            $this->width     = isset($sourceImageAttr[0]) ? $sourceImageAttr[0] : 0;
            $this->height    = isset($sourceImageAttr[1]) ? $sourceImageAttr[1] : 0;
            $this->img_type  = isset($sourceImageAttr[2]) ? $sourceImageAttr[2] : 0;
            $this->bits      = isset($sourceImageAttr['bits']) ? $sourceImageAttr['bits'] : null;
            $this->channels  = isset($sourceImageAttr['channels']) ? $sourceImageAttr['channels'] : null;
            $this->mime_type = isset($sourceImageAttr['mime']) ? $sourceImageAttr['mime'] : null;

            // set memory for image
            $this->allocationMemory($sourceImageAttr); // 分配記憶體
            $this->file_ext = $this->getExtbyType($this->img_type);

            if (!$this->file_ext) {

                $errorMsg = '建立圖片失敗';
                $errorNo = ERR_NO_GD;
                $this->logErrorMsg($errorMsg, $errorNo);
                return $this;
            }

            $this->image = $this->imageResource($fileName, $this->img_type);
            $this->layer = ImageWorkshop::initFromResourceVar($this->image);

            $this->full_path = str_replace($this->root_path, '', str_replace('\\', '/', $fileName));

        } else {

            $errorMsg = sprintf('找不到原始圖片 %s ', $fileName);
            $errorNo = ERR_IMAGE_NOT_EXISTS;
            $this->logErrorMsg($errorMsg, $errorNo);
        }

        return $this;
    }


    /**
     * 調整圖片尺寸
     *
     * @access    public
     *
     * @param     intger    $w    圖片寬度
     * @param     intger    $h    圖片高度
     *
     * @return    object
     */
    public function resize($w, $h)
    {
        $this->layer->resizeInPixel($w, $h);

        $this->width = $this->layer->getWidth();
        $this->height = $this->layer->getHeight();
        $this->image = $this->layer->getImage();

        return $this;
    }

    /**
     * 裁切圖片
     *
     * @access    public
     *
     * @param     intger    $x    裁切 X 軸座標
     * @param     intger    $y    裁切 Y 軸座標
     * @param     intger    $w    圖片寬度
     * @param     intger    $h    圖片高度
     *
     * @return    object
     */
    public function crop($x, $y, $w, $h)
    {
        $this->layer->cropInPixel($w, $h, $x, $y);

        $this->width = $this->layer->getWidth();
        $this->height = $this->layer->getHeight();
        $this->image = $this->layer->getImage();

        return $this;
    }

    /**
     * 建立圖片的縮圖
     *
     * @access    public
     *
     * @param     integer    $thumbWidth     縮圖寬度
     * @param     integer    $thumbHeight    縮圖高度
     * @param     integer    $thumbType      縮圖類型
     *
     * @return    object
     */
    public function makeThumb($thumbWidth = 0, $thumbHeight = 0, $thumbType = 0)
    {
        /* 檢查是否有錯誤訊息 */
        if (!empty($this->error)) {

            return $this;
        }

        if ($thumbWidth <= 0 && $thumbHeight <= 0) {

            $errorMsg = '尚未設定縮圖寬度與高度';
            $errorNo = ERR_INVALID_THUMB_SIZE;
            $this->logErrorMsg($errorMsg, $errorNo);

            return $this;

        // 當圖片尺寸跟縮圖尺寸一樣不執行縮圖動作
        } elseif ($this->width == $thumbWidth && $this->height == $thumbHeight) {

            $this->image = $this->layer->getResult();

            return $this;
        }

        switch ($thumbType) {
            /* 指定大小等比例縮圖 */
            case 0:

                $resizeQueue = array(
                    array(
                        'width' => $thumbWidth,
                        'height' => null
                    ),
                    array(
                        'width' => null,
                        'height' => $thumbHeight
                    )
                );

                $this->width > $this->height && $resizeQueue = array_reverse($resizeQueue, true);
                foreach ($resizeQueue as $row) {

                    $this->layer->resizeInPixel($row['width'], $row['height'], true);
                }

                $this->image = $this->layer->getImage();
                break;

            /* 中心點截圖 */
            case 1:

                $resizeWidth = $thumbWidth;
                $resizeHeight = $thumbHeight;

                if (
                    ($this->width > $this->height && $thumbHeight != $this->height) ||
                    ($this->width < $this->height && $thumbWidth == $this->width) ||
                    ($this->width == $this->height && $thumbWidth > $thumbHeight)
                ) {

                    $ratio = $thumbHeight / ($this->height * ($thumbWidth / $this->width));
                    if ($ratio > 1) {

                        $resizeWidth = $thumbWidth * $ratio;

                    } else {

                        $resizeHeight = $thumbHeight / $ratio;
                    }

                } elseif (
                    ($this->width < $this->height && $thumbWidth != $this->width) ||
                    ($this->width > $this->height && $thumbHeight != $this->height) ||
                    ($this->width == $this->height && $thumbWidth < $thumbHeight)
                ) {

                    $ratio = $thumbWidth / ($this->width * ($thumbHeight / $this->height));
                    if ($ratio > 1) {

                        $resizeHeight = $thumbHeight * $ratio;

                    } else {

                        $resizeWidth = $thumbWidth / $ratio;
                    }
                }

                $this->layer->resizeInPixel($resizeWidth, $resizeHeight, true);
                if (isset($ratio) && $ratio != 0) {

                    $this->layer->cropInPixel($thumbWidth, $thumbHeight, 0, 0, 'MM');
                }

                $this->image = $this->layer->getImage();
                break;

            /* 底色填滿 */
            case 2:

                $this->layer->resizeInPixel($thumbWidth, $thumbHeight, true);
                $this->image = $this->layer->getResult(ltrim($this->bgcolor, '#'));
                break;
        }

        $this->width = $this->layer->getWidth();
        $this->height = $this->layer->getHeight();

        return $this;
    }

    /**
     * 建立圖片指定寬度的縮圖
     *
     * @access    public
     *
     * @param     integer    $size     縮圖寬度
     *
     * @return    object
     */
    public function makeThumbWidth($size)
    {
        /* 檢查是否有錯誤訊息 */
        if (!empty($this->error)) {

            return $this;
        }

        $this->layer->resizeInPixel($size, null, true);

        $this->width = $this->layer->getWidth();
        $this->height = $this->layer->getHeight();
        $this->image = $this->layer->getImage();

        return $this;
    }

    /**
     * 建立圖片指定高度的縮圖
     *
     * @access    public
     *
     * @param     integer    $size     縮圖高度
     *
     * @return    object
     */
    public function makeThumbHeight($size)
    {
        /* 檢查是否有錯誤訊息 */
        if (!empty($this->error)) {

            return $this;
        }

        $this->layer->resizeInPixel(null, $size, true);

        $this->width = $this->layer->getWidth();
        $this->height = $this->layer->getHeight();
        $this->image = $this->layer->getImage();

        return $this;
    }

    /**
     * 設定圖片透明
     *
     * @access    public
     *
     * @return    object
     */
    private function transparent($image, $imageType)
    {
        // 透明處理
        if ($imageType == IMAGETYPE_GIF || $imageType == IMAGETYPE_PNG) {

            $transparent = imagecolortransparent($image);

            if ($transparent >= 0) {
                // If we have a specific transparent color

                // Get the original image's transparent color's RGB values
                $rgb = @imagecolorsforindex($image, $transparent);

                // 在新的圖片資源分配相同的顏色
                $clr = imagecolorallocate($image, $rgb['red'], $rgb['green'], $rgb['blue']);

                // 完全填充新圖片與分配顏色的背景
                imagefill($image, 0, 0, $clr);

                // 新圖片的背景顏色設置為透明
                imagecolortransparent($image, $clr);

            } elseif ($imageType == IMAGETYPE_PNG) {

                // Always make a transparent background color for PNGs that don't have one allocated already

                // 暫時關閉透明度混合
                imagealphablending($image, false);

                // 建立一個新的透明色圖片
                $clr = imagecolorallocatealpha($image, 0, 0, 0, 127);

                // 完全填充新圖片與分配顏色的背景
                imagefill($image, 0, 0, $clr);

                // 恢復透明度混合
                imagesavealpha($image, true);
            }
        }

        return $this;
    }

    /**
     * 為圖片增加浮水印
     *
     * @access    public
     *
     * @param     string     targetFile      原始圖片檔案名稱，包含完整路徑，為空則覆蓋原檔案
     * @param     string     $watermark      浮水印完整路徑
     * @param     integer    $wmPlace        浮水印位置代碼
     * @param     integer    $wmAlpha        浮水印透明度
     * @param     integer    $wmRotate       浮水印角度旋轉
     * @param     integer    $edgePadding    浮水印於合併圖的內距
     * @param     integer    $wmSize         浮水印於合併圖的比率大小
     *
     * @return    mixed                      如果成功則回傳檔案路徑，否則回傳 false
     */
    public function watermark($targetFile = '', $watermark = '', $wmPlace = '', $wmAlpha = 0.65, $wmRotate = 0, $edgePadding = 15, $wmSize = null)
    {
        /* 檢查是否有錯誤訊息 */
        if (!empty($this->error)) {

            return $this;
        }

        /* 如果水印的位置為0，則返回原圖 */
        if ($wmPlace == 0 || empty($watermark)) {

            return $this;
        }

        if (!$this->validateWatermarkFile($watermark)) {

            /* 已經記錄了錯誤信息 */
            return $this;
        }

        $this->allocationMemory($watermark); // 分配記憶體

        // 取得原始浮水印圖片資訊
        $wmInfo = getimagesize($watermark);
        $wmWidth = isset($wmInfo[0]) ? $wmInfo[0] : 0;
        $wmHeight = isset($wmInfo[1]) ? $wmInfo[1] : 0;
        $wmImgType = isset($wmInfo[2]) ? $wmInfo[2] : 0;

        // 調整浮水印於合併圖的比率大小
        if (is_null($wmSize)) {

            $wmDestWidth = $wmWidth;
            $wmDestHeight = $wmHeight;

        } elseif ($wmSize == 'larger') {

            $placementX = 0;
            $placementY = 0;
            $wmPlace = 3;
            $wmDestWidth = $wmWidth;
            $wmDestHeight = $wmHeight;

            // 浮水印的尺寸都必須為 5% 以上的原始圖片
            // 先調整寬度
            if ($wmWidth > $this->width * 1.05 && $wmHeight > $this->height * 1.05){

                // 兩者都已經大於原來的至少 5%, 需要產生小尺寸的浮水印
                $wdiff = $wmDestWidth - $this->width;
                $hdiff = $wmDestHeight - $this->height;
                if ($wdiff > $hdiff){

                    // 寬度最大的區別 - 得到的百分比
                    $sizer = ($wdiff / $wmDestWidth) - 0.05;

                } else {

                    $sizer = ($hdiff / $wmDestHeight) - 0.05;
                }
                $wmDestWidth -= $wmDestWidth * $sizer;
                $wmDestHeight -= $wmDestHeight * $sizer;

            } else {

                // 浮水印需要被放大
                $wdiff = $this->width - $wmDestWidth;
                $hdiff = $this->height - $wmDestHeight;
                if ($wdiff > $hdiff){

                    // 寬度最大的區別 - 得到的百分比
                    $sizer = ($wdiff / $wmDestWidth) + 0.05;

                } else {

                    $sizer = ($hdiff / $wmDestHeight) + 0.05;
                }
                $wmDestWidth += $wmDestWidth * $sizer;
                $wmDestHeight += $wmDestHeight * $sizer;
            }

        } else {

            $wmDestWidth = round($this->width * floatval($wmSize));
            $wmDestHeight = round($this->height * floatval($wmSize));
            if ($wmSize == 1) {

                $wmDestWidth -= 2 * $edgePadding;
                $wmDestHeight -= 2 * $edgePadding;
            }
        }

        $wmTmp = $this->root_path . $this->data_dir . '/' . pathinfo($watermark, PATHINFO_FILENAME) . '.tmp';

        $this->resizeWatermark($watermark, $wmDestWidth, $wmDestHeight, $wmTmp, $wmRotate);

        // 取得暫存浮水印圖片資訊
        $wmInfo = getimagesize($wmTmp);
        $wmTmpWidth = isset($wmInfo[0]) ? $wmInfo[0] : 0;
        $wmTmpHeight = isset($wmInfo[1]) ? $wmInfo[1] : 0;
        $wmTmpImgType = isset($wmInfo[2]) ? $wmInfo[2] : 0;

        $differenceX = $this->width - $wmTmpWidth;
        $differenceY = $this->height - $wmTmpHeight;

        // 0|無,1|左上,2|右上,3|居中,4|左下,5|右下
        // 根據系統設置獲得水印的位置
        switch ($wmPlace) {
            // 左上
            case 1:
            case 'left top':
                $placementX = $edgePadding;
                $placementY = $edgePadding;
                break;
            // 中上
            case 6:
            case 'center top':
                $placementX = round($differenceX / 2);
                $placementY = $edgePadding;
                break;
            // 右上
            case 2:
            case 'right top':
                $placementX = $this->width - $wmDestWidth - $edgePadding;
                $placementY = $edgePadding;
                $y = 0;
                break;
            // 左下
            case 4:
            case 'left bottom':
                $placementX = $edgePadding;
                $placementY = $this->height - $wmDestHeight - $edgePadding;
                break;
            // 中下
            case 9:
            case 'center bottom':
                $placementX = round($differenceX / 2);
                $placementY = $this->height - $wmDestHeight - $edgePadding;
                break;
            // 右下
            case 5:
            case 'right bottom':
                $placementX = $this->width - $wmDestWidth - $edgePadding;
                $placementY = $this->height - $wmDestHeight - $edgePadding;
                break;
            // 置中
            case 3:
            case 'center':
            case 'center center':
                $placementX = round($differenceX / 2);
                $placementY = round($differenceY / 2);
                break;
            // 左中
            case 7:
            case 'center left':
                $placementX = $edgePadding;
                $placementY = round($differenceY / 2);
                break;
            // 右中
            case 8:
            case 'center right':
                $placementX = $this->width - $wmDestWidth - $edgePadding;
                $placementY = round($differenceY / 2);
                break;
        }

        $wmHandle = $this->imageResource($wmTmp, $wmTmpImgType);

        if (!$wmHandle) {

            $errorMsg = sprintf('建立浮水印圖片資源失敗。浮水印圖片類型為%s', $this->type_maping[$wmInfo[2]]);
            $errorNo = ERR_INVALID_IMAGE;
            $this->logErrorMsg($errorMsg, $errorNo);

            return $this;
        }

        $this->imageMergeAlpha(
            $this->image,
            $wmHandle,
            $placementX,
            $placementY,
            0,
            0,
            $wmInfo[0],
            $wmInfo[1],
            $wmAlpha
        );
        unlink($wmTmp);; // 刪除浮水印暫存檔案

        $dest = $this->root_path . (empty($targetFile) ? $this->full_path : $targetFile);

        if ($this->root_path == '' || $path = realpath($dest)) {

            switch ($this->file_ext) {
                case 'gif':
                    imagegif($this->image, $dest);
                    break;
                case 'jpg':
                    imagejpeg($this->image, $dest, 100);
                    break;
                case 'png':
                    imagepng($this->image, $dest);
                    break;
                case 'bmp':
                    imagebmp($this->image, $dest);
                    break;
            }

            if ($this->root_path == '') {

                $this->full_path = $dest;

            } else {

                $this->full_path = str_replace($this->root_path, '', str_replace('\\', '/', $path));
            }

            return $this;

        } else {

            $errorMsg = '圖片寫入失敗';
            $errorNo = ERR_DIRECTORY_READONLY;
            $this->logErrorMsg($errorMsg, $errorNo);

            return $this;
        }
    }

    /**
     * 圖片透明合併
     *
     * @access    private
     *
     * @param     resource    $destImg    目標圖片資源
     * @param     resource    $srcImg     來源圖片資源
     * @param     integer     $destX      置放於目標圖片的 X 軸座標
     * @param     integer     $destY      置放於目標圖片的 Y 軸座標
     * @param     integer     $srcX       來源圖片 X 軸座標
     * @param     integer     $srcY       來源圖片 Y 軸座標
     * @param     integer     $srcW       來源圖片寬度
     * @param     integer     $srcH       來源圖片高度
     * @param     integer     $pct        來源圖片透明度
     *
     * @return    object
     */
    private function imageMergeAlpha($destImg, $srcImg, $destX, $destY, $srcX, $srcY, $srcW, $srcH, $pct = 0)
    {
        $destW = imagesx($destImg);
        $destH = imagesy($destImg);

        for ($y = 0; $y < $srcH + $srcY; $y++) {

            for ($x = 0; $x < $srcW + $srcX; $x++) {

                if ($x + $destX >= 0 && $x + $destX < $destW && $x + $srcX >= 0 && $x + $srcX < $srcW && $y + $destY >= 0 && $y + $destY < $destH && $y + $srcY >= 0 && $y + $srcY < $srcH) {

                    $destPixel = imagecolorsforindex($destImg, imagecolorat($destImg, $x + $destX, $y + $destY));

                    $srcImgColorat = imagecolorat($srcImg, $x + $srcX, $y + $srcY);

                    if ($srcImgColorat > 0) {

                        $srcPixel = imagecolorsforindex($srcImg, $srcImgColorat);

                        $srcAlpha = 1 - ($srcPixel['alpha'] / 127);
                        $destAlpha = 1 - ($destPixel['alpha'] / 127);
                        $opacity = $srcAlpha * $pct / 100;

                        if ($destAlpha >= $opacity) {

                            $alpha = $destAlpha;
                        }
                        if ($destAlpha < $opacity) {

                            $alpha = $opacity;
                        }
                        if ($alpha > 1) {

                            $alpha = 1;
                        }

                        if ($opacity > 0) {

                            $destRed = round((($destPixel['red'] * $destAlpha * (1 - $opacity))));
                            $destGreen = round((($destPixel['green'] * $destAlpha * (1 - $opacity))));
                            $destBlue = round((($destPixel['blue'] * $destAlpha * (1 - $opacity))));
                            $srcRed = round((($srcPixel['red'] * $opacity)));
                            $srcGreen = round((($srcPixel['green'] * $opacity)));
                            $srcBlue = round((($srcPixel['blue'] * $opacity)));
                            $red = round(($destRed + $srcRed) / ($destAlpha * (1 - $opacity) + $opacity));
                            $green = round(($destGreen + $srcGreen) / ($destAlpha * (1 - $opacity) + $opacity));
                            $blue = round(($destBlue + $srcBlue) / ($destAlpha * (1 - $opacity) + $opacity));

                            if ($red > 255) {

                                $red = 255;
                            }
                            if ($green > 255) {

                                $green = 255;
                            }
                            if ($blue  > 255) {

                                $blue  = 255;
                            }

                            $alpha = round((1 - $alpha) * 127);

                            $color = imagecolorallocatealpha($destImg, $red, $green, $blue, $alpha);

                            imagesetpixel($destImg, $x + $destX, $y + $destY, $color);
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * 移動檔案
     *
     * @access    public
     *
     * @param     string     $newFile    目標檔案路徑
     *
     * @return    boolean
     */
    public function moveFile($newFileName = null, $recover = true)
    {
        $dirPath = $this->root_path . $this->data_dir . '/' . $this->images_dir . '/' . date('Ym') . '/';

        /* 如果目標目錄不存在，則創建它 */
        if (!file_exists($dirPath)) {

            if (!$this->makeDir($dirPath)) {

                /* 創建目錄失敗 */
                $errorMsg = sprintf('目錄 %s 不存在或不可寫', $dirPath);
                $errorNo = ERR_DIRECTORY_READONLY;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
            }
        }

        if (is_null($newFileName)) {

            $newFileUrl = $dirPath . $this->uniqueName($dirPath) . '.' . $this->file_ext;
            $pathinfo['extension'] = $this->file_ext;

        } else {

            preg_match(
                '%^(?<dirname>.*?)[\\\\/]*(?<basename>(?<filename>[^/\\\\]*?)(?:\.(?<extension>[^\.\\\\/]+?)|))[\\\\/\.]*$%im',
                $newFileName,
                $pathinfo
            );

            if ($recover) {

                unlink($dirPath . $newFileName);
            }

            // 檢查檔案是否已存在
            $i = 0;
            do {

                $newFileName = $pathinfo['filename'] . ($i > 0 ? '(' . $i . ')' : '') . '.' . $pathinfo['extension'];
                $newFileUrl = $dirPath . $newFileName;
                $i++;

            } while (is_file($newFileUrl));
        }

        if (@rename($this->root_path . $this->full_path, $newFileUrl)) {

            $this->open($newFileUrl);

        } else {

            $errorMsg = '圖片寫入失敗';
            $errorNo = ERR_DIRECTORY_READONLY;
            $this->logErrorMsg($errorMsg, $errorNo);
        }

        return $this;
    }

    /**
     * 圖片另存為一個檔案格式
     *
     * @access    public
     *
     * @return     object
     */
    public function save($dest, $type = 'jpg')
    {
        if (!file_exists($dest)) {

            switch ($type) {
                case 'gif':
                    imagegif($this->image, $dest);
                    break;
                case 'jpg':
                    imagejpeg($this->image, $dest, 100);
                    break;
                case 'png':
                    imagepng($this->image, $dest);
                    break;
                case 'bmp':
                    imagebmp($this->image, $dest);
                    break;
            }
        }

        return $this;
    }

    /**
     * 自動存檔
     *
     * @access    public
     *
     * @param     string    $dir         存檔資料夾名稱
     * @param     string    $saveType    存檔格式
     *
     * @return    object
     */
    public function autoSave($dir = '', $saveType = null)
    {
        if (!empty($this->error)) {

            return $this;
        }

        /* 沒有指定目錄默認為根目錄images */
        if (empty($dir)) {

            /* 目標目錄 */
            $dir = $this->root_path . $this->data_dir . '/' . $this->images_dir . '/' . date('Ym') . '/';

        } else {

            /* 目標目錄 */
            $dir = $this->root_path . $this->data_dir . '/' . $dir . '/';
        }

        $this->autoPathSave($dir, $saveType);

        return $this;
    }

    /**
     * 指定資料夾存檔
     *
     * @access    public
     *
     * @param     string    $dirPath     存檔資料夾名稱
     * @param     string    $saveType    存檔格式
     *
     * @return    object
     */
    public function autoPathSave($dirPath, $saveType = null)
    {
        if (!empty($this->error)) {

            return $this;
        }

        /* 如果目標目錄不存在，則創建它 */
        if (!file_exists($dirPath)) {

            if (!$this->makeDir($dirPath)) {

                /* 創建目錄失敗 */
                $errorMsg = sprintf('目錄 %s 不存在或不可寫', $dirPath);
                $errorNo = ERR_DIRECTORY_READONLY;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
            }
        }

        /* 如果文件名為空，生成不重名隨機文件名 */
        $fileName = $this->uniqueName($dirPath) . '.' . (is_null($saveType) ? $this->file_ext : $saveType);
        $fullPath = $dirPath . $fileName;

        $this->save($fullPath, (is_null($saveType) ? $this->file_ext : $saveType));
        $this->full_path = str_replace($this->root_path, '', str_replace('\\', '/', $fullPath));

        return $this;
    }

    /**
     * 圖片直接呈現至瀏覽器 (不儲存)
     *
     * @access    public
     *
     * @param     string      $type          圖片類型
     * @param     resource    $imgResurce    圖片資源
     *
     * @return    object
     */
    public function render($type = 'png', $imgResurce = null)
    {
        if (is_null($imgResurce)) {

            $imgResurce = $this->image;
        }

        switch ($this->getExtbyType($type)) {

            case 'gif':

                header('Content-Type: image/gif');
                imagegif($imgResurce);
                break;

            case 'jpg':

                header('Content-Type: image/jpeg');
                imagejpeg($imgResurce);
                break;

            case 'png':

                header('Content-Type: image/png');
                imagepng($imgResurce);

            case 'bmp':

                header('Content-Type: image/bmp');
                imagebmp($imgResurce);
                break;
        }

        return $this;
    }

    /**
     * 動態分配記憶體
     *
     * @return    void
     */
    private function allocationMemory($imageinfo)
    {
        $srcWidth = isset($imageinfo[0]) ? $imageinfo[0] : 0;
        $srcHeight = isset($imageinfo[1]) ? $imageinfo[1] : 0;
        $srcImgType = isset($imageinfo[2]) ? $imageinfo[2] : 0;
        $srcBits = isset($imageinfo['bits']) ? $imageinfo['bits'] : null;
        $srcChannels = isset($imageinfo['channels']) ? $imageinfo['channels'] : null;
        $srcMimeType = isset($imageinfo['mime']) ? $imageinfo['mime'] : null;

        /**
         * 計算所需記憶體
         * Width x Height x 8 (bits) x 3(channels) / 8 x 1.65
         */
        $memoryRequired = $srcWidth * $srcHeight;
        !is_null($srcBits) && $srcBits > 0 && $memoryRequired *= $srcBits;
        !is_null($srcChannels) && $srcChannels > 0 && $memoryRequired *= ($srcChannels / 8);

        $memoryLimited = $this->convertToBytes(@ini_get('memory_limit')); // 取得目前記憶體上限
        $memoryUsage = memory_get_usage(); // 取得目前已使用記憶體
        $memoryTotal = $memoryUsage + $memoryRequired; // 預估記憶體總使用量

        // 更新記憶體上限
        if ($memoryTotal > $memoryLimited) {
            $memoryLimiting = $memoryRequired * 1.65;

            if (false === @ini_set('memory_limit', $memoryLimiting)) {

                $errorMsg = sprintf(
                    '圖片超出上傳檔案限制 (最大值: %s), 無法上傳。',
                    ini_get('upload_max_filesize')
                );
                $errorNo = ERR_UPLOAD_SERVER_SIZE;
                $this->logErrorMsg($errorMsg, $errorNo);

                return $this;
            }
        }

        return $this;
    }

    /**
     * 清空錯誤資訊
     *
     * @access    public
     *
     * @return    object
     */
    public function cleanErrorMsg()
    {
        $this->error = array();

        return $this;
    }

    /**
     * 回傳最後一條錯誤資訊
     *
     * @access    public
     *
     * @return    object
     */
    public function lastErrorMsg()
    {
        $this->error = array_slice($this->error, -1);

        return $this;
    }

    /**
     * 檢查浮水印圖片是否合法
     *
     * @access    private
     *
     * @param     string     $path    圖片路徑
     *
     * @return    boolean
     */
    public function validateWatermarkFile($path)
    {
        if (empty($path)) {

            $errorMsg = '浮水印檔參數不能為空';
            $errorNo = ERR_INVALID_PARAM;
            $this->logErrorMsg($errorMsg, $errorNo);

            return false;
        }

        // 檔案是否存在
        if (!file_exists($path)) {

            $errorMsg = sprintf('找不到浮水印檔%s', $path);
            $errorNo = ERR_IMAGE_NOT_EXISTS;
            $this->logErrorMsg($errorMsg, $errorNo);

            return false;
        }

        // 取得檔案以及源檔案的資訊
        $imageInfo = @getimagesize($path);

        if (!$imageInfo) {

            $errorMsg = sprintf('無法識別浮水印圖片 %s ', $path);
            $errorNo = ERR_INVALID_IMAGE;
            $this->logErrorMsg($errorMsg, $errorNo);

            return false;
        }

        /* 檢查處理函數是否存在 */
        if (!$this->checkImageFunction($imageInfo[2])) {

            $errorMsg = sprintf('不支援該圖像格式 %s ', $this->type_maping[$imageInfo[2]]);
            $errorNo = ERR_NO_GD;
            $this->logErrorMsg($errorMsg, $errorNo);

            return false;
        }

        return true;
    }

    /**
     * 調整浮水印尺寸
     *
     * @access    private
     *
     * @return    void
     */
    private function resizeWatermark($source, $newWidth, $newHeight, $target, $rotate = 0)
    {
        $srcInfo = @getimagesize($source);
        $srcWidth = isset($srcInfo[0]) ? $srcInfo[0] : 0;
        $srcHeight = isset($srcInfo[1]) ? $srcInfo[1] : 0;
        $srcImgType = isset($srcInfo[2]) ? $srcInfo[2] : 0;
        $srcBits = isset($srcInfo['bits']) ? $srcInfo['bits'] : null;
        $srcChannels = isset($srcInfo['channels']) ? $srcInfo['channels'] : null;

        $this->allocationMemory($srcInfo); // 分配記憶體

        $srcImage = $this->imageResource($source, $srcImgType);
        if ($srcImage == '') {

            return false;
        }

        $percentage = (double) $newWidth / $srcWidth;
        $destHeight = round($srcHeight * $percentage) + 1;
        $destWidth = round($srcWidth * $percentage) + 1;

        // 如果寬度產生的高度比新高度要大，計算基礎高度
        if ($destHeight > $newHeight) {

            $percentage = (double) $newHeight / $srcHeight;
            $destHeight = round($srcHeight * $percentage) + 1;
            $destWidth = round($srcWidth * $percentage) + 1;
        }

        // 重新分配記憶體
        $this->allocationMemory(array_merge($srcInfo, array($destWidth, $destHeight)));

        $destImage = imagecreatetruecolor($destWidth - 1, $destHeight - 1);
        if (($srcImgType == IMAGETYPE_GIF) || ($srcImgType == IMAGETYPE_PNG)) {

            $this->transparent($destImage, $srcImgType);

        } else {

            $bgcolor = trim($this->bgcolor, '#');

            sscanf($bgcolor, '%2x%2x%2x', $red, $green, $blue);

            $clr = imagecolorallocate($destImage, $red, $green, $blue);

            imagefill($destImage, 0, 0, $clr);
        }

        imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
        if ($rotate != 0) {

            $destImage = imagerotate($destImage, $rotate, $clr);
        }

        switch ($srcImgType) {
            case IMAGETYPE_GIF:

                imagegif($destImage, $target);
                break;

            case IMAGETYPE_JPEG:

                imagejpeg($destImage, $target, 100);
                break;

            case IMAGETYPE_PNG:

                imagesavealpha($destImage, true);
                imagepng($destImage, $target);
                break;

            case IMAGETYPE_BMP:

                imagebmp($destImage, $target, $srcBits);
                break;

            default:

                return false;
        }

        imagedestroy($destImage);
        imagedestroy($srcImage);

        return true;
    }

    /**
     * 將資料長度轉成位元組
     *
     * @param     string    $num    帶有簡寫容量單位的資料長度
     *
     * @return    float
     */
    private function convertToBytes($num)
    {
        $units = array(
            'B' => 0,               // Byte
            'K' => 1,   'KB' => 1,  // Kilobyte
            'M' => 2,   'MB' => 2,  // Megabyte
            'G' => 3,   'GB' => 3,  // Gigabyte
            'T' => 4,   'TB' => 4,  // Terabyte
            'P' => 5,   'PB' => 5,  // Petabyte
            'E' => 6,   'EB' => 6,  // Exabyte
            'Z' => 7,   'ZB' => 7,  // Zettabyte
            'Y' => 8,   'YB' => 8   // Yottabyte
        );

        $unit = strtoupper(trim(preg_replace('/[0-9.]/', '', $num)));
        $num = trim(preg_replace('/[^0-9.]/', '', $num));

        if (!in_array($unit, array_keys($units))) {
            return false;
        }

        return (float)$num * pow(1024, $units[$unit]);
    }

    /**
     * 將位元組轉成可閱讀格式
     *
     * @param     float      $bytes       位元組
     * @param     integer    $decimals    分位數
     * @param     string     $unit        容量單位
     *
     * @return    string
     */
    private function formatByte($bytes, $decimals = 0, $unit = '')
    {
        $units = array(
            'B' => 0,               // Byte
            'K' => 1,   'KB' => 1,  // Kilobyte
            'M' => 2,   'MB' => 2,  // Megabyte
            'G' => 3,   'GB' => 3,  // Gigabyte
            'T' => 4,   'TB' => 4,  // Terabyte
            'P' => 5,   'PB' => 5,  // Petabyte
            'E' => 6,   'EB' => 6,  // Exabyte
            'Z' => 7,   'ZB' => 7,  // Zettabyte
            'Y' => 8,   'YB' => 8   // Yottabyte
        );

        $value = 0;
        if ($bytes > 0) {
            if (!array_key_exists($unit, $units)) {
                $pow = floor(log($bytes) / log(1024));
                $unit = array_search($pow, $units);
            }

            $value = ($bytes / pow(1024, floor($units[$unit])));
        }

        $decimals = floor(abs($decimals));
        if ($decimals > 53) {
            $decimals = 53;
        }

        return sprintf('%.' . $decimals . 'f %s', $value, $unit);
    }

    private function getExtbyType($mimeType)
    {
        $fileExt = false;
        switch ($mimeType) {

            // gif 檔案
            case IMAGETYPE_GIF:
            case 'image/gif':
            case 'gif':

                $fileExt = 'gif';
                break;

            // jpg 檔案
            case IMAGETYPE_JPEG:
            case 'image/pjpeg':
            case 'image/jpeg':
            case 'jpg':

                $fileExt = 'jpg';
                break;

            // png 檔案
            case IMAGETYPE_PNG:
            case 'image/x-png':
            case 'image/png':
            case 'png':

                $fileExt = 'png';
                break;

            // bmp 檔案
            case IMAGETYPE_BMP:
            case 'image/bmp':
            case 'image/x-ms-bmp':
            case 'bmp':

                $fileExt = 'bmp';
                break;
        }

        return $fileExt;
    }

    /**
     * 根據來源檔案的檔案類型建立一個圖像操作的標識符
     * 如果成功則回傳圖像操作標誌符，反之則回傳錯誤代碼
     *
     * @access    private
     *
     * @param     string      $imageFile   圖片檔案的路徑
     * @param     string      $mimeType    圖片檔案的檔案類型
     *
     * @return    mixed
     */
    private function imageResource($imageFile, $mimeType)
    {
        $res = false;

        switch ($this->getExtbyType($mimeType)) {
            // gif 檔案
            case 'gif':
                $res = imagecreatefromgif($imageFile);
                break;
            // jpg 檔案
            case 'jpg':
                $res = imagecreatefromjpeg($imageFile);
                break;
            // png 檔案
            case 'png':
                $res = imagecreatefrompng($imageFile);
                break;
            // bmp 檔案
            case 'bmp':
                $res = imagecreatefrombmp($imageFile);
                break;
        }

        return $res;
    }

    /**
     * 檢查目標檔案夾是否存在，如果不存在則自動建立該目錄
     *
     * @access    private
     *
     * @param     string     $folder    目錄路徑。不能使用相對於網站根目錄的 URL
     *
     * @return    boolean
     */
    private function makeDir($folder)
    {
        $reval = false;

        if (!file_exists($folder)) {

            /* 如果目錄不存在則嘗試創建該目錄 */
            @umask(0);

            /* 將目錄路徑拆分成數組 */
            preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

            /* 如果第一個字符為/則當作物理路徑處理 */
            $base = ($atmp[0][0] == '/') ? '/' : '';

            /* 遍歷包含路徑信息的數組 */
            foreach ($atmp[1] as $val) {

                if ('' != $val) {

                    $base .= $val;
                    if ('..' == $val || '.' == $val) {

                        /* 如果目錄為.或者..則直接補/繼續下一個循環 */
                        $base .= '/';
                        continue;
                    }

                } else {

                    continue;
                }

                $base .= '/';

                if (!@file_exists($base)) {

                    /* 嘗試創建目錄，如果創建失敗則繼續循環 */
                    if (@mkdir($base, 0777)) {

                        @chmod($base, 0777);
                        $reval = true;
                    }
                }
            }

        } else {

            /* 路徑已經存在。返回該路徑是不是一個目錄 */
            $reval = is_dir($folder);
        }

        clearstatcache();

        return $reval;
    }

    /**
     *  產生指定目錄不重複的檔案名稱
     *
     * @access    private
     *
     * @param     string    $dir    要檢查是否有同名檔案的目錄
     *
     * @return    string
     */
    private function uniqueName($dir)
    {
        $filename = '';
        while (empty($filename)) {

            $filename = $this->randomFileName();

            if (
                file_exists($dir . $filename . '.jpg') ||
                file_exists($dir . $filename . '.gif') ||
                file_exists($dir . $filename . '.png') ||
                file_exists($dir . $filename . '.bmp')
            ) {

                $filename = '';
            }
        }

        return $filename;
    }

    /**
     * 產生隨機的數字串
     *
     * @access    private
     *
     * @return    string
     */
    private function randomFileName()
    {
        $str = '';
        for ($i = 0; $i < 9; $i++) {

            $str .= mt_rand(0, 9);
        }

        return time() . $str;
    }

    /**
     * 回傳檔案副檔名
     *
     * @access    private
     *
     * @param     string    $path    檔案名稱或路徑
     *
     * @return    string
     */
    private function getFileType($path)
    {
        $pos = strrpos($path, '.');

        return ($pos !== false) ? substr($path, $pos) : '';
    }

    /**
     * 檢查圖片處理能力
     *
     * @access    private
     *
     * @param     string    $mimeType   圖片類型
     *
     * @return    void
     */
    private function checkImageFunction($mimeType)
    {
        $imgType = $this->getExtbyType($mimeType);

        switch ($imgType) {

            case 'gif':

                return function_exists('imagecreatefromgif');
                break;

            case 'jpeg':

                return function_exists('imagecreatefromjpeg');
                break;

            case 'png':

                return function_exists('imagecreatefrompng');
                break;

            case 'bmp':

                return function_exists('imagecreatefrombmp');
                break;

            default:

                return false;
        }
    }

    /**
     * 檢查圖片類型
     *
     * @access    private
     *
     * @param     string     $imgType    圖片類型
     *
     * @return    boolean
     */
    private function checkImageType($imgType)
    {
        return $imgType == 'image/gif' ||
               $imgType == 'image/x-png' ||
               $imgType == 'image/png' ||
               $imgType == 'image/jpg' ||
               $imgType == 'image/pjpeg' ||
               $imgType == 'image/jpeg' ||
               $imgType == 'image/bmp' ||
               $imgType == 'image/x-ms-bmp';
    }

    public function getFullPath()
    {
        return $this->properties['full_path'];
    }

    public function getFileExt()
    {
        return $this->properties['file_ext'];
    }

    /**
     * 顯示錯誤訊息
     *
     * @access    public
     *
     * @param     string    $open
     * @param     string    $close
     *
     * @return    string
     */
    public function displayErrors($open = '', $close = '')
    {
        $str = '';
        foreach ($this->error as $val) {

            $str .= $open . $val['error'] . $close;
        }

        return $str;
    }
}

if (!function_exists('imagecreatefrombmp')) {
    /**
     * Create a new image from GD file or URL
     *
     * @param     string      $filename    Path to the GD file
     *
     * @return    resource                 success: image resource identifier
     *                                     errors: false
     */
    function imagecreatefrombmp($filename)
    {
        if (!$f1 = fopen($filename, 'rb')) {

            return false;
        }

        $file = unpack('vfile_type/Vfile_size/Vreserved/Vbitmap_offset', fread($f1, 14));
        if ($file['file_type'] != 19778) {

            return false;
        }

        $bmp = unpack(
            'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
            '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
            '/Vvert_resolution/Vcolors_used/Vcolors_important',
            fread($f1, 40)
        );
        $bmp['colors'] = pow(2, $bmp['bits_per_pixel']);

        if ($bmp['size_bitmap'] == 0) {

            $bmp['size_bitmap'] = $file['file_size'] - $file['bitmap_offset'];
        }

        $bmp['bytes_per_pixel'] = $bmp['bits_per_pixel'] / 8;
        $bmp['bytes_per_pixel2'] = ceil($bmp['bytes_per_pixel']);
        $bmp['decal'] = ($bmp['width'] * $bmp['bytes_per_pixel'] / 4);
        $bmp['decal'] -= floor($bmp['width'] * $bmp['bytes_per_pixel'] / 4);
        $bmp['decal'] = 4 - (4 * $bmp['decal']);
        if ($bmp['decal'] == 4) {

            $bmp['decal'] = 0;
        }

        $palette = array();
        if ($bmp['colors'] < 16777216){

            $palette = unpack('V' . $bmp['colors'], fread($f1, $bmp['colors'] * 4));
        }

        $img = fread($f1, $bmp['size_bitmap']);
        $vide = chr(0);

        $res = imagecreatetruecolor($bmp['width'], $bmp['height']);
        $p = 0;
        $y = $bmp['height'] - 1;
        while ($y >= 0) {

            $x = 0;

            while($x < $bmp['width']) {

                if ($bmp['bits_per_pixel'] == 32) {

                    $color = unpack('V', substr($img, $p, 3));

                    $b = ord(substr($img, $p, 1));
                    $g = ord(substr($img, $p + 1, 1));
                    $r = ord(substr($img, $p + 2, 1));
                    $color = imagecolorexact($res, $r, $g, $b);

                    if ($color == -1) {

                        $color = imagecolorallocate($res, $r, $g, $b);
                    }

                    $color[0] = $r * 256 * 256 + $g * 256 + $b;
                    $color[1] = $color;

                } elseif ($bmp['bits_per_pixel'] == 24) {

                    $color = unpack('V', substr($img, $p, 3) . $vide);

                } elseif ($bmp['bits_per_pixel'] == 16) {

                    $color = unpack('n', substr($img, $p, 2));
                    $color[1] = $palette[$color[1] + 1];

                } elseif ($bmp['bits_per_pixel'] == 8) {

                    $color = unpack('n', $vide . substr($img, $p, 1));
                    $color[1] = $palette[$color[1] + 1];

                } elseif ($bmp['bits_per_pixel'] == 4) {

                    $color = unpack('n', $vide . substr($img, floor($p), 1));

                    if (($p * 2) % 2 == 0) {

                        $color[1] = ($color[1] >> 4);

                    } else {

                        $color[1] = ($color[1] & 0x0f);
                    }

                    $color[1] = $palette[$color[1] + 1];

                } elseif ($bmp['bits_per_pixel'] == 1) {

                    $color = unpack('n', $vide . substr($img, floor($p), 1));

                    if (($p * 8) % 8 == 0) {

                        $color[1] = $color[1] >> 7;

                    } elseif (($p * 8) % 8 == 1) {

                        $color[1] = ($color[1] & 0x40) >> 6;

                    } elseif (($p * 8) % 8 == 2) {

                        $color[1] = ($color[1] & 0x20) >> 5;

                    } elseif (($p * 8) % 8 == 3) {

                        $color[1] = ($color[1] & 0x10) >> 4;

                    } elseif (($p * 8) % 8 == 4) {

                        $color[1] = ($color[1] & 0x8) >> 3;

                    } elseif (($p * 8) % 8 == 5) {

                        $color[1] = ($color[1] & 0x4) >> 2;

                    } elseif (($p * 8) % 8 == 6) {

                        $color[1] = ($color[1] & 0x2) >> 1;

                    } elseif (($p * 8) % 8 == 7) {

                        $color[1] = ($color[1] & 0x1);
                    }

                    $color[1] = $palette[$color[1] + 1];

                } else {

                    return false;
                }

                imagesetpixel($res, $x, $y, $color[1]);

                $x++;
                $p += $bmp['bytes_per_pixel'];
            }

            $y--;
            $p += $bmp['decal'];
        }
        fclose($f1);

        return $res;
    }
}

if (!function_exists('imagebmp')) {

    /**
     * 輸出 BMP 格式圖片至瀏覽器或檔案
     *
     * @param     resource    $image          圖片資源
     * @param     string      $filename       另存新檔的檔案名稱，如果為空則直接在瀏覽器輸出
     * @param     integer     $bit            位元深度
     * @param     integer     $compression    壓縮方式
     *                                        0: 不壓縮
     *                                        1: 使用 RLE8 壓縮演算法進行壓縮
     *
     * @return    boolean
     */
    function imagebmp(&$image, $filename = '', $bit = null, $compression = 0)
    {
        if (!in_array($bit, array(1, 4, 8, 16, 24, 32))) {

            $bit = 8;

        } else if ($bit == 32) {

            $bit = 24;
        }
        $bits = pow(2, $bit);

        // 調整調色板
        imagetruecolortopalette($image, true, $bits);
        $width = imagesx($image);
        $height = imagesy($image);
        $colorsNum = imagecolorstotal($image);

        if ($bit <= 8) {

            // 顏色索引
            $rgbQuad = '';
            for ($i = 0; $i < $colorsNum; $i ++) {

                $colors = imagecolorsforindex($image, $i);
                $rgbQuad .= chr($colors['blue']) . chr($colors['green']) . chr($colors['red']) . 0x00;
            }

            // 點陣圖資料
            $bmpData = '';
            if ($compression == 0 || $bit < 8) { // 非壓縮

                if (!in_array($bit, array(1, 4, 8))) {

                    $bit = 8;
                }
                $compression = 0;

                // 每行字元數必須為 4 的倍數，補齊
                $extra = '';
                $padding = 4 - ceil($width / (8 / $bit)) % 4;
                if ($padding % 4 != 0) {

                    $extra = str_repeat(0x00, $padding);
                }

                for ($j = $height - 1; $j >= 0; $j --) {

                    $i = 0;

                    while ($i < $width){

                        $bin = 0;
                        $limit = $width - $i < 8 / $bit ? (8 / $bit - $width + $i) * $bit : 0;

                        for ($k = 8 - $bit; $k >= $limit; $k -= $bit) {

                            $index = imagecolorat($image, $i, $j);
                            $bin |= $index << $k;
                            $i ++;
                        }

                        $bmpData .= chr($bin);
                    }

                    $bmpData .= $extra;
                }

            } else if ($compression == 1 && $bit == 8) { // RLE8 壓縮

                for ($j = $height - 1; $j >= 0; $j--) {

                    $lastIndex = 0x00;
                    $sameNum = 0;

                    for ($i = 0; $i <= $width; $i ++) {

                        $index = imagecolorat($image, $i, $j);

                        if ($index !== $lastIndex || $sameNum > 255) {

                            if ($sameNum != 0) {
                                $bmpData .= chr($sameNum) . chr($lastIndex);
                            }

                            $lastIndex = $index;
                            $sameNum = 1;

                        } else {

                            $sameNum++;
                        }
                    }

                    $bmpData .= 0x00 . 0x00;
                }

                $bmpData .= 0x00 . 0x01;
            }

            $sizeQuad = strlen($rgbQuad);
            $sizeData = strlen($bmpData);

        } else {

            // 每行字元數必須為 4 的倍數，補齊
            $extra = '';
            $padding = 4 - ($width * ($bit / 8)) % 4;

            if ($padding % 4 != 0){

                $extra = str_repeat(0x00, $padding);
            }

            // 點陣圖資料
            $bmpData = '';
            for ($j = $height - 1; $j >= 0; $j--) {

                for ($i = 0; $i < $width; $i ++) {

                    $index = imagecolorat($image, $i, $j);
                    $colors = imagecolorsforindex($image, $index);

                    if ($bit == 16) {

                        $bin = 0 << $bit;
                        $bin |= ($colors['red'] >> 3) << 10;
                        $bin |= ($colors['green'] >> 3) << 5;
                        $bin |= $colors['blue'] >> 3;
                        $bmpData .= pack('v', $bin);

                    } else {

                        $bmpData .= pack('c*', $colors['blue'], $colors['green'], $colors['red']);
                    }
                }

                $bmpData .= $extra;
            }

            $sizeQuad = 0;
            $sizeData = strlen($bmpData);
            $colorsNum = 0;
            $compression = 0;
        }

        // 點陣圖檔案標頭
        $fileHeader = 'BM' . pack('V3', 54 + $sizeQuad + $sizeData, 0, 54 + $sizeQuad);
        // 點陣圖檔案標頭資訊
        $infoHeader = pack('V3v2V*', 0x28, $width, $height, 1, $bit, $compression, $sizeData, 0, 0, $colorsNum, 0);

        // 寫入檔案
        if ($filename != '') {

            $fp = fopen($filename, 'wb');

            fwrite($fp, $fileHeader);
            fwrite($fp, $infoHeader);
            fwrite($fp, $rgbQuad);
            fwrite($fp, $bmpData);
            fclose($fp);

            return 1;
        }

        // 瀏覽器輸出
        header('Content-Type: image/bmp');
        echo $fileHeader . $infoHeader;
        echo $rgbQuad;
        echo $bmpData;

        return 1;
    }
}
