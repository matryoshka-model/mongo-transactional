<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace MatryoshkaMongoTransactionTest\Model;

use Matryoshka\Model\Wrapper\Mongo\Criteria\ActiveRecord\ActiveRecordCriteria as NotIsolatedActiveRecordCritera;
use Matryoshka\Model\Wrapper\Mongo\Criteria\Isolated\ActiveRecordCriteria;
use Matryoshka\Model\Wrapper\Mongo\Criteria\Isolated\DocumentStore;
use Matryoshka\Model\Wrapper\Mongo\ResultSet\HydratingResultSet;
use Matryoshka\MongoTransactional\Entity\TransactionEntity;
use Matryoshka\MongoTransactional\Entity\TransactionHydrator;
use Matryoshka\MongoTransactional\Entity\TransactionInterface;
use Matryoshka\MongoTransactional\Error\ErrorInterface;
use Matryoshka\MongoTransactional\Exception\DomainException;
use Matryoshka\MongoTransactional\Exception\InvalidArgumentException;
use Matryoshka\MongoTransactional\Model\TransactionEvent;
use Matryoshka\MongoTransactional\Model\TransactionModel;
use Matryoshka\MongoTransactional\Model\TransactionModelHydrator;
use MatryoshkaModelWrapperMongoTest\TestAsset\MongoCollectionMockProxy;
use PHPUnit_Framework_TestCase;

/**
 * Class TransactionModelTest
 */
class TransactionModelTest extends PHPUnit_Framework_TestCase
{

    protected static $oldErrorLevel;

    protected static function disableStrictErrors()
    {
        self::$oldErrorLevel = error_reporting();
        error_reporting(self::$oldErrorLevel & ~E_STRICT);
    }

    protected static function restoreErrorReportingLevel()
    {
        error_reporting(self::$oldErrorLevel);
    }

    protected $transactionModel;

    protected $mongoCollectionMock;

    protected $mockProxy;


    protected $classRefl;

    protected $switchStateMethodRefl;

    public function setUp()
    {
        $mongoCollectionMock = $this->getMockBuilder('\MongoCollection')
            ->disableOriginalConstructor()
            ->setMethods(['save', 'find', 'remove', 'insert', 'update', 'getName'])
            ->getMock();

        $this->mongoCollectionMock = $mongoCollectionMock;

        self::disableStrictErrors();
        $mockProxy = new MongoCollectionMockProxy();
        self::restoreErrorReportingLevel();
        $mockProxy->__MongoCollectionMockProxy__setMock($mongoCollectionMock);

        $this->mockProxy = $mockProxy;


        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setObjectPrototype(new TransactionEntity());

        $this->transactionModel = new TransactionModel($mockProxy, $resultSetPrototype);
        $this->transactionModel->setHydrator(new TransactionModelHydrator());

        $reflClass = new \ReflectionClass($this->transactionModel);
        $this->classRefl = $reflClass;
        $this->switchStateMethodRefl = $reflClass->getMethod('switchState');
        $this->switchStateMethodRefl->setAccessible(true);
    }

    public function testCtor()
    {
        $this->assertInstanceOf(TransactionModel::class, $this->transactionModel);
    }

    public function testSaveShouldThrowExceptionIfTransactionIsNotInitial()
    {
        $this->setExpectedException(DomainException::class);
        $transaction = new TransactionEntity();
        $transaction->setState(TransactionInterface::STATE_DONE);
        $this->transactionModel->save(new ActiveRecordCriteria(), $transaction);
    }

