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
    const HTTP_400_1 = '缺少必要欄位';
    const HTTP_400_2 = '參數格式不合法';

    const HTTP_401_1 = 'APPKEY驗證失敗';
    const HTTP_401_2 = 'SSOToken驗證失敗';

    const HTTP_402_1 = '拒絕訪問';
    const HTTP_402_2 = '拒絕操作';

    const HTTP_404   = '找不到對應服務';
    const HTTP_404_1 = '資料不存在';

    const HTTP_500 = '未知的錯誤';

    const HTTP_500_1 = '資料庫錯誤';

    const HTTP_503_2 = '請求的資料超過可負載的數量';
    const HTTP_503_3 = '資料取得失敗';

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
    
    protected $httpCode = 500;

    protected $exMessage = null;

    /**
     * Exception Init
     * 
     * @param string  $code      錯誤代碼 HTTP_XXX_XX
     * @param string  $exMessage 錯誤細節
     * @param integer $httpCode  Http Status Code
     */
    public function __construct($code, $exMessage = null, $httpCode = 500)
    {
        $this->code = $code;

        $this->message = self::getDefinedMessage($code);

        if (is_array($exMessage)) {
            $this->exMessage = implode(", ", $exMessage);
        } elseif (is_string($exMessage)) {
            $this->exMessage = $exMessage;
        }
    }

    /**
     * 取得預設Message
     *
     * @param string $code 錯誤代碼
     *
     * @return string
     */
    public static function getDefinedMessage($code)
    {
        $message = @constant('self::' . $code);

        if (!$message) {
            $message = 'An unknown error has occurred';
        }

        return $message;
    }

    /**
     * 取得錯誤細節
     * 
     * @return string
     */
    public function getExMessage()
    {
        return $this->exMessage;
    }

    /**
     * 取得Http Status Code
     * 
     * @return int
     */
    public function getHttpCode()
    {
        return $this->httpCode;
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
            'code' => 'HTTP_500',
            'message' => null,
            'extMessage' => null,
            'httpCode' => 500
        ];

        switch (get_class($e)) {
            case "Phalpro\HttpException":
            case "Phalpro\HttpException\ClientException":
            case "Phalpro\HttpException\ServerException":
                $result['code']       = $e->getCode();
                $result['extMessage'] = $e->getExMessage();
                $result['httpCode']   = $e->getHttpCode();
                break;
            case "PDOException":
            case "Phalcon\Mvc\Model\Transaction\Failed":
                $result['code']       = 'HTTP_500_1';
                $result['extMessage'] = $e->getMessage();
                break;
            default:
                $result['code'] = 'HTTP_500';
                
                if (get_cfg_var("APPLICATION_ENV") == "development") {
                    $result['extMessage'] = "{$e->getFile()}\n [{$e->getLine()}] {$e->getMessage()}";
                }

                mail(
                    'yuting_liu@hiiir.com',
                    '[System Error]Ship未知的錯誤',
                    "{$e->getFile()}\n [{$e->getLine()}] {$e->getMessage()}"
                );
                break;
        }//end switch

        $result['message'] = self::getDefinedMessage($result['code']);

        // if ( is_dir(__DIR__ . '/../../../logs/') == FALSE ) {
        //     mkdir(__DIR__ . '/../../../logs/');
        // }
        // $logDir = realpath(__DIR__ . '/../../../logs/');

        // $fp = fopen( $logDir . '/' . date('Y-m-d') . '.log', "a");
        // fwrite($fp, '[' . date('H:i:s') . '] ');
        // fwrite($fp, get_class($e));
        // fwrite($fp, PHP_EOL . 'message: ' . $this->message);
        // fwrite($fp, PHP_EOL . 'exMessage: ' . $this->exMessage);
        // fwrite($fp, PHP_EOL . PHP_EOL);
        // fclose($fp);

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
                'extMessage' => $result['extMessage']
            ]
        );

        $response->send();
    }
}
?>