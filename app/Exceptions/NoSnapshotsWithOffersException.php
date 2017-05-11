<?php

namespace App\Exceptions;

use App\MarketplaceListing;
use Exception;

class NoSnapshotsWithOffersException extends Exception
{
    private $marketplace_listing = null;

    public function __construct(MarketplaceListing $marketplace_listing, $code = 0, Exception $previous = null)
    {
        parent::__construct("No snapshots with offers available for listing ({$marketplace_listing->id}).", $code, $previous);
    }

    public function getMarketplaceListing() : MarketplaceListing {
        return $this->marketplace_listing;
    }

}
