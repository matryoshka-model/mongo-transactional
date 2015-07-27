<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Model;

use Matryoshka\Model\ModelEvent;
use Matryoshka\MongoTransactional\Entity\TransactionInterface;

/**
 * Class TransactionEvent
 *
 */
class TransactionEvent extends ModelEvent
{

    const EVENT_BEGIN_TRANSACTION_PRE = 'beginTransaction.pre';
    const EVENT_BEGIN_TRANSACTION_POST = 'beginTransaction.post';

    const EVENT_COMMIT_TRANSACTION_PRE = 'commitTransaction.pre';
    const EVENT_COMMIT_TRANSACTION_POST = 'commitTransaction.post';

    const EVENT_COMPLETE_TRANSACTION_PRE = 'completeTransaction.pre';
    const EVENT_COMPLETE_TRANSACTION_POST = 'completeTransaction.post';

    const EVENT_ABORT_TRANSACTION_PRE = 'abortTransaction.pre';
    const EVENT_ABORT_TRANSACTION_POST = 'abortTransaction.post';

    const EVENT_BEGIN_ROLLBACK_PRE = 'beginRollback.pre';
    const EVENT_BEGIN_ROLLBACK_POST = 'beginRollback.post';

    const EVENT_COMPLETE_ROLLBACK_PRE = 'completeRollback.pre';
    const EVENT_COMPLETE_ROLLBACK_POST = 'completeRollback.post';

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
