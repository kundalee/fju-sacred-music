<?php

class SMSHttp
{
    public $respondUrl = '';

    private $smsHost = 'smsb2c.mitake.com.tw';

    private $sendSMSUrl;

    private $getCreditUrl;

    private $username;

    private $password;

    public $statusCode = array(
        '0' => '預約傳送中',
        '1' => '已送達業者',
        '2' => '已送達業者',
        '4' => '已送達⼿機',
        '5' => '內容有錯誤',
        '6' => '⾨號有錯誤',
        '7' => '簡訊已停⽤',
        '8' => '逾時無送達',
        '9' => '預約已取消',
        '*' => '系統發⽣錯誤，請聯絡三⽵資訊窗⼝⼈員',
        'a' => '簡訊發送功能暫時停⽌服務，請稍候再試',
        'b' => '簡訊發送功能暫時停⽌服務，請稍候再試',
        'c' => '請輸入帳號',
        'd' => '請輸入密碼',
        'e' => '帳號、密碼錯誤',
        'f' => '帳號已過期',
        'h' => '帳號已被停⽤',
        'k' => '無效的連線位址',
        'l' => '帳號已達到同時連線數上限',
        'm' => '必須變更密碼，在變更密碼前，無法使⽤簡訊發送服務',
        'n' => '密碼已逾期，在變更密碼前，將無法使⽤簡訊發送服務',
        'p' => '沒有權限使⽤外部Http程式',
        'r' => '系統暫停服務，請稍後再試',
        's' => '帳務處理失敗，無法發送簡訊',
        't' => '簡訊已過期',
        'u' => '簡訊內容不得為空⽩',
        'v' => '無效的⼿機號碼',
        'w' => '查詢筆數超過上限',
        'x' => '發送檔案過⼤，無法發送簡訊',
        'y' => '參數錯誤',
        'z' => '查無資料'
    );

    /**
     * 取得帳號餘額
     *
     * @param     string    $username    帳號
     * @param     string    $password    密碼
     *
     * @return.   void
     */
    function __construct($username, $password)
    {
        $this->username = $username;

        $this->password = $password;

        $this->sendSMSUrl = 'http://' . $this->smsHost . '/b2c/mtk/SmSend?';

        $this->getCreditUrl = 'http://' . $this->smsHost . '/b2c/mtk/SmQuery?';
    }

    /**
     * 取得帳號餘額
     *
     * @return    boolean    true:取得成功；false:取得失敗
     */
    function getCredit()
    {
        $postDataString = 'username=' . $this->username . '&password=' . $this->password;

        return $this->httpPost($this->getCreditUrl, $postDataString);
    }

    /**
     * 傳送簡訊
     *
     * @param     string    $subject     簡訊主旨，主旨不會隨著簡訊內容發送出去。用以註記本次發送之用途。可傳入空字串
     * @param     string    $content     簡訊發送內容
     * @param     string    $mobile      接收人之手機號碼
     * @param     string    $sendTime    簡訊預定發送時間。-立即發送：請傳入空字串。-預約發送：請傳入預計發送時間，若傳送時間小於系統接單時間，將不予傳送。格式為YYYYMMDDhhmnss；例如:預約2009/01/31 15:30:00發送，則傳入20090131153000。若傳遞時間已逾現在之時間，將立即發送
     *
     * @return    mixed                傳送成功:array；傳送失敗:false
     */
    function sendSMS($subject, $content, $mobile, $sendTime = '', $urlParams = array())
    {
        $urlParams = array_merge(
            array(
                'username' => $this->username,
                'password' => $this->password,
                'dstaddr' => $mobile, // * 收訊⼈之⼿機號碼
                'smbody' => urlencode($content), // * 簡訊內容
                'destname' => urlencode($subject), // 收訊⼈名稱
                'dlvtime' => $sendTime, // 簡訊預約時間
                'vldtime' => '', // 簡訊有效期限
                'response' => $this->respondUrl, // 狀態主動回報網址
                'clientid' => '', // 客⼾簡訊ID
                'CharsetURL' => 'UTF-8', // 編碼⽅式
                'objectID' => '' // 批次名稱
            ),
            $urlParams
        );

        if ($urlParams['clientid'] == '') {

            $urlParams['clientid'] = md5(json_encode(array('mobile' => $urlParams['dstaddr'], 'content' => $urlParams['smbody']), JSON_UNESCAPED_UNICODE));
        }

        foreach ($urlParams as $key => $val) {

            $urlString[] = $key . '=' . $val;
        }

        return $this->httpPost($this->sendSMSUrl, implode('&', $urlString));
    }

    function httpPost($url, $urlParams)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url . $urlParams,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        );
        curl_setopt_array($ch, $options);
        $curlRes = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        $curlInfo['errno'] = curl_errno($ch);
        curl_close($ch);

        if ($curlInfo['errno'] == 0 && $curlInfo['http_code'] == '200') {

            foreach (explode(PHP_EOL, trim($curlRes)) as $row) {

                list($key, $val) = explode('=', $row, 2);

                $key = trim($key);
                $val = trim($val);

                $params[$key] = $val;

                if ($key == 'statuscode' && isset($this->statusCode[$val])) {

                    $params['statusdesc'] = $this->statusCode[$val];
                }
            }

            if (!empty($params)) {

                return $params;
            }
        }

        return false;
    }
}
