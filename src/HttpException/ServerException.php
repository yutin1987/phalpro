<?php

namespace Phalpro\HttpException;

use Phalpro\HttpException as HttpException;

/**
 * Http Exception for Client
 *
 * @category Class
 * @package  Register
 * @author   YuTin <yuting1987@gmail.com>
 */
class ServerException extends HttpException
{
    protected $httpCode = 500;

    /**
     * 建立Exception
     * 
     * @param string  $code      錯誤代碼
     * @param string  $exMessage 詳細訊息
     * @param integer $httpCode  Http Status Code
     */
    public function __construct($code, $exMessage = null, $httpCode = 500)
    {
        parent::__construct($code, $exMessage, $httpCode);

    }
}
?>