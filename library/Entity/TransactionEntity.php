<?php
/**
 * Mongo Transactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Entity;

use Matryoshka\Model\Object\ActiveRecord\AbstractActiveRecord;

/**
 * Class TransactionEntity
 */
class TransactionEntity extends AbstractActiveRecord implements TransactionInterface
{
    use TransactionTrait;

    /**
     * {@inheritdoc}
     */
    public function getHydrator()
    {
        if (!$this->hydrator) {
            $this->hydrator = new TransactionHydrator();
        }
        return $this->hydrator;
    }
}
