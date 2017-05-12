<?php

namespace App\Exceptions;

use App\MarketplaceListing;

class NoSnapshotsWithOffersException extends MarketplaceListingException
{
    public function __construct(MarketplaceListing $listing)
    {
        $this->listing = $listing;

        parent::__construct("No offers available for listing ({$listing->id}).");
    }
}
