<?php
/**
 * MongoDB Transaction
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransaction\Exception;

use Exception;

class RollbackNotPermittedException extends Exception implements ExceptionInterface
{
}
