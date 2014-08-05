<?php

namespace Phalpro;

use JsonSchema;

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
        
        $this->validator = new JsonSchema\Validator();
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
        $retriever = new JsonSchema\Uri\UriRetriever;
        $schemaObj = $retriever->retrieve("file://{$schemaFile}");

        $refResolver = new JsonSchema\RefResolver($retriever);
        $refResolver->resolve($schemaObj, "file://{$schemaPath}/");

        return $schemaObj;
    }

    /**
     * 驗證Data
     *
     * @param mixed  $data   data
     * @param string $schema schema
     *
     * @return boolean
     * @throws Exception schma 不存在或不正確, 或 data 格式錯誤
     */
    public function validate($data, $schema)
    {
        try {
            $dataObj   = $this->convertData($data);
            $schemaObj = $this->loadSchema($schema);
            
            if (false == $schemaObj) {
                return true;
            }

            $this->validator->check($dataObj, $schemaObj);

            if ($this->validator->isValid()) {
                return true;
            } else {
                foreach ($this->validator->getErrors() as $error) {
                    if ($error['property']) {
                        $error['message'] = str_replace(
                            'property ',
                            "property {$error['property']}.",
                            $error['message']
                        );
                    };
                    array_push(
                        $this->errorMessage,
                        $error['message']
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
        return implode(',', $this->errorMessage);
    }
}
?>