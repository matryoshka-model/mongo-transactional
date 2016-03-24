<?php

namespace MatryoshkaMongoTransactionalTest\Error;

use Matryoshka\MongoTransactional\Error\ErrorTrait;
use Matryoshka\MongoTransactional\Exception\InvalidArgumentException;
use MatryoshkaMongoTransactionalTest\Error\TestAsset;
use Zend\Stdlib\ArrayObject;

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

    public function testExceptionClassSetterAndGetter()
    {
        $this->assertSame($this->traitObject, $this->traitObject->setExceptionClass(\Exception::class));
        $this->assertSame(\Exception::class, $this->traitObject->getExceptionClass());
        $this->assertSame($this->traitObject, $this->traitObject->setExceptionClass(false));
        $this->assertNull($this->traitObject->getExceptionClass());
        $this->assertSame($this->traitObject, $this->traitObject->setExceptionClass(null));
        $this->assertNull($this->traitObject->getExceptionClass());
    }

    public function testCodeSetterAndGetter()
    {
        $this->assertSame($this->traitObject, $this->traitObject->setCode(0));
        $this->assertSame(0, $this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode(1));
        $this->assertSame(1, $this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode('0'));
        $this->assertSame('0', $this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode('1'));
        $this->assertSame('1', $this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode('abc'));
        $this->assertSame('abc', $this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode(true));
        $this->assertSame('1', $this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode(''));
        $this->assertNull($this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode(false));
        $this->assertNull($this->traitObject->getCode());
        $this->assertSame($this->traitObject, $this->traitObject->setCode(null));
        $this->assertNull($this->traitObject->getCode());
    }

    public function testMessageSetterAndGetter()
    {
        $this->assertSame($this->traitObject, $this->traitObject->setMessage('mex'));
        $this->assertSame('mex', $this->traitObject->getMessage());
        $this->assertSame($this->traitObject, $this->traitObject->setMessage(false));
        $this->assertNull($this->traitObject->getMessage());
        $this->assertSame($this->traitObject, $this->traitObject->setMessage(null));
        $this->assertNull($this->traitObject->getMessage());
    }

    public function testAdditionalDetailsSetterAndGetter()
    {
        $testArray = [1, 2, 3];
        $testTraversable = new ArrayObject($testArray);
        $this->assertSame($this->traitObject, $this->traitObject->setAdditionalDetails($testTraversable));
        $this->assertSame($testArray, $this->traitObject->getAdditionalDetails());

        $this->assertSame($this->traitObject, $this->traitObject->setAdditionalDetails($testArray));
        $this->assertSame($testArray, $this->traitObject->getAdditionalDetails());

        $testNull = null;
        $this->assertSame($this->traitObject, $this->traitObject->setAdditionalDetails($testNull));
        $this->assertEmpty($this->traitObject->getAdditionalDetails());

        $testNotArray = 123456;
        $this->setExpectedException(InvalidArgumentException::class);
        $this->traitObject->setAdditionalDetails($testNotArray);
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
