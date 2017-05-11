<?php

namespace App\Exceptions;

use App\MarketplaceListing;
use Exception;

class NoSnapshotsAvailableException extends Exception
{
    private $marketplace_listing = null;

    public function __construct(MarketplaceListing $marketplace_listing, $code = 0, Exception $previous = null)
    {
        $this->listing_id = $marketplace_listing;
        parent::__construct("No snapshots available for listing ({$marketplace_listing->id}).", $code, $previous);
    }

    public function getMarketplaceListing() : MarketplaceListing {
        return $this->marketplace_listing;
    }

}
