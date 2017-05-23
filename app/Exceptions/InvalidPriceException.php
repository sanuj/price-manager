<?php

namespace App\Exceptions;

use App\MarketplaceListing;

class InvalidPriceException extends MarketplaceListingException
{
    public function __construct(MarketplaceListing $listing, $type='')
    {
        $this->listing = $listing;

        parent::__construct("Invalid ".$type." price for listing ({$listing->getKey()}).");
    }
}
