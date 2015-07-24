<?php
/**
 * MongoDB Transaction
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransaction\Model;

use Matryoshka\Model\ModelEvent;
use Matryoshka\MongoTransaction\Entity\TransactionInterface;

class TransactionEvent extends ModelEvent
{

    /**
     * @var TransactionInterface
     */
    protected $transaction;

    /**
     * @return TransactionInterface|null
     */
    public function getTransaction()
    {
        if (!$this->transaction && $this->data instanceof TransactionInterface) {
            $this->transaction = $this->data;
        }
        return $this->transaction;
    }

    /**
     * @param TransactionInterface $transaction
     * @return $this
     */
    public function setTransaction(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
        return $this;
    }
}
