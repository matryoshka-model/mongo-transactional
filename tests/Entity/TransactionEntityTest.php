<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace MatryoshkaMongoTransactionalTest\Entity;

use Matryoshka\MongoTransactional\Entity\TransactionEntity;
use Matryoshka\MongoTransactional\Entity\TransactionHydrator;
use Matryoshka\MongoTransactional\Entity\TransactionInterface;
use PHPUnit_Framework_TestCase;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;

/**
 * Class TransactionEntityTest
 *
 * @group entity
 */
class TransactionEntityTest extends PHPUnit_Framework_TestCase
{

    public function testImplementTransactionInterface()
    {
        $transaction = new TransactionEntity();
        $this->assertInstanceOf(TransactionInterface::class, $transaction);
    }

    public function testImplementHydratorAwareInterface()
    {
        $transaction = new TransactionEntity();
        $this->assertInstanceOf(HydratorAwareInterface::class, $transaction);
    }

    public function testDefaultHydrator()
    {
        $transaction = new TransactionEntity();
        $this->assertInstanceOf(TransactionHydrator::class, $transaction->getHydrator());
    }
}
