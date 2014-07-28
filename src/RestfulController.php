<?php
namespace Phalpro;

use \Phalcon\Mvc\Controller;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\RefResolver;

use Page;

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

    protected $methods = [
        'GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'OPTIONS'
    ];

    protected $rawBody = null;

    protected $errorMessage = array();

    protected $contentType = 'json';

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
    protected function setMethods($methods)
    {
        $this->methods = $methods;
        $this->response->setHeader(
            "Access-Control-Allow-Methods",
            implode(",", $methods)
        );
    }

    /**
     * Response Success
     * 
     * @param array   $data     data
     * @param string  $type     format type
     * @param integer $httpCode http status code
     * 
     * @return void
     */
    protected function resSuccess($data = null, $type = 'json', $httpCode = 200)
    {
        if ($type == 'josn' || empty($json)) {
            $this->response->setContentType("application/json", "UTF-8");
            $this->response->setJsonContent($data);
        } elseif ($type == 'xml') {
            $this->response->setContentType("application/xml", "UTF-8");
        } else {
            $this->response->setContentType("text/html", "UTF-8");
            $this->response->setContent($this->view->render($type, $data));
        }
        
        $this->response->setStatusCode($httpCode, "OK");
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
        $schema = realpath($schema);

        $retriever = new JsonSchema\Uri\UriRetriever;
        $schema    = $retriever->retrieve('file://' . $schema);

        $refResolver = new JsonSchema\RefResolver($retriever);
        $refResolver->resolve($schema, 'file://' . dirname($schema));

        $validator = new JsonSchema\Validator();
        $validator->check($data, $schema);

        if ($validator->isValid()) {
            return $data;
        } else {
            foreach ($validator->getErrors() as $error) {
                array_push(
                    $this->errorMessage,
                    sprintf("[%s] %s\n", $error['property'], $error['message'])
                );
            };
            return false;
        }
    }

    /**
     * Get Request From Query
     * 
     * @param string $schema json schema
     * 
     * @return mixed
     */
    protected function getQeury($schema)
    {
        return $this->validate($_GET, $schema);
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
        return $this->validate($_POST, $schema);
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
        $rawBody = $this->request->getJsonRawBody();
        if (empty($rawBody)) {
            array_push($this->errorMessage, 'Not found raw body');
            return false;
        } else {
            return $this->validate($rawBody, $schema);
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
        $rawBody = $this->request->getRawBody();
        if (empty($rawBody)) {
            array_push($this->errorMessage, 'Not found raw body');
            return false;
        } else {
            parse_str($rawBody, $data);
            return $this->validate($data, $schema);
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

    public static function notifyError($e)
    {

    }

    /**
     * 處理錯誤資訊
     * 
     * @param Exception $e        Exception物件
     * @param string    $httpCode http status code
     * 
     * @return Object
     */
    public static function handleError($e, $httpCode)
    {
        $result = [
            'code' => 'ERROR',
            'message' => null,
            'extMessage' => null,
            'httpCode' => self::HTTP_SERVER_ERROR_INTERNAL
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
                $result['code']       = 'ERROR';
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

        return $result;
    }
}
?>