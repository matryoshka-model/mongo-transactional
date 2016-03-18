<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Model;

use Matryoshka\Model\Criteria\DeletableCriteriaInterface;
use Matryoshka\Model\Criteria\WritableCriteriaInterface;
use Matryoshka\Model\ObservableModel;
use Matryoshka\Model\Wrapper\Mongo\Criteria\Isolated\ActiveRecordCriteria;
use Matryoshka\MongoTransactional\Entity\TransactionInterface;
use Matryoshka\MongoTransactional\Error\ErrorObject;
use Matryoshka\MongoTransactional\Exception\DomainException;
use Matryoshka\MongoTransactional\Exception\InvalidArgumentException;
use Matryoshka\MongoTransactional\Exception\RollbackNotPermittedException;
use Matryoshka\MongoTransactional\Exception\RuntimeException;

/**
 * Class TransactionModel
 *
 * @see http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/
 *
 */
class TransactionModel extends ObservableModel
{

    /**
     * Options for Mongo save and delete operations
     *
     * @var array
     */
    protected $mongoOptions = [
        // TODO: double checked if majority + isolation writes are enough to avoid using data behind the primary
        'w' => 'majority',
        'j' => true,
    ];

    /**
     * Set options for Mongo save and delete operations
     *
     * Options must be allow "Acknowledged Writes" by enabling
     * "j" or "fsync" settings.
     *
     * @see http://php.net/manual/en/mongo.writeconcerns.php
     * @param array $options
     * @throws DomainException
     * @return $this
     */
    public function setMongoOptions(array $options)
    {
        if (empty($options['j']) && empty($options['fsync'])) {
            throw new DomainException(
                sprintf(
                    'Journaled writes ("j" => true) or disk sync ("fsync" => true) must be enabled'
                )
            );
        }

        // Test "w" => 0 is not required, when "j" or "fsync" is enabled
        // an acknowledged is implied and it will override setting "w" to 0.

        $this->mongoOptions = $options;
        return $this;
    }

    /**
     * Get options for Mongo save and delete operations
     *
     * @return array
     */
    public function getMongoOptions()
    {
        return $this->mongoOptions;
    }

    /**
     * {@inheritdoc}
     * @return TransactionInterface
     */
    public function getObjectPrototype()
    {
        $objectPrototype = parent::getObjectPrototype();
        if (!$objectPrototype instanceof TransactionInterface) {
            throw new RuntimeException(
                sprintf(
                    'Object prototype must be an instance of "%s": "%s" given',
                    TransactionInterface::class,
                    is_object($objectPrototype) ? get_class($objectPrototype) : gettype($objectPrototype)
                )
            );
        }
        return $objectPrototype;
    }

    /**
     * {@inheritdoc}
     */
    public function getHydrator()
    {
        if (!$this->hydrator) {
            $this->hydrator = new TransactionModelHydrator();
        }
        return $this->hydrator;
    }

    /**
     * {@inheritdoc}
     * @return TransactionEvent
     */
    protected function getEvent()
    {
        $event = new TransactionEvent();
        $event->setTarget($this);
        return $event;
    }


    /**
     * @param TransactionInterface $from
     * @param TransactionInterface $to
     */
    protected function transactionCopy(TransactionInterface $from, TransactionInterface $to)
    {
        $to->getHydrator()->hydrate(
            $from->getHydrator()->extract($from),
            $to
        );
        $from->getHydrator()->extract($from);
    }


    /**
     * @param WritableCriteriaInterface $criteria
     * @param TransactionInterface $transaction
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @return number
     */
    protected function isolatedSave(WritableCriteriaInterface $criteria, TransactionInterface $transaction)
    {
        if (!$criteria instanceof ActiveRecordCriteria) {
            throw new InvalidArgumentException(
                sprintf(
                    'Isolated criteria required, "%s" given',
                    get_class($criteria)
                )
            );
        }

        $criteria->setMongoOptions($this->getMongoOptions());

        $result = parent::save($criteria, $transaction);

        if ($result != 1) {
            throw new RuntimeException(
                sprintf(
                    'Unexpected write result: expected just one, got "%s"',
                    is_int($result) ? $result : gettype($result)
                )
            );
        }

        return 1;
    }

