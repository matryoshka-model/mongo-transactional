<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Error;

/**
 * Interface ErrorAwareInterface
 */
interface ErrorAwareInterface
{
    /**
     * Get the error (can be null)
     *
     * @return ErrorInterface|null
     */
    public function getError();

    /**
     * Set the error
     *
     * @param ErrorInterface|null $error
     * @return $this
    */
    public function setError(ErrorInterface $error = null);
}
