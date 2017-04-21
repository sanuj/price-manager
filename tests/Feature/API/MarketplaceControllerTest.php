<?php

namespace Tests\Feature\API;

use App\CompanyMarketplace;
use App\Marketplace;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MarketplaceControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index()
    {
        $company = $this->getUser()->company;
        $marketplaces = factory(Marketplace::class, 3)->create();
        factory(Marketplace::class)->create(); // --
        $credentials = ['credentials' => 'foo;bar'];

        $company->marketplaces()->saveMany($marketplaces, [$credentials, $credentials, $credentials]);
        $this->assertDatabaseHas('company_marketplace', []);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('marketplace.read')
                         ->get('/api/company/marketplaces');

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
        $marketplace = factory(Marketplace::class)->create();

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('marketplace.create')
                         ->postJson('/api/company/marketplaces', [
                             'marketplace_id' => $marketplace->getKey(),
                             'credentials' => ['username' => 'foo', 'password' => 'bar'],
                         ]);

        $response->assertStatus(200);
        $response->assertJson(['id' => 1, '_type' => \App\CompanyMarketplace::class]);
        $this->assertDatabaseHas('company_marketplace', [
            'company_id' => $this->getUser()->company_id,
            'marketplace_id' => $marketplace->getKey(),
            'id' => 1,
        ]);
    }

    public function test_update()
    {
        $marketplace = factory(Marketplace::class)->create();
        $company = $this->getUser()->company;

        $original = ['credentials' => ['username' => 'foo', 'password' => 'bar']];
        $updated = ['credentials' => ['username' => 'bar', 'password' => 'baz']];

        /** @var \App\CompanyMarketplace $pivot */
        $pivot = $company->marketplaces()->newPivot($original);
        $pivot->marketplace()->associate($marketplace);
        $pivot->company()->associate($company);
        $pivot->save();

        $this->assertEquals($original['credentials'], $pivot->credentials);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('marketplace.update')
                         ->putJson('/api/company/marketplaces/'.$marketplace->getKey(), $updated);

        $response->assertStatus(200);

        $pivot = $company->marketplaces()
                         ->where('marketplace_id', $marketplace->getKey())
                         ->first()->pivot;
        $this->assertEquals($updated['credentials'], $pivot->credentials);
    }

    public function test_destroy()
    {
        $marketplace = factory(Marketplace::class)->create();
        $company = $this->getUser()->company;

        $original = ['credentials' => ['username' => 'foo', 'password' => 'bar']];

        /** @var \App\CompanyMarketplace $pivot */
        $pivot = $company->marketplaces()->newPivot($original);
        $pivot->marketplace()->associate($marketplace);
        $pivot->company()->associate($company);
        $pivot->save();

        $this->assertDatabaseHas('company_marketplace', []);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('marketplace.delete')
                         ->delete('/api/company/marketplaces/'.$marketplace->getKey());

        $response->assertStatus(202);
        $this->assertDatabaseMissing('company_marketplace', []);
    }
}
