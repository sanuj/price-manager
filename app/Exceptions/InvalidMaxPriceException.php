<?php

namespace App\Exceptions;

use App\MarketplaceListing;
use Exception;

class InvalidMaxPriceException extends Exception
{
    public function __construct(MarketplaceListing $listing, $code = 0, Exception $previous = null)
    {
        parent::__construct("Invalid maximum price for listing ({$listing->getKey()}).", $code, $previous);
    }

}
