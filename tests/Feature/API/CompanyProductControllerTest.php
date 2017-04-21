<?php

namespace Tests\Feature\API;

use App\CompanyProduct;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CompanyProductControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index()
    {
        factory(CompanyProduct::class, 3)->create([
            'company_id' => $this->getUser()->company_id,
        ]);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.read')
                         ->get('/api/company/products');

        $response->assertJsonStructure([
            '0' => ['id'],
            '1' => ['id'],
            '2' => ['id'],
            '_meta' => ['paginator'],
        ]);
    }

    public function test_store()
    {
        $attributes = [
            'name' => 'Foo Product',
            'sku' => 'xx56cb',
        ];

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.create')
                         ->postJson('/api/company/products', $attributes);

        $response->assertStatus(200)->assertJson([]);
        $this->assertDatabaseHas('company_products', $attributes);
    }

    public function test_update()
    {
        $attributes = [
            'sku' => 'xx56cb',
        ];

        $product = factory(CompanyProduct::class)->create(['company_id' => $this->getUser()->company_id]);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.update')
                         ->putJson('/api/company/products/'.$product->getKey(), $attributes);

        $response->assertStatus(200)->assertJson([]);
        $this->assertDatabaseHas('company_products', $attributes);
    }

    public function test_delete()
    {

        $product = factory(CompanyProduct::class)->create(['company_id' => $this->getUser()->company_id]);

        $response = $this->actingAs($this->getUser())
                         ->givePermissionTo('company_product.delete')
                         ->delete('/api/company/products/'.$product->getKey());

        $response->assertStatus(202);
        $this->assertDatabaseMissing('company_products', ['id' => $product->getKey()]);
    }
}
