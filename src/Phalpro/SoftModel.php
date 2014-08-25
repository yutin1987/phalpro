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

    public static $jsonProperty = [];

    public static $enumProperty = [];

    /**
     * model find
     * 
     * @param object $parameters parameters
     * 
     * @return array
     */
    public static function find($parameters = null)
    {
        $data = [];
        $temp = parent::find($parameters);

        foreach ($temp as $row) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * 取得TimeStamp
     * 
     * @return datatime
     */
    public static function getTimeStamp()
    {
        return DateTime::format(DATE_ISO8601);
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
     * 對屬性解碼
     * 
     * @return void
     */
    protected function decodeProperty()
    {
        // Json Property
        foreach (static::$jsonProperty as $property) {
            if (empty($this->$property)) {
                $this->$property = new stdClass();
            } else {
                $this->$property = json_decode($this->$property);
            }
        }

        // Enum Property
        foreach (static::$enumProperty as $property) {
            $enum = &static::${$property . 'Enum'};
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
        foreach (static::$jsonProperty as $property) {
            $this->$property = json_encode($this->$property);
        }

        // Enum Property
        foreach (static::$enumProperty as $property) {
            $enum = &static::${$property . 'Enum'};
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
            if (is_array($value)) {
                if (empty($target->$key)) {
                    $target->$key = new stdClass();
                }
                
                $this->put($target->$key, $value);
            } else {
                $target->$key = $value;
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
        $data = json_decode(json_encode($data), true);

        if (empty($data)) {
            return;
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