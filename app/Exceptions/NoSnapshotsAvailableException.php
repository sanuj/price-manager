<?php

namespace App\Exceptions;

use Exception;

class NoSnapshotsAvailableException extends Exception
{
    private $listing_id = null;

    public function __construct($marketplace_listing_id, $code = 0, Exception $previous = null)
    {
        $this->listing_id = $marketplace_listing_id;
        parent::__construct("No snapshots available for listing ({$marketplace_listing_id}).", $code, $previous);
    }

    public function getListingId() {
        return $this->listing_id;
    }

}
