<?php
namespace Phalpro;

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