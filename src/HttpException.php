<?php

namespace Phalpro;

use Phalcon\Exception as PhalconException;
use Phalcon\Http\Response as Response;

/**
 * Http Exception
 *
 * @category Class
 * @package  Phalpro
 * @author   YuTin <yuting1987@gmail.com>
 */
class HttpException extends PhalconException
{
    const MISSING_REQUIRED_FIELD       = 40010;
    const PARAMETER_FORMAT_NOT_CORRECT = 40020;
    const APPKEY_IS_NOT_VALID          = 40110;
    const SSO_TOKEN_IS_NOT_VALID       = 40120;
    const ACCESS_FORBIDDEN             = 40310;
    const OPERATION_IS_FORBIDDEN       = 40320;
    const NOT_FOUND                    = 40400;
    const URL_WAS_NOT_FOUND            = 40410;
    const RESOURCE_NOT_FOUND           = 40420;
    const UNKNOWN_ERROR                = 50000;
    const DATA_PROCESSING_ERROR        = 50010;
    const SERVICE_UNAVAILABLE          = 50300;
    const OVERLOAD_SERVICE             = 50310;

    protected $httpCode = 500;

    public static $errorAry = [
        40010 => '缺少必要欄位',
        40020 => '參數格式不合法',
        40110 => 'APPKEY驗證失敗',
        40120 => 'SSOToken驗證失敗',
        40310 => '拒絕訪問',
        40320 => '拒絕操作',
        40400 => '找不到對應服務',
        40410 => '找不到URL資源',
        40420 => '資料不存在',
        50000 => '未知的錯誤',
        50010 => '資料處理錯誤',
        50300 => '停止服務',
        50310 => '伺服器過載',
    ];

    public static $headerAry = [
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Forbidden",
        500 => "Internal Server Error"
    ];
    
    /**
     * Exception Init
     * 
     * @param string  $message 錯誤訊息
     * @param integer $code    錯誤代碼
     *
     * @return void
     */
    public function __construct($message, $code = UNKNOWN_ERROR)
    {
        $this->code = $code;

        if (is_array($message)) {
            $this->message = implode(", ", $message);
        } else {
            $this->message = $message;
        }
    }

    /**
     * 取得預設Message
     *
     * @return string
     */
    public function getDefinedMessage()
    {
        if (isset(self::$errorAry[$this->code])) {
            $message = $errorAry[$code];
        } else {
            $message = 'An unknown error has occurred';
        }

        return $message;
    }

    /**
     * 取得Http Status Code
     * 
     * @return int
     */
    public function getHttpCode()
    {
        if (preg_match('/^\d\d\d/', $this->code, $match)) {
            return $match[0];
        } else {
            return 500;
        }
    }

    /**
     * 處理錯誤資訊
     * 
     * @param Exception $e Exception物件
     * 
     * @return Object
     */
    public static function handle($e)
    {
        $result = [
            'code' => self::UNKNOWN_ERROR,
            'message' => 'An unknown error has occurred',
            'detail' => null,
            'httpCode' => 500
        ];

        switch (get_class($e)) {
            case "Phalpro\HttpException":
                $result['code']     = $e->getCode();
                $result['message']  = $e->getDefinedMessage();
                $result['detail']   = $e->getMessage();
                $result['httpCode'] = $e->getHttpCode();
                break;
            case "PDOException":
            case "Phalcon\Mvc\Model\Transaction\Failed":
                $result['code']    = self::DATA_PROCESSING_ERROR;
                $result['message'] = self::$errorAry[self::DATA_PROCESSING_ERROR];
                $result['detail']  = $e->getMessage();
                break;
            default:
                if (get_cfg_var("APPLICATION_ENV") == "development") {
                    $result['detail'] = "{$e->getFile()}\n [{$e->getLine()}] {$e->getMessage()}";
                }
                
                mail(
                    'yuting_liu@hiiir.com',
                    '[System Error]Ship未知的錯誤',
                    "{$e->getFile()}\n [{$e->getLine()}] {$e->getMessage()}"
                );
                break;
        }//end switch

        return $result;
    }

    /**
     * 處理錯誤資訊 for JSON
     * 
     * @param Exception $e        Exception物件
     * @param Response  $response Phalcon Response Object
     * 
     * @return void
     */
    public static function handleJson($e, $response)
    {
        if (!$response) {
            $response = new Response();
        }

        $result = self::handle($e);

        $response->setStatusCode(
            $result['httpCode'],
            self::$headerAry[$result['httpCode']]
        );

        $response->setJsonContent(
            [
                'status' => 'fail',
                'code' => $result['code'],
                'message' => $result['message'],
                'detail' => $result['detail']
            ]
        );

        $response->send();
    }
}
?>