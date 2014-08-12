<?php
namespace Phalpro;

use \stdClass;

use Phalcon\Mvc\Model\Behavior\SoftDelete;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model;
use Phalcon\Db\Column;

/**
 * SoftModel
 *
 * @category SoftModel
 * @package  Phalpro
 * @author   YuTin <yuting1987@gmail.com>
 */
class SoftModel extends Model
{

    protected $jsonProperty = [];

    protected $enumProperty = [];

    /**
     * 取得TimeStamp
     * 
     * @return datatime
     */
    public static function getTimeStamp()
    {
        return date("Y-m-d H:i:s", time("now"));
    }
    
    /**
     * model initialize
     * 
     * @return void
     */
    public function initialize()
    {
        $this->setSoftDelete();

        $this->skipAttributesOnUpdate(array('createTime'));
    }

    /**
     * 設定 Json格式 的屬性
     * 
     * @param string $property 屬性名稱
     *
     * @return void
     */
    protected function setJsonProperty($property)
    {
        $this->jsonProperty[] = $property;

        if (empty($this->$property)) {
            $this->$property = new stdClass();
        }
    }

    /**
     * 設定 Enum格式 的屬性
     * 
     * @param string $property 屬性名稱
     * @param array  $enum     參考值
     *
     * @return void
     */
    protected function setEnumProperty($property, $enum)
    {
        $this->enumProperty[$property] = $enum;
    }

    /**
     * 對屬性解碼
     * 
     * @return void
     */
    protected function decodeProperty()
    {
        // Json Property
        foreach ($this->jsonProperty as $property) {
            $this->$property = json_decode($this->$property);
        }

        // Enum Property
        foreach ($this->enumProperty as $property => $enum) {
            $this->$property = $enum[$this->$property];
        }
    }

    /**
     * 對屬性編碼
     * 
     * @return void
     */
    protected function encodeProperty()
    {
        // Json Property
        foreach ($this->jsonProperty as $property) {
            $this->$property = json_encode($this->$property);
        }

        // Enum Property
        foreach ($this->enumProperty as $property => $enum) {
            $this->$property = array_search($this->$property, $enum);
        }
    }
    
    /**
     * Model afterFetch
     * 
     * @return void
     */
    public function afterFetch()
    {
        $this->decodeProperty();
    }
    
    /**
     * Model afterSave
     * 
     * @return void
     */
    public function afterSave()
    {
        $this->decodeProperty();
    }

    /**
     * Model onValidationFails
     * 
     * @return void
     */
    public function onValidationFails()
    {
        $this->decodeProperty();
    }

    /**
     * Model After Create
     * 
     * @return void
     * @throws ServerException
     */
    public function beforeValidation()
    {
        $this->encodeProperty();
    }

    /**
     * 設定SoftDelete
     *
     * @return void
     */
    protected function setSoftDelete()
    {
        $this->addBehavior(
            new SoftDelete(
                [
                    'field' => 'deleteTime',
                    'value' => self::getTimeStamp()
                ]
            )
        );
    }

    /**
     * 設定SoftStatus
     * 
     * @return void
     */
    protected function setSoftStatus()
    {
        $this->addBehavior(
            new SoftDelete(
                [
                    'field' => 'status',
                    'value' => 0
                ]
            )
        );
    }

    /**
     * 放入資料
     * 
     * @param mixed &$target 目標
     * @param mixed $data    資料
     * 
     * @return void
     */
    protected function put(&$target, $data)
    {
        foreach ($data as $key => $value) {
            if (!is_object($this->$key)) {
                $target->$key = $value;
            } elseif (is_object($value)) {
                $this->put($target->$key, $value);
            } else {
                continue;
            }
        }
    }

    /**
     * 大量放入資料
     * 
     * @param object/array $data 資料
     * 
     * @return void
     * @throws Exception If data must be an object or array
     */
    public function putIn($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (!is_array($data)) {
            throw new Exception("data must be an object or array");
        }

        $this->put($this, $data);
    }

    /**
     * before ValidationOnCreate
     * 
     * @return void
     */
    public function beforeValidationOnCreate()
    {
        $this->updateTime = self::getTimeStamp();
        $this->createTime = self::getTimeStamp();
    }

    /**
     * before ValidationOnUpdate
     * 
     * @return void
     */
    public function beforeValidationOnUpdate()
    {
        $this->updateTime = self::getTimeStamp();
    }
}
?>