    public function testSaveShouldThrowExceptionIfNotTransactionInterface()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $transaction = new \stdClass();
        $this->transactionModel->save(new ActiveRecordCriteria(), $transaction);
    }

    public function testSaveShouldThrowExceptionIfNotIsolatedCriteria()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $transaction = new TransactionEntity();
        $this->transactionModel->save(new NotIsolatedActiveRecordCritera(), $transaction);
    }

    public function testSaveInjectErrorWithinTransaction()
    {
        $transaction = new TransactionEntity();
        $this->assertNull($transaction->getError());
        $e = null;

        try {
            $this->transactionModel->save(new ActiveRecordCriteria(), $transaction);
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(ErrorInterface::class, $transaction->getError());
        $this->assertInstanceOf($transaction->getError()->getExceptionClass(), $e);
    }


    protected $exampleTransactionData = [
        'type' => 'Transaction',
        'state' => 'initial',
        'recovery' => null,
        'error' => null,
    ];

    public function testSaveForInsert()
    {
        $transaction = new TransactionEntity();
        $transaction->setHydrator(new TransactionHydrator());
        $criteria = new ActiveRecordCriteria();

        $expectedData = $this->exampleTransactionData;

        // Populate the object
        $this->transactionModel->getHydrator()->hydrate($expectedData, $transaction);

        $expectedSaveOption = [
            // Added by TransactionModel
            'w'=> 'majority',
            'j'=>true,
        ];

        $expectedReturn = 1;

        $this->mongoCollectionMock->expects($this->at(0))
            ->method('insert')
            ->with(
                $this->equalTo($expectedData),
                $this->equalTo($expectedSaveOption)
            )
            ->willReturn([
                'ok' => true,
                'n'  => 1
            ]);

        // We're going to insert a new transaction, ID must not be present yet
        $this->assertNull($transaction->getId());

        // Create the transaction
        $this->assertSame(
            $expectedReturn,
            $this->transactionModel->save($criteria, $transaction)
        );

        // Test id was added
        $this->assertInternalType('string', $transaction->getId());
    }

    public function testSaveForUpdate()
    {
        $transaction = new TransactionEntity();
        $transaction->setHydrator(new TransactionHydrator());
        $criteria = new ActiveRecordCriteria();

        $expectedData = $this->exampleTransactionData;
        // Insert will inject _id into $expectedData
        DocumentStore::getSharedInstance()->isolatedUpsert($this->mockProxy, $expectedData);

        // Populate the object
        $this->transactionModel->getHydrator()->hydrate($expectedData, $transaction);
        $criteria->setId($transaction->getId());

        $expectedSaveOption = [
            // Added by TransactionModel
            'w'=> 'majority',
            'j'=>true,
            // Added by isolatedUpsert()
            'multi' => false,
            'upsert' => false
        ];

        $expectedReturn = 1;


        // Test modification
        $transaction->setRecovery(true);

        // Expected data that will be write to the persistence
        $expectedData['recovery'] = true;


        $this->mongoCollectionMock->expects($this->at(0))
                                    ->method('update')
                                    ->with(
                                        $this->anything(),
                                        $this->equalTo($expectedData),
                                        $this->equalTo($expectedSaveOption)
                                    )
                                    ->willReturn([
                                        'ok' => true,
                                        'n'  => 1
                                    ]);

        // We're going to insert a new transaction, ID must not be present yet
        $this->assertSame(
            $expectedReturn,
            $this->transactionModel->save($criteria, $transaction)
        );

        $this->assertTrue($transaction->getRecovery());
    }

    public function testSwitchStateShouldThrowExceptionWhenNoId()
    {
        $this->setExpectedException(DomainException::class, sprintf(
            '%s: cannot change state from "%s" to "%s" because transaction ID is not present',
            $eventName = 'test',
            $fromState = 'foo',
            $toState = 'bar'
        ));

        $transaction = new TransactionEntity();
        $this->switchStateMethodRefl->invoke($this->transactionModel, $transaction, $fromState, $toState, $eventName);
    }

    public function testSwitchStateShouldThrowExceptionWhenFromStateMismatches()
    {
        $transaction = new TransactionEntity(); // Assuming a new transaction is in initial state
        $transaction->setId('foo');

        $this->setExpectedException(DomainException::class, sprintf(
            '%s(%s): cannot change state from "%s" to "%s" because transaction current state is "%s"',
            $eventName = 'commitTransaction',
            $transaction->getId(),
            $fromState = $transaction::STATE_PENDING,
            $toState = $transaction::STATE_APPLIED,
            $transaction->getState()
        ));

        $this->switchStateMethodRefl->invoke(
            $this->transactionModel,
            $transaction,
            $fromState,
            $toState,
            $eventName
       );
    }


    public function statesDataProvider()
    {
        return [
            [TransactionInterface::STATE_INITIAL, TransactionInterface::STATE_PENDING, 'beginTransaction'],
            [TransactionInterface::STATE_PENDING, TransactionInterface::STATE_APPLIED, 'commitTransaction'],
            [TransactionInterface::STATE_APPLIED, TransactionInterface::STATE_DONE, 'completeTransaction'],
            [TransactionInterface::STATE_PENDING, TransactionInterface::STATE_CANCELING, 'beginRollback'],
            [TransactionInterface::STATE_CANCELING, TransactionInterface::STATE_CANCELLED, 'completeRollback'],
            [TransactionInterface::STATE_INITIAL, TransactionInterface::STATE_ABORTED, 'abortTransaction'],

        ];
    }


    protected function prepareEntityForSwitchState($fromState, $toState)
    {
        $transaction = new TransactionEntity();
        $transaction->setHydrator(new TransactionHydrator());

        $expectedData = $this->exampleTransactionData;
        $expectedData['state'] = $fromState;
        // Insert will inject _id into $expectedData
        DocumentStore::getSharedInstance()->isolatedUpsert($this->mockProxy, $expectedData);

        // Populate the object
        $this->transactionModel->getHydrator()->hydrate($expectedData, $transaction);

        $expectedSaveOption = [
            // Added by TransactionModel
            'w'=> 'majority',
            'j'=>true,
            // Added by isolatedUpsert()
            'multi' => false,
            'upsert' => false
        ];

        $expectedData['state'] = $toState;

        $this->mongoCollectionMock->expects($this->at(0))
        ->method('update')
        ->with(
            $this->anything(),
            $this->equalTo($expectedData),
            $this->equalTo($expectedSaveOption)
        )
        ->willReturn([
            'ok' => true,
            'n'  => 1
        ]);

        return $transaction;
    }

    /**
     * @dataProvider statesDataProvider
     * @param string $fromState
     * @param string $toState
     * @param string $eventName
     */
    public function testSwitchState($fromState, $toState, $eventName)
    {
        $transaction = $this->prepareEntityForSwitchState($fromState, $toState);

        $preCalled = false;
        $this->transactionModel->getEventManager()->attach($eventName.'.pre', function (TransactionEvent $event) use (&$preCalled, $transaction, $fromState) {
            $preCalled = true;
            $this->assertSame($transaction, $event->getTransaction());
            $this->assertSame($fromState, $event->getTransaction()->getState());
        });

        $postCalled = false;
        $this->transactionModel->getEventManager()->attach($eventName.'.post', function (TransactionEvent $event) use (&$postCalled, $transaction, $toState) {
            $postCalled = true;
            $this->assertSame($transaction, $event->getTransaction());
            $this->assertSame($toState, $event->getTransaction()->getState());
        });

        $this->switchStateMethodRefl->invoke($this->transactionModel, $transaction, $fromState, $toState, $eventName);
        $this->assertEquals($toState, $transaction->getState());
        $this->assertTrue($preCalled, $eventName . '.pre has been not called');
        $this->assertTrue($postCalled, $eventName . '.post has been not called');

        // TODO: test events
    }

    /**
     * @dataProvider statesDataProvider
     * @param string $fromState
     * @param string $toState
     * @param string $eventName
     */
    public function testTransactionMethods($fromState, $toState, $eventName)
    {
        $transaction = $this->prepareEntityForSwitchState($fromState, $toState);

        $this->assertTrue($this->classRefl->hasMethod($eventName));
        $method = $this->classRefl->getMethod($eventName);
        $method->setAccessible(true);

        $method->invoke($this->transactionModel, $transaction);
    }
}
