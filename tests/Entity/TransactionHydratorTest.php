<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace MatryoshkaMongoTransactionTest\Entity;

use Matryoshka\Model\Hydrator\ClassMethods;
use Matryoshka\Model\Hydrator\Strategy\HasOneStrategy;
use Matryoshka\MongoTransactional\Entity\TransactionHydrator;
use Matryoshka\MongoTransactional\Error\ErrorInterface;
use PHPUnit_Framework_TestCase;

/**
 * Class TransactionHydratorTest
 */
class TransactionHydratorTest extends PHPUnit_Framework_TestCase
{

    public function testCtor()
    {
        $hydrator = new TransactionHydrator();
        $this->assertTrue($hydrator->getUnderscoreSeparatedKeys());

        $hydrator = new TransactionHydrator(true);
        $this->assertTrue($hydrator->getUnderscoreSeparatedKeys());

        $hydrator = new TransactionHydrator(false);
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
        $hydrator = new TransactionHydrator();
        $this->assertInstanceOf(ClassMethods::class, $hydrator);
    }

}