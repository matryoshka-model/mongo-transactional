<?php
/**
 * MongoDB Transaction
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransaction\Entity;

use Matryoshka\Model\Hydrator\ClassMethods;
use Matryoshka\Model\Hydrator\Strategy\HasOneStrategy;
use Matryoshka\MongoTransaction\Error\ErrorObject;


class TransactionHydrator extends ClassMethods
{
    public function __construct()
    {
        parent::__construct(true);

        // Strategies
        $this->addStrategy('error', new HasOneStrategy(new ErrorObject()));
    }
}
