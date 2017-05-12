<?php

namespace App\Exceptions;

use App\MarketplaceListing;

class InvalidMinPriceException extends MarketplaceListingException
{
    public function __construct(MarketplaceListing $listing)
    {
        $this->listing = $listing;

        parent::__construct("Invalid minimum price for listing ({$listing->getKey()}).");
    }
}
