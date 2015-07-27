<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace MatryoshkaMongoTransactionalTest\Model;

use Matryoshka\MongoTransactional\Entity\TransactionEntity;
use Matryoshka\MongoTransactional\Model\TransactionEvent;
use PHPUnit_Framework_TestCase;

/**
 * Class TransactionEventTest
 *
 * @group model
 */
class TransactionEventTest extends PHPUnit_Framework_TestCase
{

    public function testGetSetTransaction()
    {
        $event = new TransactionEvent();

        $this->assertNull($event->getTransaction());

        $transaction = new TransactionEntity();
        $this->assertSame($event, $event->setTransaction($transaction));
        $this->assertSame($transaction, $event->getTransaction());
    }

    public function testGetTransactionPeerFromData()
    {
        $event = new TransactionEvent();

        $this->assertNull($event->getTransaction());

        $transaction = new TransactionEntity();
        $event->setData($transaction);
        $this->assertSame($transaction, $event->getTransaction());
    }
}
