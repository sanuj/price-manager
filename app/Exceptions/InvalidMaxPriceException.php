<?php

namespace App\Exceptions;

use App\MarketplaceListing;

class InvalidMaxPriceException extends MarketplaceListingException
{
    public function __construct(MarketplaceListing $listing)
    {
        $this->listing = $listing;

        parent::__construct("Invalid maximum price for listing ({$listing->getKey()}).");
    }
}
