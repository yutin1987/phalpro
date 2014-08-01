<?php

use Phalpro\Validator as Validator;

use Codeception\Util\Debug;
use Codeception\TestCase\Test;

class ValidatorTest extends Test
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testMe()
    {
        $validator = new Validator( __DIR__ . '/../_data/');
        
        $report = $validator->validate(
            [
                "shopId" => 1,
                "serviceType" => "2",
                "order" => [
                    "number" => "VAL0001",
                    "date" => "2014-03-01",
                ],
                "from" => [
                    "name" => "YuTIn",
                    "address" => "Taiwan, Taipei",
                    "phone" => "0963066000",
                ],
                "to" => [
                    "name" => "YuTIn",
                    "address" => "Taiwan, Taipei",
                    "phone" => "0963066000",
                ],
            ],
            'schema'
        );

        Debug::debug($validator->getMessage());

        $this->assertTrue($report);
    }
}
?>