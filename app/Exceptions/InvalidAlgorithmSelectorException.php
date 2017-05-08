<?php

namespace App\Exceptions;

use App\MarketplaceListing;
use Exception;

class InvalidAlgorithmSelectorException extends Exception
{
    public function __construct($index, $code = 0, Exception $previous = null)
    {
        parent::__construct("No algorithm selector exists with index ({$index}).", $code, $previous);
    }

}
