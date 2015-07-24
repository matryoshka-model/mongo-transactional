<?php

namespace MatryoshkaMongoTransactionalTest\Error;

use Matryoshka\MongoTransactional\Error\ErrorTrait;
use MatryoshkaMongoTransactionalTest\Error\TestAsset;

/**
 * Class ErrorTraitTest
 *
 * @group error
 */
class ErrorTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ErrorTrait
     */
    protected $traitObject;

    public function setUp()
    {
        $this->traitObject = $this->getObjectForTrait(ErrorTrait::class);
    }

    public function testFromException()
    {
        $mess = 'message';
        $code = 123;
        $exc = new \Exception($mess, $code);

        /** @var $trait ErrorTrait */
        $this->assertSame($this->traitObject, $this->traitObject->fromException($exc));
        $this->assertSame($code, $this->traitObject->getCode());
        $this->assertSame($mess, $this->traitObject->getMessage());
        $this->assertSame(get_class($exc), $this->traitObject->getExceptionClass());
        $this->assertEmpty($this->traitObject->getAdditionalDetails());
    }

    /**
     * @depends testFromException
     */
    public function testFromExceptionWithAdditionalDetails()
    {
        $details = ['test' => 1];
        $mess = 'message';
        $code = 123;
        $exc = new TestAsset\ExceptionWithAdditionalDetails($mess, $code);
        $exc->setAdditionalDetails($details);

        /** @var $trait ErrorTrait */
        $this->assertSame($this->traitObject, $this->traitObject->fromException($exc));
        $this->assertSame($details, $this->traitObject->getAdditionalDetails());
    }

    /**
     * @depends testFromException
     */
    public function testFromExceptionWithNotArrayOrTraversableAdditionalDetails()
    {
        $details = 456;
        $mess = 'message';
        $code = 123;
        $exc = new TestAsset\ExceptionWithAdditionalDetails($mess, $code);
        $exc->setAdditionalDetails($details);

        /** @var $trait ErrorTrait */
        $this->assertSame($this->traitObject, $this->traitObject->fromException($exc));
        $this->assertEmpty($this->traitObject->getAdditionalDetails());
    }
}
