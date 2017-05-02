<?php

namespace App\Drivers\Marketplace;

use App\CompanyMarketplace;
use App\Contracts\MarketplaceDriverContract;
use App\Marketplace\ProductOffer;
use App\Services\ThrottleService;
use CaponicaAmazonMwsComplete\AmazonClient\MwsProductClient;
use DOMDocument;
use SimpleXMLElement;

class AmazonIndiaDriver implements MarketplaceDriverContract
{
    protected $error_reporting;
    /**
     * @var \App\CompanyMarketplace
     */
    protected $marketplace;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * @var ThrottleService
     */
    protected $pricedOfferThrottle;

    /**
     * @var ThrottleService
     */
    protected $offerListingThrottle;

    public function __construct(array $credentials = ['ItemCondition' => 'New'])
    {
        $this->credentials = $credentials;
    }

    public function setPrice(string $asin, float $price, array $options = [])
    {

    }

    /**
     * @param string|array $ASINs
     *
     * @return ProductOffer[][]
     */
    public function getPrice($ASINs)
    {
        $client = $this->getProductClient();
        $request = [
            'SellerId' => $this->credentials['SellerId'],
            'MarketplaceId' => $this->credentials['MarketplaceId'],
            'ItemCondition' => $this->credentials['ItemCondition'],
            'ASINList' => ['ASIN' => $ASINs],
        ];

        $response = $this->toArray($client->getMyPriceForASIN($request)->toXML());

        $result = [];

        foreach ($this->getItemsFrom($response, 'GetMyPriceForASINResult') as $listing) {
            if (!hash_equals('Success', data_get($listing, '@attributes.status'))) {
                continue;
            }

            $asin = data_get($listing, '@attributes.ASIN');
            $result[$asin] = [];

            foreach ($this->getItemsFrom($listing, 'Product.Offers.Offer') as $offer) {
                $is_fulfilled = data_get($offer, 'FulfillmentChannel') === 'AMAZON';
                $rating = -1;
                $reviews = -1;
                $price = floatval(data_get($offer, 'BuyingPrice.ListingPrice.Amount')) + floatval(data_get($offer,
                        'BuyingPrice.Shipping.Amount'));
                $currency = data_get($offer, 'BuyingPrice.ListingPrice.CurrencyCode');
                $has_buy_box = null;

                $result[$asin][] = compact('is_fulfilled', 'reviews', 'rating', 'price', 'currency', 'has_buy_box');
            }
        }

        if (count($request) === 0) {
            return $result;
        }

        $ASINs = array_keys($result);

        $request = [
            'SellerId' => $this->credentials['SellerId'],
            'MarketplaceId' => $this->credentials['MarketplaceId'],
            'ItemCondition' => $this->credentials['ItemCondition'],
            'ASINList' => ['ASIN' => $ASINs],
        ];

        $response = $this->toArray($client->getCompetitivePricingForASIN($request)->toXML());

        foreach ($this->getItemsFrom($response, 'GetCompetitivePricingForASINResult') as $key => $listing) {
            $status = (string)data_get($listing, '@attributes.status');

            if (!hash_equals('Success', $status)) {
                continue;
            }

            $asin = data_get($listing, '@attributes.ASIN');
            $is_me = filter_var(data_get($listing,
                'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.@attributes.belongsToRequester'),
                FILTER_VALIDATE_BOOLEAN);

            // Can't figure out which offer has buy-box, so updating all offers.
            foreach($result[$asin] as $k => $temp)
                $result[$asin][$k]['has_buy_box'] = $is_me;
        }

        return $result;
    }

    protected function getItemsFrom($source, $key)
    {
        if (data_get($source, $key) === null) {
            return [];
        }
        if (data_get($source, $key.'.0') === null) {
            return [data_get($source, $key)];
        }

        return data_get($source, $key);
    }

    /**
     * Get price of given ASIN list.
     *
     * @param string|array $asin
     *
     * @return \App\Marketplace\ProductOffer[][]
     * @throws \Exception
     */
    public function getOffers($asin)
    {
        if ($this->canUsePricedOffersAPI()) {
            return $this->getPriceWithPricedOffersAPI((array)$asin);
        } elseif ($this->canUseOfferListingAPI()) {
            return $this->getPriceWithOfferListingAPI((array)$asin);
        } else {
            throw new \Exception('Amazon MWS API limit reached.');
        }
    }

    public function getPriceWithPricedOffersAPI(array $ASINs)
    {
        $client = $this->getProductClient();

        $result = [];

        foreach ($ASINs as $asin) {
            $request = [
                'SellerId' => $this->credentials['SellerId'],
                'MarketplaceId' => $this->credentials['MarketplaceId'],
                'ASIN' => $asin,
                'ItemCondition' => $this->credentials['ItemCondition'],
            ];

            $response = $this->toArray($client->getLowestPricedOffersForASIN($request)->toXML());

            $status = data_get($response, 'GetLowestPricedOffersForASINResult.@attributes.status');

            if (!hash_equals('Success', $status)) {
                continue;
            }

            $result[$asin] = [];

            $offers = (array)data_get($response, 'GetLowestPricedOffersForASINResult.Offers.Offer');

            foreach ($offers as $offer) {
                $is_fulfilled = filter_var(data_get($offer, 'IsFulfilledByAmazon'), FILTER_VALIDATE_BOOLEAN);
                $rating = intval(data_get($offer, 'SellerFeedbackRating.SellerPositiveFeedbackRating', 0)) / 20.0;
                $reviews = intval(data_get($offer, 'SellerFeedbackRating.FeedbackCount'));
                $price = floatval(data_get($offer, 'ListingPrice.Amount')) + floatval(data_get($offer,
                        'Shipping.Amount'));
                $currency = data_get($offer, 'ListingPrice.CurrencyCode');
                $has_buy_box = filter_var(data_get($offer, 'IsBuyBoxWinner'), FILTER_VALIDATE_BOOLEAN);

                $result[$asin][] = new ProductOffer(
                    compact('is_fulfilled', 'rating', 'price', 'reviews', 'currency', 'has_buy_box')
                );
            }
        }

        return $result;
    }

