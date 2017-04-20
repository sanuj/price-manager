<?php

namespace App\Drivers\Marketplace;

use App\CompanyMarketplace;
use App\Contracts\MarketplaceDriverContract;
use App\Marketplace\ProductOffer;
use App\Services\ThrottleService;
use CaponicaAmazonMwsComplete\MwsProductClient;
use DOMDocument;
use Illuminate\Cache\CacheManager;

class AmazonIndiaDriver implements MarketplaceDriverContract
{
    /**
     * @var \App\CompanyMarketplace
     */
    protected $marketplace;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

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
        $this->cache = resolve(CacheManager::class)->store();
    }

    public function setPrice(string $asin, float $price, array $options = [])
    {
        // TODO: Implement setPrice() method.
    }

    /**
     * Get price of given ASIN list.
     *
     * @param string|array $asin
     *
     * @return \App\Marketplace\ProductOffer[][]
     * @throws \Exception
     */
    public function getPrice($asin)
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
                $reviews = data_get($offer, 'SellerFeedbackRating.FeedbackCount');
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

        $listings = data_get($response, 'GetLowestOfferListingsForASINResult.@attributes') === null
            ? $response->get('GetLowestOfferListingsForASINResult')
            : [$response->get('GetLowestOfferListingsForASINResult')];

        $result = [];

        foreach ($listings as $listing) {
            $status = data_get($listing, '@attribute.status');

            if (!hash_equals('Success', $status)) {
                continue;
            }

            $asin = data_get($listing, '@attribute.ASIN');

            $result[$asin] = [];

            $offers = (array)data_get($listing, 'Product.LowestOfferListings.LowestOfferListing', []);

            foreach ($offers as $offer) {
                $is_fulfilled = data_get($offer, 'Qualifiers.FulfillmentChannel') === 'Amazon';
                $rating = intval(data_get($offer, 'Qualifiers.SellerPositiveFeedbackRating', 0)) / 20.0;
                $reviews = data_get($offer, 'SellerFeedbackCount');
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

        $response = $this->toArray($client->getLowestOfferListingsForASIN($request)->toXML());

        $listings = data_get($response, 'GetCompetitivePricingForASINResult.@attributes') === null
            ? $response->get('GetCompetitivePricingForASINResult')
            : [$response->get('GetCompetitivePricingForASINResult')];

        foreach ($listings as $listing) {
            $status = data_get($listing, '@attribute.status');

            if (!hash_equals('Success', $status)) {
                continue;
            }

            $asin = data_get($listing, '@attribute.ASIN');

            $price = floatval(data_get($listing,
                    'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.Price.ListingPrice.Amount'))
                     + floatval(data_get($listing,
                    'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.Price.Shipping.Amount'));
            $is_me = filter_var(data_get($listing,
                'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.@attributes.belongsToRequester'),
                FILTER_VALIDATE_BOOLEAN);

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

    /**
     * @return \CaponicaAmazonMwsComplete\MwsProductClient
     */
    protected function getProductClient(): \CaponicaAmazonMwsComplete\MwsProductClient
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