    /**
     * @param TransactionInterface $transaction
     * @param $fromState
     * @param $toState
     * @param $eventName
     * @throws DomainException
     * @throws \Exception
     */
    protected function switchState(TransactionInterface $transaction, $fromState, $toState, $eventName)
    {
        try {
            if (!$transaction->getId()) {
                throw new DomainException(
                    sprintf(
                        '%s: cannot change state from "%s" to "%s" because transaction ID is not present',
                        $eventName,
                        $fromState,
                        $toState
                    )
                );
            }

            if ($transaction->getState() != $fromState) {
                throw new DomainException(
                    sprintf(
                        '%s(%s): cannot change state from "%s" to "%s" because transaction current state is "%s"',
                        $eventName,
                        $transaction->getId(),
                        $fromState,
                        $toState,
                        $transaction->getState()
                    )
                );
            }

            $criteria = new ActiveRecordCriteria();
            $criteria->setId($transaction->getId());
            $event = $this->getEvent();
            $event->setCriteria($criteria);
            $event->setParam('data', $transaction);
            $event->setTransaction($transaction);

            $this->getEventManager()->trigger($eventName . '.pre', $event);

            // TODO: understand what happens if event propagation is stopped

            $transaction->setState($toState);
            //FIXME: mismatching type from $criteria to parameter needed from method isolatedSave
            $this->isolatedSave($criteria, $transaction);

            $this->getEventManager()->trigger($eventName . '.post', $event);
        } catch (\Exception $e) {
            $transaction->setError(new ErrorObject($e));
            throw $e;
        }
    }


    /**
     * @param TransactionInterface $transaction
     */
    protected function beginTransaction(TransactionInterface $transaction)
    {
        $this->switchState(
            $transaction,
            TransactionInterface::STATE_INITIAL,
            TransactionInterface::STATE_PENDING,
            __FUNCTION__
        );
    }

    /**
     * @param TransactionInterface $transaction
     */
    protected function commitTransaction(TransactionInterface $transaction)
    {
        $this->switchState(
            $transaction,
            TransactionInterface::STATE_PENDING,
            TransactionInterface::STATE_APPLIED,
            __FUNCTION__
        );
    }

    /**
     * @param TransactionInterface $transaction
     */
    protected function completeTransaction(TransactionInterface $transaction)
    {
        $this->switchState(
            $transaction,
            TransactionInterface::STATE_APPLIED,
            TransactionInterface::STATE_DONE,
            __FUNCTION__
        );
    }

    /**
     * @param TransactionInterface $transaction
     */
    protected function beginRollback(TransactionInterface $transaction)
    {
        $this->switchState(
            $transaction,
            TransactionInterface::STATE_PENDING,
            TransactionInterface::STATE_CANCELING,
            __FUNCTION__
        );
    }

    /**
     * @param TransactionInterface $transaction
     */
    protected function completeRollback(TransactionInterface $transaction)
    {
        $this->switchState(
            $transaction,
            TransactionInterface::STATE_CANCELING,
            TransactionInterface::STATE_CANCELLED,
            __FUNCTION__
        );
    }

