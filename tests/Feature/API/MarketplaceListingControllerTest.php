<?php

namespace Tests\Feature\API;

use App\CompanyProduct;
use App\Marketplace;
use App\MarketplaceListing;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MarketplaceListingControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index()
    {
        $product = factory(CompanyProduct::class)->create(['company_id' => $this->getUser()->company_id]);
        factory(MarketplaceListing::class, 3)->create(['company_product_id' => $product->getKey()]);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.read')
                         ->get("/api/company/products/{$product->id}/listings");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '0' => ['id'],
            '1' => ['id'],
            '2' => ['id'],
            '_meta' => ['paginator'],
        ]);
    }

    public function test_create()
    {
        $product = factory(CompanyProduct::class)->create(['company_id' => $this->getUser()->company_id]);
        $marketplace = factory(Marketplace::class)->create();

        $attributes = [
            'marketplace_id' => $marketplace->getKey(),
            'uid' => 'ASIN01',
            'cost_price' => 100,
            'min_price' => 110,
            'max_price' => 300,
        ];

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.create')
                         ->postJson("/api/company/products/{$product->id}/listings", $attributes);

        $response->assertStatus(200);
        $response->assertJson(['id' => 1]);
        $this->assertDatabaseHas('marketplace_listings', $attributes);
    }

    public function test_update()
    {
        $product = factory(CompanyProduct::class)->create(['company_id' => $this->getUser()->company_id]);
        $listing = factory(MarketplaceListing::class)->create(['company_product_id' => $product->getKey()]);

        $attributes = [
            'cost_price' => 100,
            'min_price' => 110,
            'max_price' => 300,
        ];

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.update')
                         ->putJson("/api/company/products/{$product->id}/listings/{$listing->id}", $attributes);

        $response->assertStatus(200);
        $response->assertJson(['id' => 1]);
        $this->assertDatabaseHas('marketplace_listings', $attributes);
    }

    public function test_delete()
    {
        $product = factory(CompanyProduct::class)->create(['company_id' => $this->getUser()->company_id]);
        $listing = factory(MarketplaceListing::class)->create(['company_product_id' => $product->getKey()]);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.delete')
                         ->delete("/api/company/products/{$product->id}/listings/{$listing->id}");

        $response->assertStatus(202);
        $this->assertDatabaseMissing('marketplace_listings', []);
    }
}
