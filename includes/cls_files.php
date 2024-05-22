<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 *
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

class cls_files
{
    var $dirList = array();

    /**
     * 讀取文件
     *
     * @param      boolean    $exit
     * @param      string     $file
     *
     * @return     string
     */
    function read($file, $exit = true)
    {
        return MooReadFile($file, $exit);
    }

    /**
     * 存儲數據
     *
     * @param     string     $file
     * @param     string     $content
     * @param     string     $mod
     * @param     boolean    $exit
     *
     * @return    boolean
     */
    function write($file, $content, $mod = 'w', $exit = true)
    {
        return MooWriteFile($file, $content, $mod, $exit);
    }

    /**
     * 刪除操作
     *
     * @param     string    $file
     *
     * @return    boolean
     */
     function fileDelete($folder)
     {
        if (is_file($folder) && file_exists($folder)) {

            unlink($folder);
        }
        if (is_dir($folder)) {

          $handle = opendir($folder);
            while(false !== ($myFile = readdir($handle))) {

                if ($myFile != '.' && $myFile != '..') {

                    $this->fileDelete($folder.'/'.$myFile);
                }
            }
            closedir($handle);
            rmdir($folder);
        }
        unset($folder);
        return true;
    }

    /**
     * 創建文件或目錄
     *
     * @param     string    $file
     * @param     string    $type
     *
     * @return    boolean
     */
    function fileMake($file, $type = 'dir')
    {
        $array = explode('/', $file);
        $count = count($array);
        $msg = '';
        if ($type == 'dir') {

            for ($i = 0; $i < $count; $i++) {

                $msg .= $array[$i];
                if (!file_exists($msg) && ($array[$i])) {

                    mkdir($msg, 0777);
                }
                $msg .= '/';
            }

        } else {

            for ($i = 0; $i < ($count-1); $i++) {

                $msg .= $array[$i];
                if (!file_exists($msg) && ($array[$i])) {

                    mkdir($msg, 0777);
                }
                $msg .= '/';
            }
            global $systemTime;
            $theTime = $systemTime ? $systemTime : time();
            //note:創建文件
            @touch($file, $theTime);
            unset($theTime);
        }
        unset($msg, $file, $type, $count, $array);
        return true;
    }

    /**
     * 複製操作
     *
     * @param     string     $old
     * @param     string     $new
     * @param     boolean    $recover
     *
     * @return    boolean
     */
    function fileCopy($old, $new, $recover = true)
    {
        if (substr($new, -1) == '/') {

            $this->fileMake($new, 'dir');

        } else {

            $this->fileMake($new, 'file');
        }
        if (is_file($new)) {

            if ($recover) {

                unlink($new);
            } else {

                return false;
            }

        } else {

            $new = $new.basename($old);
        }
        copy($old, $new);
        unset($old, $new, $recover);
        return true;
    }

    /**
     * 文件移動操作
     *
     * @param     string     $old
     * @param     string     $new
     * @param     boolean    $recover
     *
     * @return    boolean
     */
    function fileMove($old, $new, $recover = true)
    {
        if (substr($new, -1) == '/') {

            $this->fileMake($new, 'dir');

        } else {

            $this->fileMake($new, 'file');
        }
        if (is_file($new)) {

            if ($recover) {

                unlink($new);

            } else {

                return false;
            }

        } else {

            $new = $new.basename($old);
        }
        rename($old, $new);
        unset($old, $new, $recover);
        return true;
    }

    /**
     * 獲取文件夾列表
     *
     * @param     string     $folder
     * @param     boolean    $isSubDir
     *
     * @return    array
     */
    function getDirList($folder, $isSubDir = false)
    {
        $this->dirList = array();
        if (is_dir($folder)) {

            $handle = opendir($folder);
            while(false !== ($myFile = readdir($handle))) {

                if ($myFile != '.' && $myFile != '..') {

                    $this->dirList[] = $myFile;
                    if ($isSubDir && is_dir($folder . '/' . $myFile)) {

                        $this->getDirList($folder . '/' . $myFile, $isSubDir);
                    }
                }
            }
            closedir($handle);
            unset($folder, $isSubDir);
            return $this->dirList;
        }
        return $this->dirList;
    }

    /**
     * 打開文件
     *
     * @param      string   $file
     * @param      string   $type
     *
     * @return    resource
     */
    function fileOpen($file, $type = 'wb')
    {
        $handle = fopen($file, $type);
        return $handle;
    }

    /**
     * 關閉指針
     *
     * @param    resource    $handle
     *
     * @return   boolean
     */
    function fileClose($handle)
    {
        return fclose($handle);
    }
}
