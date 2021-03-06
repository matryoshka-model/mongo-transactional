<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Entity;

use Matryoshka\MongoTransactional\Error\ErrorInterface;
use Matryoshka\MongoTransactional\Exception\InvalidArgumentException;

/**
 * Trait TransactionTrait
 */
trait TransactionTrait
{

    /**
     * @var string
     */
    protected $state = TransactionInterface::STATE_INITIAL;

    /**
     * @var bool
     */
    protected $recovery = false;

    /**
     * @var ErrorInterface|null
     */
    protected $error;

    /**
     * @return string
     */
    public static function extractTypeFromClass()
    {
        $derivedTypeName = static::class;

        if (strpos($derivedTypeName, '\\') !== false) {
            $derivedTypeName = explode('\\', $derivedTypeName);
            $derivedTypeName = array_pop($derivedTypeName);
        }

        if (substr($derivedTypeName, -6, 6) == 'Entity') {
            $derivedTypeName = substr($derivedTypeName, 0, -6);
        }

        return $derivedTypeName;
    }

    /**
     * Returns the object type
     *
     * @return string
     */
    public function getType()
    {
        return static::extractTypeFromClass();
    }

    /**
     * Set the transaction type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        if ($this->getType() != $type) {
            throw new InvalidArgumentException(
                sprintf(
                    'The only allowed type for "%s" class is "%s", "%s" given',
                    get_class($this),
                    $this->getType(),
                    $type
                )
            );
        }
        return $this;
    }

    /**
     * Get the current transaction state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the current transaction state
     *
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        if (!in_array(
            $state,
            [
                TransactionInterface::STATE_INITIAL,
                TransactionInterface::STATE_PENDING,
                TransactionInterface::STATE_APPLIED,
                TransactionInterface::STATE_DONE,
                TransactionInterface::STATE_CANCELING,
                TransactionInterface::STATE_CANCELLED,
                TransactionInterface::STATE_ABORTED,
            ]
        )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '"%s" is not a valid state',
                    $state
                )
            );
        }

        $this->state = $state;
        return $this;
    }

    /**
     * Get true if a recovery procedure started
     *
     * @return bool
     */
    public function getRecovery()
    {
        return $this->recovery;
    }

    /**
     * Set recovery
     *
     * @param bool $recovery
     * @return $this
     */
    public function setRecovery($recovery)
    {
        if ($this->recovery && !$recovery) {
            throw new InvalidArgumentException(
                'Recovery field is not reversible.' .
                'It can not be set to false since it has been already switched to true'
            );
        }
        $this->recovery = (bool)$recovery;
        return $this;
    }

    /**
     * Get the error (can be null)
     *
     * @return ErrorInterface|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set the error
     *
     * @param ErrorInterface|null $error
     * @return $this
     */
    public function setError(ErrorInterface $error = null)
    {
        $this->error = $error;
        return $this;
    }
}
