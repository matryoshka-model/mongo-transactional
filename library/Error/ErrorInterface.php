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
 * Interface ErrorInterface
 */
interface ErrorInterface
{
    /**
     * @return string|null
     */
    public function getExceptionClass();

    /**
     * @param string|null $exceptionClass
     * @return $this
     */
    public function setExceptionClass($exceptionClass);

    /**
     * @return int|string|null
     */
    public function getCode();

    /**
     * @param int|string|null $code
     * @return $this;
     */
    public function setCode($code);

    /**
     * @return string|null
     */
    public function getMessage();

    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return array
     */
    public function getAdditionalDetails();

    /**
     * @param array|null|\Traversable $additionalDetails
     * @return $this
     */
    public function setAdditionalDetails($additionalDetails);

    /**
     * Populate the error object from an exception
     *
     * @param \Exception $exception
     * @return $this
     */
    public function fromException(\Exception $exception);
}
