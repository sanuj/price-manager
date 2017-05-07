<?php

namespace App\Exceptions;

use Exception;

class NoSnapshotsAvailableException extends Exception
{
    public function __construct($marketplace_listing_id, $code = 0, Exception $previous = null)
    {
        parent::__construct("No snapshots available for listing ({$marketplace_listing_id}).", $code, $previous);
    }

}