    public function getPriceWithOfferListingAPI(array $ASINs)
    {
        $client = $this->getProductClient();

        $request = [
            'SellerId' => $this->credentials['SellerId'],
            'MarketplaceId' => $this->credentials['MarketplaceId'],
            'ItemCondition' => $this->credentials['ItemCondition'],
            'ASINList' => ['ASIN' => $ASINs],
        ];

        $response = $this->toArray($client->getLowestOfferListingsForASIN($request)->toXML());

        $listings = $this->getItemsFrom($response, 'GetLowestOfferListingsForASINResult');

        $result = [];

        foreach ($listings as $listing) {
            $status = data_get($listing, '@attributes.status');

            if (!hash_equals('Success', $status)) {
                continue;
            }

            $asin = data_get($listing, '@attributes.ASIN');

            $result[$asin] = [];

            $offers = $this->getItemsFrom($listing, 'Product.LowestOfferListings.LowestOfferListing');

            foreach ($offers as $offer) {
                $is_fulfilled = data_get($offer, 'Qualifiers.FulfillmentChannel') === 'Amazon';
                $rating = intval(data_get($offer, 'Qualifiers.SellerPositiveFeedbackRating', 0)) / 20.0;
                $reviews = intval(data_get($offer, 'SellerFeedbackCount'));
                $price = floatval(data_get($offer, 'Price.ListingPrice.Amount')) + floatval(data_get($offer,
                        'Price.Shipping.Amount'));
                $currency = data_get($offer, 'Price.ListingPrice.CurrencyCode');
                $has_buy_box = null;

                $result[$asin][] = new ProductOffer(
                    compact('is_fulfilled', 'rating', 'price', 'reviews', 'currency', 'has_buy_box')
                );
            }
        }

        $ASINs = array_keys($result);

        $request = [
            'SellerId' => $this->credentials['SellerId'],
            'MarketplaceId' => $this->credentials['MarketplaceId'],
            'ItemCondition' => $this->credentials['ItemCondition'],
            'ASINList' => ['ASIN' => $ASINs],
        ];

        $response = $this->toArray($client->getCompetitivePricingForASIN($request)->toXML());

        foreach ($this->getItemsFrom($response, 'GetCompetitivePricingForASINResult') as $listing) {
            $status = data_get($listing, '@attributes.status');

            if (!hash_equals('Success', $status)) {
                continue;
            }

            $asin = data_get($listing, '@attributes.ASIN');
            $price = floatval(data_get($listing,
                    'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.Price.ListingPrice.Amount'))
                     + floatval(data_get($listing,
                    'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.Price.Shipping.Amount'));

            foreach ($result[$asin] as $offer) {
                if ($offer['price'] <= $price) {
                    $offer['has_buy_box'] = true;
                }
            }
        }

        return $result;
    }

    public function use (CompanyMarketplace $marketplace, array $credentials = []): MarketplaceDriverContract
    {
        $this->marketplace = $marketplace;
        $this->credentials = $credentials = array_merge($this->credentials, $marketplace->credentials, $credentials);

        return $this;
    }

    protected function cacheKey(string $key): string
    {
        return '__MARKETPLACE__AMAZON_INDIA__'.$this->marketplace->getKey().'__'.$key;
    }

    protected function canUseOfferListingAPI(): bool
    {
        if ($this->offerListingThrottle === null) {
            $this->offerListingThrottle = new ThrottleService($this->cacheKey('OfferListing'), 20, 1);
        }

        return $this->offerListingThrottle->attempt();
    }

    /**
     * @return mixed
     */
    protected function canUsePricedOffersAPI(): bool
    {
        if ($this->pricedOfferThrottle === null) {
            $this->pricedOfferThrottle = new ThrottleService($this->cacheKey('PricedOffer'), 12, 1);
        }

        return $this->pricedOfferThrottle->attempt();
    }


    protected function getProductClient(): MwsProductClient
    {
        return new MwsProductClient(
            $this->credentials['AWSAccessKeyId'],
            $this->credentials['SecretKey'],
            $this->credentials['name'],
            $this->credentials['version'],
            ['ServiceURL' => $this->credentials['ServiceURL']]
        );
    }

    /**
     * @param string $xml
     *
     * @return \Illuminate\Support\Collection
     */
    protected function toArray($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;

        $data = new SimpleXMLElement($dom->saveXML());

        return collect(json_decode(json_encode($data, true), true));
    }
}
