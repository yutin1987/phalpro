<?php
namespace Phalpro;

use \Phalcon\Mvc\Controller;

use \Phalpro\Validator;
use \Phalpro\Page;

/**
 * Restful Controller
 *
 * @category Controller
 * @package  Phalpro
 * @author   YuTin <yuting1987@gmail.com>
 */
class RestfulController extends Controller
{
    const HTTP_SUCCESS_OK            = "200";
    const HTTP_SUCCESS_CREATED       = "201";
    const HTTP_SUCCESS_ACCEPTED      = "202";
    const HTTP_SUCCESS_NO_CONTENT    = "204";
    const HTTP_SUCCESS_RESET_CONTENT = "205";

    const HTTP_CLIENT_ERROR_BAD          = "400";
    const HTTP_CLIENT_ERROR_UNAUTHORIZED = "401";
    const HTTP_CLIENT_ERROR_FARBIDDEN    = "403";
    const HTTP_CLIENT_ERROR_NOT_FOUND    = "404";
    const HTTP_CLIENT_ERROR_CONFLICT     = "404";
    const HTTP_CLIENT_ERROR_GONE         = "404";

    const HTTP_SERVER_ERROR_INTERNAL    = "500";
    const HTTP_SERVER_ERROR_UNAVAILABLE = "503";

    public static $headerAry = [
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        500 => "Internal Server Error",
        503 => "Service Unavailable"
    ];

    protected $methods = [
        'GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'OPTIONS'
    ];

    protected $rawBody = null;

    protected $validatorDir = '';

    protected $errorMessage = array();

    protected $contentType = 'json';

    protected $viewer;

    /**
     * Before Execute Route
     * 
     * @param object $dispatcher dispatcher
     * 
     * @return void
     */
    public function beforeExecuteRoute($dispatcher)
    {
        $this->response->setHeader(
            "Access-Control-Allow-Origin",
            "*"
        );
        
        $this->response->setHeader(
            "Access-Control-Allow-Headers",
            "Content-Type, APPKEY, AUTHORIZATION"
        );
        
        $this->setMethods();
        $this->setContentType();
    }

    public function afterExecuteRoute($dispatcher)
    {
        // $this->response->send();
    }

    /**
     * Set methods
     * 
     * @param array $methods http methods
     *
     * @return void
     */
    protected function setMethods($methods = null)
    {
        if (!empty($methods)) {
            $this->methods = $methods;
        }

        $this->response->setHeader(
            "Access-Control-Allow-Methods",
            implode(",", $this->methods)
        );
    }

    /**
     * 設定contentType
     * 
     * @param string $type 內容格式
     *
     * @return void
     */
    protected function setContentType($type = null)
    {
        if (empty($type)) {
            $this->contentType = 'json';
        }

        if ($this->contentType == 'xml') {
            $this->response->setContentType("application/xml", "UTF-8");
        } elseif ($this->contentType == 'html') {
            $this->response->setContentType("text/html", "UTF-8");
        } else {
            $this->response->setContentType("application/json", "UTF-8");
        }
    }

    /**
     * 設定viewer
     * 
     * @param string $temp 樣板檔
     *
     * @return void
     */
    protected function setViewer($temp)
    {
        $this->viewer = $temp;
    }

    /**
     * Response Success
     * 
     * @param array   $data     data
     * @param integer $httpCode http status code
     * 
     * @return void
     */
    protected function resSuccess($data = null, $httpCode = 200)
    {
        if ($this->contentType == 'xml') {
            $xmlObj = new SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8" ?>'
            );

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if (!is_numeric($key)) {
                        $subnode = $xmlObj->addChild($key);
                        self::array2xml($value, $subnode);
                    } else {
                        self::array2xml($value, $xmlObj);
                    }
                } else {
                    $xmlObj->addChild($key, $value);
                }
            }

            $this->response->setContent(
                $xmlObj->asXML()
            );
        } elseif ($this->contentType == 'html') {
            $this->response->setContent(
                $this->view->render($this->viewer, $data)
            );
        } else {
            $this->response->setJsonContent($data);
        }//end if

        $this->response->setStatusCode(
            $httpCode,
            self::$headerAry[$httpCode]
        );
    }

    /**
     * 設定validator目錄
     * 
     * @param string $path 目錄
     *
     * @return void
     */
    protected function setValidatorDir($path)
    {
        $this->validatorDir = $path;
    }

    /**
     * Use JSON Schema validation data
     * 
     * @param mixed  $data   data
     * @param string $schema json schema
     * 
     * @return mixed
     */
    protected function validate($data, $schema)
    {
        $validator = new Validator($this->validatorDir);
        
        $report = $validator->validate($data, $schema);

        if ($validator->getMessage()) {
            array_push(
                $this->errorMessage,
                $validator->getMessage()
            );
        };

        return $report;
    }

    /**
     * Get Request From Query
     * 
     * @param string $schema json schema
     * 
     * @return mixed
     */
    protected function getQuery($schema)
    {
        if ($this->validate($_GET, $schema)) {
            return $_GET;
        } else {
            return false;
        }
    }

    /**
     * Get Request From Post
     * 
     * @param string $schema json schema
     * 
     * @return mixed
     */
    protected function getPost($schema)
    {
        if ($this->validate($_POST, $schema)) {
            return $_POST;
        } else {
            return false;
        }
    }

    /**
     * Get Request From RawBody For Json
     * 
     * @param string $schema json schema
     * 
     * @return mixed
     */
    protected function getJsonRawBody($schema)
    {
        $rawBody = $this->request->getJsonRawBody();
        if (empty($rawBody)) {
            array_push($this->errorMessage, 'Not found raw body');
            return false;
        } elseif ($this->validate($rawBody, $schema)) {
            return $rawBody;
        } else {
            return false;
        }
    }

    /**
     * Get Request From RawBody For Query
     * 
     * @param string $schema json schema
     * 
     * @return mixed
     */
    protected function getQueryRawBody($schema)
    {
        $rawBody = $this->request->getRawBody();
        if (empty($rawBody)) {
            array_push($this->errorMessage, 'Not found raw body');
            return false;
        } else {
            parse_str($rawBody, $data);
            if ($this->validate($data, $schema)) {
                return $data;
            } else {
                return false;
            }
        }
    }

    /**
     * Get Page
     * 
     * @return Page
     */
    protected function getPage()
    {
        $current = intval($_GET['currentPage']);
        $size    = intval($_GET['pageSize']);

        return new Page($size, $current);
    }
}
?>