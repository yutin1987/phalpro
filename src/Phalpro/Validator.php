<?php
namespace Phalpro;

use \stdClass;

use \JsonSchema\Uri\UriRetriever as JsonUriRetriever;
use \JsonSchema\RefResolver as JsonRefResolver;
use \JsonSchema\Validator as JsonValidator;

/**
 * Validator
 *
 * @category Validator
 * @package  Phalpro
 * @author   YuTin <yuting1987@gmail.com>
 */
class Validator
{
    public $path;

    public $errorMessage = [];

    protected $validator;

    /**
     * __construct
     *
     * @param string $path schema 目錄位置
     *
     * @return void
     */
    public function __construct($path = null)
    {
        $this->path = $path;
    }

    /**
     * 轉換Data to Object
     *
     * @param mixed $data data
     *
     * @return object $dataObj
     */
    private function convertData($data)
    {
        if (is_object($data)) {
            return $data;
        }

        if (is_string($data)) {
            return json_decode($data);
        }

        if (is_array($data)) {
            return json_decode(json_encode($data));
        }

        return false;
    }

    /**
     * 載入Schema
     *
     * @param mixed $schema schema
     *
     * @return object $schemaObj
     * @throws ModelException if data type is not string, array or object
     */
    private function loadSchema($schema)
    {
        // 加上副檔名JSON
        if (pathinfo($schema, PATHINFO_EXTENSION) != 'json') {
            $schema .= '.json';
        }

        // 檢查Schema檔是否存在
        if (file_exists(realpath("{$this->path}/{$schema}"))) {
            $schemaFile = realpath("{$this->path}/{$schema}");
            $schemaPath = dirname($schemaFile);
        } elseif (file_exists(realpath($schema))) {
            $schemaFile = realpath($schema);
            $schemaPath = dirname($schemaFile);
        } else {
            return false;
        }

        // 載入Schema
        $retriever = new JsonUriRetriever;
        $schemaObj = $retriever->retrieve("file://{$schemaFile}");

        $refResolver = new JsonRefResolver($retriever);
        $refResolver->resolve($schemaObj, "file://{$schemaPath}/");

        return $schemaObj;
    }
    
    /**
     * 驗證Data is Required
     * 
     * @param object/array $data      資料
     * @param array        $parameter 必需的參數
     * 
     * @return boolean
     * @throws Exception parameter 不存在或 data 格式錯誤
     */
    public function required($data, $parameter)
    {
        if (!is_array($parameter)) {
            throw new Exception("parameter must be an array");
        }

        $validation = true;

        if (is_array($data)) {
            for ($i = count($data); $i >= 0; $i--) {
                if (!$this->required($data[$i], $parameter)) {
                    $validation = false;
                };
            }
        } else {
            for ($i = (count($parameter) - 1); $i >= 0; $i--) {
                $name = $parameter[$i];
                if (!property_exists($data, $name) || empty($data->$name)) {
                    if (!in_array("{$name} is required", $this->errorMessage)) {
                        array_push($this->errorMessage, "{$name} is required");
                    }

                    $validation = false;
                }
            }
        }

        return $validation;
    }

    /**
     * 驗證Data
     *
     * @param mixed  $data       data
     * @param string $properties properties
     *
     * @return object
     */
    public function convertType($data, $properties)
    {
        $temp = new stdClass();
        foreach ($properties as $key => $value) {
            switch ($value->type) {
                case 'object':
                    $temp->$key = $this->convertType(
                        $data->$key,
                        $properties->properties
                    );
                    break;
                case 'integer':
                    $temp->$key = intval($data->$key);
                    break;
                default:
                    $temp->$key = $data->$key;
                    break;
            }
        }

        return $temp;
    }

    /**
     * 驗證Data
     *
     * @param mixed  $data   data
     * @param string $schema schema
     *
     * @return mixed
     * @throws Exception schma 不存在或不正確, 或 data 格式錯誤
     */
    public function validate($data, $schema)
    {
        try {
            $validator = new JsonValidator();

            $dataObj   = $this->convertData($data);
            $schemaObj = $this->loadSchema($schema);

            if (false == $schemaObj) {
                return true;
            }

            $dataObj = $this->convertType(
                $dataObj,
                $schemaObj->properties
            );

            $validator->check($dataObj, $schemaObj);

            if ($validator->isValid()) {
                return $dataObj;
            } else {
                foreach ($validator->getErrors() as $error) {
                    array_push(
                        $this->errorMessage,
                        "{$error['property']} {$error['message']}"
                    );
                };
                return false;
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            throw new Exception("schema 格式錯誤 ($msg)");
        }//end try
    }

    /**
     * get error from validation result
     *
     * @return array errors
     */
    public function getMessage()
    {
        return implode(';', $this->errorMessage);
    }
}
?>
