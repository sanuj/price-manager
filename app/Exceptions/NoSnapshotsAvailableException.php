<?php

namespace App\Exceptions;

use App\MarketplaceListing;

class NoSnapshotsAvailableException extends MarketplaceListingException
{

    public function __construct(MarketplaceListing $listing)
    {
        $this->listing = $listing;

        parent::__construct("No snapshots available for listing ({$listing->id}).");
    }
}
