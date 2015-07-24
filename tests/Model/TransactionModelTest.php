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
use Matryoshka\MongoTransactional\Model\Matryoshka\MongoTransactional\Model;
use Matryoshka\Model\Wrapper\Mongo\ResultSet\Matryoshka\Model\Wrapper\Mongo\ResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Matryoshka\MongoTransactional\Entity\Matryoshka\MongoTransactional\Entity;
use Matryoshka\MongoTransactional\Exception\RuntimeException;

/**
 * Class TransactionModelTest
 *
 */
class TransactionModelTest extends PHPUnit_Framework_TestCase
{


    public static $eventStateMap = [
        TransactionEvent::EVENT_BEGIN_TRANSACTION_PRE => TransactionInterface::STATE_INITIAL,
        TransactionEvent::EVENT_BEGIN_TRANSACTION_POST => TransactionInterface::STATE_PENDING,
        TransactionEvent::EVENT_COMMIT_TRANSACTION_PRE => TransactionInterface::STATE_PENDING,
        TransactionEvent::EVENT_COMMIT_TRANSACTION_POST => TransactionInterface::STATE_APPLIED,
        TransactionEvent::EVENT_COMPLETE_TRANSACTION_PRE => TransactionInterface::STATE_APPLIED,
        TransactionEvent::EVENT_COMPLETE_TRANSACTION_POST => TransactionInterface::STATE_DONE,
        TransactionEvent::EVENT_BEGIN_ROLLBACK_PRE => TransactionInterface::STATE_PENDING,
        TransactionEvent::EVENT_BEGIN_ROLLBACK_POST => TransactionInterface::STATE_CANCELING,
        TransactionEvent::EVENT_COMPLETE_ROLLBACK_PRE => TransactionInterface::STATE_CANCELING,
        TransactionEvent::EVENT_COMPLETE_ROLLBACK_POST => TransactionInterface::STATE_CANCELLED,
        TransactionEvent::EVENT_ABORT_TRANSACTION_PRE => TransactionInterface::STATE_INITIAL,
        TransactionEvent::EVENT_ABORT_TRANSACTION_POST => TransactionInterface::STATE_ABORTED,
    ];

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

    public function allStatesDataProvider()
    {
        return [
            [TransactionInterface::STATE_ABORTED],
            [TransactionInterface::STATE_APPLIED],
            [TransactionInterface::STATE_CANCELING],
            [TransactionInterface::STATE_CANCELLED],
            [TransactionInterface::STATE_DONE],
            [TransactionInterface::STATE_INITIAL],
            [TransactionInterface::STATE_PENDING],
        ];
    }

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

    public function testGetHydrator()
    {
        $model = new TransactionModel('fake', new HydratingResultSet());
        $this->assertInstanceOf(TransactionModelHydrator::class, $model->getHydrator());

        $anotherHydrator = new ObjectProperty();
        $model->setHydrator($anotherHydrator);

        $this->assertSame($anotherHydrator, $model->getHydrator());
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


    protected function prepareEntityForSwitchStateSeries($fromState, array $series)
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

        $at = 0;
        foreach ($series as $toState) {
            $expectedData['state'] = $toState;
            $this->mongoCollectionMock->expects($this->at($at))
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

            $at++;
        }

        return $transaction;
    }

    protected function prepareEventSeries(TransactionInterface $transaction, array $series, array &$calledList = [])
    {

        $listener = function (TransactionEvent $event) use (&$calledList, $transaction) {
                $calledList[] = $event->getName();
                $this->assertSame($transaction, $event->getTransaction());
                $this->assertSame(self::$eventStateMap[$event->getName()], $event->getTransaction()->getState());
        };

        foreach ($series as $eventName) {
            $this->transactionModel->getEventManager()->attach($eventName.'.pre', $listener);
            $this->transactionModel->getEventManager()->attach($eventName.'.post', $listener);
        }
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

        $calledList = [];
        $this->prepareEventSeries($transaction, [$eventName], $calledList);

        $this->switchStateMethodRefl->invoke($this->transactionModel, $transaction, $fromState, $toState, $eventName);
        $this->assertEquals($toState, $transaction->getState());
        $this->assertSame([
            0 => $eventName . '.pre',
            1 => $eventName . '.post',
        ], $calledList, 'invalid events sequence');
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

    /**
     * @dataProvider allStatesDataProvider
     */
    public function testProcessShouldThrowExceptionWhenTransactionIsNotInitial($state)
    {
        if ($state == TransactionInterface::STATE_INITIAL) {
            return;
        }

        $transaction = new TransactionEntity();
        $transaction->setState($state);

        $this->setExpectedException(RuntimeException::class, sprintf(
            'Transaction must be in "%s" state in order to be processed: "%s" state given',
            TransactionInterface::STATE_INITIAL,
            $state
        ));
        $this->transactionModel->process($transaction);
    }

    /**
     *
     */
    public function testProcess()
    {
        $transaction = $this->prepareEntityForSwitchStateSeries(
            TransactionInterface::STATE_INITIAL,
            [
                TransactionInterface::STATE_PENDING,
                TransactionInterface::STATE_APPLIED,
                TransactionInterface::STATE_DONE,
            ]
        );


        $calledList = [];
        $this->prepareEventSeries($transaction, [
            'beginTransaction',
            'commitTransaction',
            'completeTransaction'
        ], $calledList);

        $this->transactionModel->process($transaction);


        $this->assertSame($this->transactionModel, $transaction->getModel());

        $this->assertSame([
            0 => 'beginTransaction.pre',
            1 => 'beginTransaction.post',
            2 => 'commitTransaction.pre',
            3 => 'commitTransaction.post',
            4 => 'completeTransaction.pre',
            5 => 'completeTransaction.post',
        ], $calledList);

    }

    public function testRecoverFromInitial()
    {
        return;
        // FIXME: another prepare method is required because recover performs a data fetch before the update
        $transaction = $this->prepareEntityForSwitchStateSeries(
            TransactionInterface::STATE_INITIAL,
            [
                TransactionInterface::STATE_ABORTED,
            ]
        );


        $calledList = [];
        $this->prepareEventSeries($transaction, [
            'abortTransaction',
        ], $calledList);

        $this->transactionModel->recover($transaction);


        $this->assertSame($this->transactionModel, $transaction->getModel());

        $this->assertSame([
            0 => 'abortTransaction.pre',
            1 => 'abortTransaction.post',
        ], $calledList);

    }


}
