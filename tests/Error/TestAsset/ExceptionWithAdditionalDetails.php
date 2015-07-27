<?php

namespace MatryoshkaMongoTransactionalTest\Error\TestAsset;

/**
 * Class ExceptionWithAdditionalDetails
 */
class ExceptionWithAdditionalDetails extends \Exception
{
    protected $additionalDetails;
    
    /**
     * @return array
     */
    public function getAdditionalDetails()
    {
        return $this->additionalDetails;
    }

    /**
     * @param $additionalDetails
     * @return $this
     */
    public function setAdditionalDetails($additionalDetails)
    {
        $this->additionalDetails = $additionalDetails;
        return $this;
    }
}
