<?php

namespace App\Exceptions;

use App\MarketplaceListing;
use Exception;

class InvalidMinPriceException extends Exception
{
    public function __construct(MarketplaceListing $listing, $code = 0, Exception $previous = null)
    {
        parent::__construct("Invalid minimum price for listing ({$listing->getKey()}).", $code, $previous);
    }

}
