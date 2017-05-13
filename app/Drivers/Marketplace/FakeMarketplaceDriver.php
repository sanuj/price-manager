<?php

namespace App\Drivers\Marketplace;

use App\CompanyMarketplace;
use App\Contracts\MarketplaceDriverContract;
use App\Marketplace\ProductOffer;
use Faker\Generator;
use Illuminate\Support\Collection;

class FakeMarketplaceDriver implements MarketplaceDriverContract
{

    /**
     * Get price & meta from marketplace API for owner's listing.
     *
     * @param \App\MarketplaceListing[]|Collection $listings
     *
     * @return \App\Marketplace\ProductOffer[][]
     */
    public function getOffers(Collection $listings)
    {
        $UIDs = $listings->pluck('uid');
        $result = [];
        /** @var Generator $f */
        $f = resolve(Generator::class);

        foreach ($UIDs as $UID) {
            $n = rand(0, 10);

            $result[$UID] = [];

            while ($n--) {
                $result[$UID][] = new ProductOffer([
                    'is_fulfilled' => $f->boolean(),
                    'rating' => $f->randomFloat(2, 0, 5),
                    'reviews' => $f->randomNumber(),
                    'price' => $f->randomFloat(2, 0.01),
                    'currency' => $f->currencyCode,
                ]);
            }
        }

        return $result;
    }

    /**
     * @param \App\MarketplaceListing[]|\Illuminate\Support\Collection $listings
     *
     * @return void TODO: Figure it out. (Return Value)
     * TODO: Figure it out. (Return Value)
     */
    public function setPrice(Collection $listings)
    {
        // TODO: Implement setPrice() method.
    }

    /**
     * Get price & meta from marketplace API.
     *
     * @param \App\MarketplaceListing[]|Collection $listings
     *
     * @return \App\Marketplace\ProductOffer[][]
     */
    public function getPrice(Collection $listings)
    {
        $UIDs = $listings->pluck('uid');
        $result = [];
        /** @var Generator $f */
        $f = resolve(Generator::class);

        foreach ($UIDs as $UID) {
            $n = rand(0, 10);

            $result[$UID] = [];

            while ($n--) {
                $result[$UID][] = [
                    'is_fulfilled' => $f->boolean(),
                    'rating' => $f->randomFloat(2, 0, 5),
                    'reviews' => $f->randomNumber(),
                    'price' => $f->randomFloat(2, 0.01),
                    'currency' => $f->currencyCode,
                ];
            }
        }

        return $result;
    }

    public function use (CompanyMarketplace $marketplace, array $credentials = []): MarketplaceDriverContract
    {
        return $this;
    }

    /**
     * Rules to validate marketplace credentials.
     *
     * @return array
     */
    public function getCredentialRules(): array
    {
        return [];
    }
}
