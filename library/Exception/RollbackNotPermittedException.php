<?php
/**
 * Mongo Transactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Exception;

use Exception;

/**
 * Class RollbackNotPermittedException
 */
class RollbackNotPermittedException extends Exception implements ExceptionInterface
{
}
