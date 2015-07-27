<?php

namespace MatryoshkaMongoTransactionalTest\Error;

use Matryoshka\Model\Hydrator\ClassMethods as MatryoshkaClassMethods;
use Matryoshka\MongoTransactional\Error\ErrorInterface;
use Matryoshka\MongoTransactional\Error\ErrorObject;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;

/**
 * Class ErrorObjectTest
 *
 * @group error
 */
class ErrorObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testCtor()
    {
        $mess = 'message';
        $code = 123;
        $exc = new \Exception($mess, $code);
        $error = new ErrorObject($exc);

        $this->assertSame($code, $error->getCode());
        $this->assertSame($mess, $error->getMessage());
        $this->assertSame(get_class($exc), $error->getExceptionClass());
        $this->assertEmpty($error->getAdditionalDetails());
    }

    public function testImplementTransactionInterface()
    {
        $error = new ErrorObject;
        $this->assertInstanceOf(ErrorInterface::class, $error);
    }

    public function testImplementHydratorAwareInterface()
    {
        $error = new ErrorObject;
        $this->assertInstanceOf(HydratorAwareInterface::class, $error);
    }

    public function testDefaultHydrator()
    {
        $error = new ErrorObject;
        $this->assertInstanceOf(MatryoshkaClassMethods::class, $error->getHydrator());
    }
}
