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
use Matryoshka\MongoTransactional\Entity\TransactionTrait;
use Matryoshka\MongoTransactional\Entity\TransactionInterface;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;
use Matryoshka\MongoTransactional\Entity\TransactionHydrator;
use Matryoshka\MongoTransactional\Exception\InvalidArgumentException;
use Matryoshka\MongoTransactional\Entity\TransactionEntity;
use Matryoshka\MongoTransactional\Error\ErrorObject;

/**
 * Class TransactionTraitTest
 */
class TransactionTraitTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var TransactionTrait
     */
    protected $transactionTrait;

    public function setUp()
    {
        $this->transactionTrait = $this->getMockForTrait(TransactionTrait::class);
    }


    public function testExtractTypeFromClass()
    {
        $transactionTrait = $this->transactionTrait;

        // Assuming that the trait mock classname:
        // - has been created in the global namespace
        // - does not have the "Entity" suffix
        // i.e. Mock_Trait_TransactionTrait_e430dfb3_49a6c0e4
        $this->assertSame(
            get_class($transactionTrait),
            $transactionTrait::extractTypeFromClass()
        );

        // Using Matryoshka\MongoTransactional\Entity\TransactionEntity as test asset
        $this->assertSame('Transaction', TransactionEntity::extractTypeFromClass());
    }

    /**
     * @depends testExtractTypeFromClass
     */
    public function testGetSetType()
    {
        $transactionTrait = $this->transactionTrait;

        $this->assertSame($transactionTrait::extractTypeFromClass(), $transactionTrait->getType());
        $this->assertSame($transactionTrait, $transactionTrait->setType($transactionTrait->getType()));

        $this->setExpectedException(InvalidArgumentException::class);
        $transactionTrait->setType('foo');
    }

    public function testGetSetState()
    {
        // Test default
        $this->assertSame(TransactionInterface::STATE_INITIAL, $this->transactionTrait->getState());

        $validStates = [
            TransactionInterface::STATE_INITIAL,
            TransactionInterface::STATE_PENDING,
            TransactionInterface::STATE_APPLIED,
            TransactionInterface::STATE_DONE,
            TransactionInterface::STATE_CANCELING,
            TransactionInterface::STATE_CANCELLED,
            TransactionInterface::STATE_ABORTED,
        ];

        foreach ($validStates as $state) {
            $this->assertSame($this->transactionTrait, $this->transactionTrait->setState($state));
            $this->assertAttributeSame($state, 'state', $this->transactionTrait);
            $this->assertSame($state, $this->transactionTrait->getState());
        }

        $this->setExpectedException(InvalidArgumentException::class);
        $this->transactionTrait->setState('foo');
    }

    public function testGetSetRecovery()
    {
        // Test default
        $this->assertFalse($this->transactionTrait->getRecovery());

        $this->assertSame($this->transactionTrait, $this->transactionTrait->setRecovery(true));
        $this->assertAttributeSame(true, 'recovery', $this->transactionTrait);
        $this->assertTrue($this->transactionTrait->getRecovery());

        $this->setExpectedException(InvalidArgumentException::class);
        $this->transactionTrait->setRecovery(false);
    }

    public function testGetSetError()
    {
        // Test default
        $this->assertNull($this->transactionTrait->getError());

        $errorObject = new ErrorObject();
        $this->assertSame($this->transactionTrait, $this->transactionTrait->setError($errorObject));
        $this->assertAttributeSame($errorObject, 'error', $this->transactionTrait);
        $this->assertSame($errorObject, $this->transactionTrait->getError());
    }
}
