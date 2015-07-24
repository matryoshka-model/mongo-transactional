<?php
/**
 * MongoDB Transaction
 *
 * @link        https://github.com/matryoshka-model/mongo-transaction
 * @copyright   Copyright (c) 2015, Ripa Club
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Matryoshka\MongoTransaction\Error;

use Zend\Stdlib\Hydrator\HydratorAwareInterface;
use Zend\Stdlib\Hydrator\HydratorAwareTrait;

/**
 * Class ErrorObject
 */
class ErrorObject implements ErrorInterface, HydratorAwareInterface
{
    use ErrorTrait;
    use HydratorAwareTrait;

    /**
     * @param \Exception $exception
     */
    public function __construct(\Exception $exception = null)
    {
        if ($exception) {
            $this->fromException($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHydrator()
    {
        if (!$this->hydrator) {
            $this->hydrator = new ClassMethods();
        }
        return $this->hydrator;
    }
}
