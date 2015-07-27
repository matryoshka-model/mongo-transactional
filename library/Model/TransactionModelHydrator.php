<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Model;

use Matryoshka\Model\Hydrator\Strategy\HasOneStrategy;
use Matryoshka\Model\Hydrator\Strategy\SetTypeStrategy;
use Matryoshka\Model\Wrapper\Mongo\Hydrator\ClassMethods as MatryoshkaMongoWrapperClassMethods;
use Matryoshka\MongoTransactional\Error\ErrorObject;

/**
 * Class TransactionModelHydrator
 */
class TransactionModelHydrator extends MatryoshkaMongoWrapperClassMethods
{
    /**
     * {@inheritdoc}
     */
    public function __construct($underscoreSeparatedKeys = true)
    {
        parent::__construct($underscoreSeparatedKeys);

        // Strategy
        $this->addStrategy('state', new SetTypeStrategy('string', 'string'));
        $this->addStrategy('type', new SetTypeStrategy('string', 'string'));
        $this->addStrategy('recovery', new SetTypeStrategy('bool', 'bool'));
        $this->addStrategy('error', new HasOneStrategy(new ErrorObject(), true));
    }
}
