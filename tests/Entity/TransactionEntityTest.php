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

/**
 * Class TransactionEntityTest
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