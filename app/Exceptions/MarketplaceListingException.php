<?php

namespace App\Exceptions;

use App\MarketplaceListing;
use Exception;

abstract class MarketplaceListingException extends Exception
{
    /**
     * @var MarketplaceListing
     */
    protected $listing;

    /**
     * @return MarketplaceListing
     */
    public function getMarketplaceListing(): MarketplaceListing
    {
        return $this->listing;
    }
}
