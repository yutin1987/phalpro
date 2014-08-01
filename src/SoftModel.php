<?php

namespace Phalpro;

use Phalcon\Mvc\Model\Behavior\SoftDelete;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model;
use Phalcon\Db\Column;

/**
 * 
 */
class SoftModel extends Model
{

    public static function getTimestamp()
    {
        return date("Y-m-d H:i:s", time("now"));
    } 
    
    public function initialize()
    {
        $this->setSoftDelete();

        $this->skipAttributesOnUpdate(array('createTime'));
    }

    protected function setSoftDelete() {

        $this->addBehavior(new SoftDelete(
            array(
                'field' => 'deleteTime',
                'value' => self::getTimestamp()
            )
        ));
        
    }

    protected function setSoftStatus() {

        $this->addBehavior(new SoftDelete(
            array(
                'field' => 'status',
                'value' => 0
            )
        ));

    }

    public function beforeValidationOnCreate()
    {
        $this->updateTime = self::getTimestamp();
        $this->createTime = self::getTimestamp();
    }

    public function beforeValidationOnUpdate()
    {
        $this->updateTime = self::getTimestamp();
    }

}