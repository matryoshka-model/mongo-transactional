<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace MatryoshkaMongoTransactionalTest\Model;

use Matryoshka\Model\Wrapper\Mongo\Hydrator\ClassMethods;
use Matryoshka\Model\Hydrator\Strategy\HasOneStrategy;
use Matryoshka\MongoTransactional\Error\ErrorInterface;
use Matryoshka\MongoTransactional\Model\TransactionModelHydrator;
use PHPUnit_Framework_TestCase;

/**
 * Class TransactionModelHydratorTest
 */
class TransactionModelHydratorTest extends PHPUnit_Framework_TestCase
{

    public function testCtor()
    {
        $hydrator = new TransactionModelHydrator();
        $this->assertTrue($hydrator->getUnderscoreSeparatedKeys());

        $hydrator = new TransactionModelHydrator(true);
        $this->assertTrue($hydrator->getUnderscoreSeparatedKeys());

        $hydrator = new TransactionModelHydrator(false);
        $this->assertFalse($hydrator->getUnderscoreSeparatedKeys());

        $this->assertTrue($hydrator->hasStrategy('error'));

        $errorObjectStrategy = $hydrator->getStrategy('error');
        $this->assertInstanceOf(HasOneStrategy::class, $errorObjectStrategy);
        /** @var $errorObjectStrategy HasOneStrategy */
        $this->assertInstanceOf(ErrorInterface::class, $errorObjectStrategy->getObjectPrototype());
        $this->assertTrue($errorObjectStrategy->isNullable());
    }

    public function testExtendClassMethods()
    {
        $hydrator = new TransactionModelHydrator();
        $this->assertInstanceOf(ClassMethods::class, $hydrator);
    }

}