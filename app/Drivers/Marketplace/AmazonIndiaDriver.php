<?php

namespace App\Drivers\Marketplace;

use App\CompanyMarketplace;
use App\Contracts\MarketplaceDriverContract;
use App\Exceptions\ThrottleLimitReachedException;
use App\Marketplace\ProductOffer;
use App\MarketplaceListing;
use CaponicaAmazonMwsComplete\AmazonClient\MwsFeedAndReportClient;
use CaponicaAmazonMwsComplete\AmazonClient\MwsProductClient;
use CaponicaAmazonMwsComplete\ClientPack\MwsFeedAndReportClientPack;
use DOMDocument;
use Illuminate\Support\Collection;
use Log;
use MarketplaceWebServiceProducts_Exception;
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

    /**
     * Get price & meta from marketplace API.
     *
     * @param Collection|\App\MarketplaceListing[] $listings
     *
     * @return \App\Marketplace\ProductOffer[][]
     * @throws \App\Exceptions\ThrottleLimitReachedException
     */
    public function getOffers(Collection $listings)
    {
        $ASINs = $listings->pluck('uid')->toArray();

        try {
            return $this->getPriceWithPricedOffersAPI((array)$ASINs);
        } catch (MarketplaceWebServiceProducts_Exception $e) {

        }

        try {
            return $this->getPriceWithOfferListingAPI((array)$ASINs);
        } catch (MarketplaceWebServiceProducts_Exception $e) {

        }

        throw new ThrottleLimitReachedException('Amazon MWS API limit reached.');
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
                $this->debug("getLowestPricedOffersForASIN(${asin}) failed.", (array)$response);

                continue;
            }

            $result[$asin] = [];

            $offers = (array)$this->getItemsFrom($response, 'GetLowestPricedOffersForASINResult.Offers.Offer');

            if (!count($offers)) {
                $this->debug("getLowestPricedOffersForASIN(${asin}) 0 offers.", (array)$response);
            }

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
            'ASINList' => ['ASIN' => $ASINs],
        ];

        $response = $this->toArray($client->getLowestOfferListingsForASIN($request)->toXML());

        $listings = $this->getItemsFrom($response, 'GetLowestOfferListingsForASINResult');

        $result = [];

        foreach ($listings as $listing) {
            $status = data_get($listing, '@attributes.status');
            $asin = data_get($listing, '@attributes.ASIN');

            if (!hash_equals('Success', $status)) {
                $this->debug("getLowestOfferListingForASIN(${asin}) failed.", (array)$listing);

                continue;
            }

            $result[$asin] = [];

            $offers = $this->getItemsFrom($listing, 'Product.LowestOfferListings.LowestOfferListing');

            if (!count($offers)) {
                $this->debug("getLowestOfferListingForASIN(${asin}) 0 offers.", (array)$listing);
            }

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
            $asin = data_get($listing, '@attributes.ASIN');

            if (!hash_equals('Success', $status)) {
                $this->debug("getCompetitivePricingForASIN(${asin}) failed.", (array)$listing);

                continue;
            }

            $price = floatval(data_get($listing,
                    'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.Price.ListingPrice.Amount'))
                     + floatval(data_get($listing,
                    'Product.CompetitivePricing.CompetitivePrices.CompetitivePrice.Price.Shipping.Amount'));

            foreach ($result[$asin] as $offer) {
                if ($offer['price'] <= $price) { // TODO: Improve this. Competitor matching.
                    $offer['has_buy_box'] = true;
                }
            }
        }

        return $result;
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

    protected function debug(string $message, array $extras = [])
    {
        Log::debug("AmazonIndia: ${message}", $extras);
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
     * Get price & meta from marketplace API for owner's listing.
     *
     * @param Collection|\App\MarketplaceListing[] $listings
     *
     * @return \App\Marketplace\ProductOffer[][]
     * @throws \App\Exceptions\ThrottleLimitReachedException
     */
    public function getPrice(Collection $listings)
    {
        $ASINs = $listings->pluck('uid')->toArray();
        $client = $this->getProductClient();
        $request = [
            'SellerId' => $this->credentials['SellerId'],
            'MarketplaceId' => $this->credentials['MarketplaceId'],
            'ASINList' => ['ASIN' => $ASINs],
        ];
        $result = [];

        try {
            $response = $this->toArray($client->getMyPriceForASIN($request)->toXML());

            foreach ($this->getItemsFrom($response, 'GetMyPriceForASINResult') as $listing) {
                $asin = data_get($listing, '@attributes.ASIN');
                if (!hash_equals('Success', data_get($listing, '@attributes.status'))) {
                    $this->debug("getMyPriceForASINResult(${asin}) failed.", (array)$listing);

                    continue;
                }
                $result[$asin] = [];

                $offers = $this->getItemsFrom($listing, 'Product.Offers.Offer');

                if (!count($offers)) {
                    $this->debug("getMyPriceForASINResult(${asin}) 0 offers.", (array)$listing);
                }

                foreach ($offers as $offer) {
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
                foreach ($result[$asin] as $k => $temp) {
                    $result[$asin][$k]['has_buy_box'] = $is_me;
                }
            }

            return $result;
        } catch (MarketplaceWebServiceProducts_Exception $e) {
            throw new ThrottleLimitReachedException('Amazon MWS API limit reached.', 0, $e);
        }
    }

    /**
     * @param \Illuminate\Support\Collection $listings
     */
    public function setPrice(Collection $listings)
    {
        $messages = $listings->reduce(function ($messages, MarketplaceListing $listing) {
            return $messages.PHP_EOL.<<<MESSAGE
<Message>
    <MessageID>{$listing->getKey()}</MessageID> 
    <Price>
      <SKU>{$listing->companyProduct->sku}</SKU>
      <StandardPrice currency="{$listing->marketplace->currency}">{$listing->marketplace_selling_price}</StandardPrice>
    </Price>
</Message>
MESSAGE;
        }, '');

        $feed = <<<FEED
<?xml version="1.0" encoding="utf-8"?>
  <AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">
  <Header>
    <DocumentVersion>1.01</DocumentVersion>
    <MerchantIdentifier>M_SELLER_354577</MerchantIdentifier>
  </Header>
  
  <MessageType>Price</MessageType> 

  ${messages}
</AmazonEnvelope>
FEED;

        // TODO: Throttle it.

        $client = $this->getFeedClient();

        $stream = fopen('php://temp', 'rw+');
        fwrite($stream, $feed);
        rewind($stream);

        $feed = trim($feed);

        $hash = base64_encode(md5($feed, true));
        rewind($stream);

        $request = [
            'Merchant' => $this->credentials['SellerId'],
            'FeedType' => MwsFeedAndReportClientPack::FEED_TYPE_PAI_PRICING,
            'FeedContent' => $stream,
            'ContentMd5' => $hash,
        ];

        $response = $client->submitFeed($request);

        fclose($stream);

        $this->debug('Do something with setPrice() response.', (array)$response);
        // TODO: Ensure price is updated.
    }

    protected function getFeedClient(): MwsFeedAndReportClient
    {
        return new MwsFeedAndReportClient(
            $this->credentials['AWSAccessKeyId'],
            $this->credentials['SecretKey'],
            [
                'ServiceURL' => $this->credentials['FeedServiceURL'],
                'ProxyHost' => null,
                'ProxyPort' => -1,
                'ProxyUsername' => null,
                'ProxyPassword' => null,
                'MaxErrorRetry' => 3,
            ],
            $this->credentials['name'],
            $this->credentials['version']
        );
    }

    public function use (CompanyMarketplace $marketplace, array $credentials = []): MarketplaceDriverContract
    {
        $this->marketplace = $marketplace;
        $this->credentials = $credentials = array_merge($this->credentials, $marketplace->credentials, $credentials);

        return $this;
    }

    /**
     * Rules to validate marketplace credentials.
     *
     * @return array
     */
    public function getCredentialRules(): array
    {
        return [
            'SellerId' => [
                'description' => 'Amazon Seller ID',
                'rule' => 'required',
            ],

            'AWSAccessKeyId' => [
                'description' => 'Amazon Access Key ID',
                'rule' => 'required',
            ],

            'SecretKey' => [
                'description' => 'Amazon Secret Key',
                'rule' => 'required',
            ],

            'MarketplaceId' => [
                'description' => 'Amazon India Marketplace ID',
                'rule' => 'required',
                'default' => 'A21TJRUUN4KGV',
            ],
        ];
    }
}
