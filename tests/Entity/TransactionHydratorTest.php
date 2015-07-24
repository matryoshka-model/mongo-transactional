<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace MatryoshkaMongoTransactionTest\Entity;

use PHPUnit_Framework_TestCase;
use Matryoshka\MongoTransactional\Entity\TransactionEntity;
use Matryoshka\MongoTransactional\Entity\TransactionInterface;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;
use Matryoshka\MongoTransactional\Entity\TransactionHydrator;
use Matryoshka\Model\Hydrator\Strategy\HasOneStrategy;
use Matryoshka\MongoTransactional\Error\ErrorInterface;
use Matryoshka\Model\Hydrator\ClassMethods;

/**
 * Class TransactionHydratorTest
 */
class TransactionHydratorTest extends PHPUnit_Framework_TestCase
{

    public function test__construct()
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
        $this->assertInstanceOf(ErrorInterface::class, $errorObjectStrategy->getObjectPrototype());
        $this->assertTrue($errorObjectStrategy->isNullable());
    }

    public function testExtendClassMethods()
    {
        $hydrator = new TransactionHydrator();
        $this->assertInstanceOf(ClassMethods::class, $hydrator);
    }

}