    /**
     * @param TransactionInterface $transaction
     */
    protected function abortTransaction(TransactionInterface $transaction)
    {
        $this->switchState(
            $transaction,
            TransactionInterface::STATE_INITIAL,
            TransactionInterface::STATE_ABORTED,
            __FUNCTION__
        );
    }


    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     * @throws DomainException
     */
    public function save(WritableCriteriaInterface $criteria, $dataOrObject)
    {
        if (!$dataOrObject instanceof TransactionInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Only instance of %s can be saved: "%s" given',
                    TransactionInterface::class,
                    is_object($dataOrObject) ? get_class($dataOrObject) : gettype($dataOrObject)
                )
            );
        }

        if ($dataOrObject->getState() != TransactionInterface::STATE_INITIAL) {
            throw new DomainException(
                sprintf(
                    'Only transactions in "%s" status can be created or updated: "%" state given',
                    TransactionInterface::STATE_INITIAL,
                    $dataOrObject->getState()
                )
            );
        }

        try {
            return $this->isolatedSave($criteria, $dataOrObject);
        } catch (\Exception $e) {
            $dataOrObject->setError(new ErrorObject($e));
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     * @throws DomainException
     * @throws RuntimeException
     */
    public function delete(DeletableCriteriaInterface $criteria)
    {
        if (!$criteria instanceof ActiveRecordCriteria) {
            throw new InvalidArgumentException(
                sprintf(
                    'Isolated criteria required, "%s" given',
                    get_class($criteria)
                )
            );
        }

        // Ensure isolation and check allowed states
        $transaction = $this->find($criteria)->current();

        if (!$transaction instanceof TransactionInterface) {
            throw new RuntimeException(
                sprintf(
                    'Transaction "%s" cannot be deleted because it does not exist or is inconsistent',
                    $criteria->getId()
                )
            );
        }

        switch ($transaction->getState()) {
            case TransactionInterface::STATE_INITIAL:
            case TransactionInterface::STATE_ABORTED:
                // Only not started transaction can be deleted
                break;
            default:
                throw new DomainException(
                    sprintf(
                        'Only transactions with "%s" or "%s" states can be deleted: transaction "%s" has "%s" state',
                        TransactionInterface::STATE_INITIAL,
                        TransactionInterface::STATE_ABORTED,
                        $transaction->getId(),
                        $transaction->getState()
                    )
                );
        }

        $criteria->setMongoOptions($this->getMongoOptions());

        return parent::delete($criteria);
    }


    /**
     * Coordinate the whole 2PC transaction.
     *
     * Transaction must in INITIAL state.
     *
     * @see http://en.wikipedia.org/wiki/Two-phase_commit_protocol
     * @see http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/
     *
     * @param TransactionInterface $transaction
     * @throws RuntimeException
     * @throws RuntimeException
     */
    public function process(TransactionInterface $transaction)
    {
        if ($transaction->getState() != TransactionInterface::STATE_INITIAL) {
            throw new RuntimeException(
                sprintf(
                    'Transaction must be in "%s" state in order to be processed: "%s" state given',
                    TransactionInterface::STATE_INITIAL,
                    $transaction->getState()
                )
            );
        }

        /*
         * Ensure this process is handling the transaction.
         *
         */
        $this->beginTransaction($transaction);
        /*
         * POST-BEGIN stage
         *
         * From post-begin stage and until commit will be applied, the transaction
         * is in PENDING state.
         *
         * In case of recovery, a rollback operation will be tried,
         * but the begin stage will be not repeated.
         *
         * However, if a cohorts prohibits the rollback then the recovery operation will try
         * to perform the commit again, because the transaction can not be rollbacked anymore.
         */


        /*
         * PRE-COMMIT stage
         *
         * Cohorts that perform external operations should be called in pre-commit stage.
         *
         * When something goes wrong and rollback is not possible, then the recovery
         * operation can try to perform the commit again, so operations applying this stage
         * MUST be idempotent.
         *
         */
        $this->commitTransaction($transaction);


        /*
         * POST-COMMIT and PRE-COMPLETE stages
         *
         * Transaction is almost done.
         *
         * At this stage, operations performed before the commit stage are already confirmed.
         * During pre-complete stage the listeners can releasing locks.
         *
         * At this state you can attach other non-transactional operations, i.e.
         * pre-complete stage can be used for idempotent operations on referenced entities,
         * pre-complete will be applied again in case of recovery. That's useful to enforce data
         * consistency or cleanup.
         *
         * Finally, mark the transaction as done.
         */
        $this->completeTransaction($transaction);

        /*
         * POST-COMPLETE stage
         *
         * Transaction has been completed, no more changes can be applied to the transaction.
         * Operations applied on post-complete stage have no warranty that will be executed,
         * because if something goes wrong the post-complete stage will be no more applied.
         */
    }

    /**
     * @param TransactionInterface $transaction
     * @param bool $tryRollback
     */
    public function recover(TransactionInterface $transaction, $tryRollback = true)
    {
        /*
         * At this state we do not know if $transaction's data is consistent.
         * First of all, re-fetch it from the database.
         * If fetching will fail, maybe another process is handling
         * the transaction and we are being trapped by the isolation block:
         * in this case we can do nothing at moment, recovery must
         * be called later from another process.
         *
         */

        $transactionFromPersistence = $this->find((new ActiveRecordCriteria())->setId($transaction->getId()))->current(
        );

        if (!$transactionFromPersistence instanceof TransactionInterface) {
            throw new RuntimeException(
                sprintf(
                    'Transaction "%s" does not exist or is inconsistent',
                    $transaction->getId()
                )
            );
        }

        /*
         * If both ($transactionFromPersistence and $transaction) are in the same state
         * then last save operation was OK.
         * At this moment, due to isolation we are sure that other processes
         * did not modify this transaction.
         * So we can use the original $transaction, in order to do not lose
         * runtime modifications of $transaction.
         *
         * Otherwise, if they are different then last save operation failed.
         * In this case we can just try to perform again the last state change,
         * using the consistent $transactionFromPersistence object data and discarding
         * the inconsistent $transaction object data.
         *
         */
        if ($transactionFromPersistence->getState() != $transaction->getState()) {
            // Copy $transactionFromPersistence data to $transaction
            $this->transactionCopy(
                $transactionFromPersistence,
                $transaction
            ); // FIXME: object integral copy may be better?
        }

        //FIXME: TransactionInterface does not declare method setModel
        $transaction->setModel($this);
        $transaction->setRecovery(true);

        switch ($transaction->getState()) {

            /*
             * Initial state does not need recovery because transaction never started.
             * Just try to abort it.
             */
            case TransactionInterface::STATE_INITIAL:
                $this->abortTransaction($transaction);
                break;


            case TransactionInterface::STATE_PENDING:

                if ($tryRollback) {
                    try {
                        $this->beginRollback($transaction);
                        $this->completeRollback($transaction);
                        break;
                    } catch (RollbackNotPermittedException $e) {
                        // RollbackNotPermittedException must be thrown only in beginRollback.pre phase
                        // In this case, ignore rollback and try to perform commit
                    }
                }

                $this->commitTransaction($transaction);
                $this->completeTransaction($transaction);
                break;


            case TransactionInterface::STATE_APPLIED:
                $this->completeTransaction($transaction);
                break;

            case TransactionInterface::STATE_CANCELING:
                $this->completeRollback($transaction);
                break;

            case TransactionInterface::STATE_DONE:
            case TransactionInterface::STATE_ABORTED:
            default:
                // Nothing to do
        }
    }
}
