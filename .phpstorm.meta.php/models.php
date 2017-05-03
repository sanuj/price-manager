<?php
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App{
/**
 * App\Company
 *
 * @property int $id
 * @property string $name
 * @property int $referrer_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Marketplace[] $marketplaces
 * @property-read \App\User $referrer
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Revision[] $revisions
 * @method static \Illuminate\Database\Query\Builder|\App\Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Company whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Company whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Company whereReferrerId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Company whereUpdatedAt($value)
 */
	class Company extends \Eloquent {}
}

namespace App{
/**
 * App\CompanyMarketplace
 *
 * @property int $id
 * @property int $company_id
 * @property int $marketplace_id
 * @property string $credentials
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Company $company
 * @property-read \App\Marketplace $marketplace
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyMarketplace whereCompanyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyMarketplace whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyMarketplace whereCredentials($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyMarketplace whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyMarketplace whereMarketplaceId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyMarketplace whereUpdatedAt($value)
 */
	class CompanyMarketplace extends \Eloquent {}
}

namespace App{
/**
 * App\CompanyProduct
 *
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property int $company_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Revision[] $revisions
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyProduct whereCompanyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyProduct whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyProduct whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyProduct whereSku($value)
 * @method static \Illuminate\Database\Query\Builder|\App\CompanyProduct whereUpdatedAt($value)
 */
	class CompanyProduct extends \Eloquent {}
}

namespace App{
/**
 * App\Marketplace
 *
 * @property int $id
 * @property string $name
 * @property string $website
 * @property string $logo
 * @property string $group
 * @property string $currency
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereCurrency($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereGroup($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereLogo($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Marketplace whereWebsite($value)
 */
	class Marketplace extends \Eloquent {}
}

namespace App{
/**
 * App\MarketplaceListing
 *
 * @property int $id
 * @property int $marketplace_id
 * @property int $company_id
 * @property int $company_product_id
 * @property string $uid
 * @property string $url
 * @property string $ref_num
 * @property int $selling_price
 * @property int $cost_price
 * @property int $min_price
 * @property int $max_price
 * @property float $marketplace_selling_price
 * @property float $marketplace_cost_price
 * @property float $marketplace_min_price
 * @property float $marketplace_max_price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property array $repricing_algorithm
 * @property-read \App\Company $company
 * @property-read \App\CompanyProduct $companyProduct
 * @property-read \App\Marketplace $marketplace
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PriceSnapshot[] $priceSnapshots
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Revision[] $revisions
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereCompanyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereCompanyProductId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereCostPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereMarketplaceCostPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereMarketplaceId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereMarketplaceMaxPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereMarketplaceMinPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereMarketplaceSellingPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereMaxPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereMinPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereRefNum($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereRepricingAlgorithm($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereSellingPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereUid($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MarketplaceListing whereUrl($value)
 */
	class MarketplaceListing extends \Eloquent {}
}

namespace App{
/**
 * App\PriceSnapshot
 *
 * @property int $id
 * @property int $marketplace_listing_id
 * @property int $selling_price
 * @property int $cost_price
 * @property int $min_price
 * @property int $max_price
 * @property float $marketplace_selling_price
 * @property float $marketplace_cost_price
 * @property float $marketplace_min_price
 * @property float $marketplace_max_price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\MarketplaceListing $marketplaceListing
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereCostPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereMarketplaceCostPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereMarketplaceListingId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereMarketplaceMaxPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereMarketplaceMinPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereMarketplaceSellingPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereMaxPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereMinPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereSellingPrice($value)
 * @method static \Illuminate\Database\Query\Builder|\App\PriceSnapshot whereUpdatedAt($value)
 */
	class PriceSnapshot extends \Eloquent {}
}

namespace App{
/**
 * App\Revision
 *
 * @property int $id
 * @property int $user_id
 * @property int $revisionable_id
 * @property string $revisionable_type
 * @property array $from
 * @property array $to
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $revisionable
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereFrom($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereRevisionableId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereRevisionableType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereTo($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Revision whereUserId($value)
 */
	class Revision extends \Eloquent {}
}

namespace App{
/**
 * App\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $company_id
 * @property string $password
 * @property string $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Company $company
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\Znck\Trust\Models\Permission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\Znck\Trust\Models\Role[] $roles
 * @method static \Illuminate\Database\Query\Builder|\App\User whereCompanyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

