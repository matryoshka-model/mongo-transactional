<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Entity;

use Matryoshka\Model\Object\ActiveRecord\ActiveRecordInterface;
use Matryoshka\MongoTransactional\Error\ErrorAwareInterface;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;

/**
 * Interface TransactionInterface
 *
 * @see http://martinfowler.com/eaaDev/AccountingTransaction.html
 * @see http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits
 */
interface TransactionInterface extends
    ActiveRecordInterface,
    HydratorAwareInterface,
    ErrorAwareInterface
{

    const STATE_INITIAL = 'initial';
    const STATE_PENDING = 'pending';
    const STATE_APPLIED = 'applied';
    const STATE_DONE = 'done';
    const STATE_CANCELING = 'canceling';
    const STATE_CANCELLED = 'cancelled';
    const STATE_ABORTED = 'aborted';

    /**
     * Get the transaction type
     *
     * @return string
     */
    public function getType();

    /**
     * Set the transaction type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * Get the current transaction state
     *
     * @return string
     */
    public function getState();

    /**
     * Set the current transaction state
     *
     * @param string $state
     * @return $this
     */
    public function setState($state);

    /**
     * Get true if a recovery procedure started
     *
     * @return bool
     */
    public function getRecovery();

    /**
     * Set recovery
     *
     * @param bool $recovery
     * @return $this
     */
    public function setRecovery($recovery);
}
