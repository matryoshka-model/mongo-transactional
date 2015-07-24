<?php
/**
 * Matryoshka MongoTransactional
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransactional\Error;

use Matryoshka\MongoTransactional\Exception\InvalidArgumentException;
use Zend\Stdlib\ArrayUtils;

/**
 * Trait ErrorTrait
 */
trait ErrorTrait
{
    /**
     * @var string|null
     */
    protected $exceptionClass;

    /**
     * @var int|string|null
     */
    protected $code;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var array
     */
    protected $additionalDetails;

    /**
     * @return string|null
     */
    public function getExceptionClass()
    {
        return $this->exceptionClass;
    }

    /**
     * @param string|null $exceptionClass
     * @return $this
     */
    public function setExceptionClass($exceptionClass)
    {
        $this->exceptionClass = $exceptionClass ? (string) $exceptionClass : null;
        return $this;
    }

    /**
     * @return int|string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int|string|null $code
     * @return $this;
     */
    public function setCode($code)
    {
        if (is_int($code)) {
            $this->code = $code;
        } elseif ($code) {
            $this->code = (string) $code;
        } else {
            $this->code = null;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message ? (string) $message : null;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdditionalDetails()
    {
        return $this->additionalDetails;
    }

    /**
     * @param array|null|\Traversable $additionalDetails
     * @return $this
     */
    public function setAdditionalDetails($additionalDetails)
    {
        if ($additionalDetails instanceof \Traversable) {
            $additionalDetails = ArrayUtils::iteratorToArray($additionalDetails);
        } elseif ($additionalDetails === null) {
            $additionalDetails = [];
        } elseif (!is_array($additionalDetails)) {
            throw new InvalidArgumentException(sprintf(
                'AdditionalDetails must be an array, null or Traversable, "%s" given',
                is_object($additionalDetails) ? get_class($additionalDetails) : gettype($additionalDetails)
            ));
        }

        $this->additionalDetails = $additionalDetails;
        return $this;
    }

    /**
     * Populate the error object from an exception
     *
     * @param \Exception $exception
     * @return $this
     */
    public function fromException(\Exception $exception)
    {
        $this->setCode($exception->getCode());
        $this->setMessage($exception->getMessage());
        $this->setExceptionClass(get_class($exception));
        $this->setAdditionalDetails(null);

        if (method_exists($exception, 'getAdditionalDetails')) {
            $additionalDetails = $exception->getAdditionalDetails();
            if (is_array($additionalDetails) || $additionalDetails instanceof \Traversable) {
                $this->setAdditionalDetails($additionalDetails);
            }
        }

        return $this;
    }
}
