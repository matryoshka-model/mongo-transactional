<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace MatryoshkaMongoTransactionalTest\Model\TestAsset;

class FakeMongoCursor extends \MongoCursor
{

    protected static $iterator;

    public function __construct()
    {
        // Do not call original ctor
    }

    public static function setIterator($iterator)
    {
        self::$iterator = $iterator;
    }

    public function current()
    {
        return self::$iterator->current();
    }

    public function key()
    {
        return self::$iterator->key();
    }

    public function next()
    {
        return self::$iterator->next();
    }

    public function rewind()
    {
        return self::$iterator->rewind();
    }

    public function valid()
    {
        return self::$iterator->valid();
    }

}