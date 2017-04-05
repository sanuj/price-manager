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
                         ->get('/api/products?'.http_build_query([
                                 '_schema' => [
                                     'products' => [
                                         'id' => true,
                                     ],
                                 ],
                             ]));

        $response->assertJsonStructure([
            'products' => [
                '0' => ['id'],
                '1' => ['id'],
                '2' => ['id'],
                '_meta' => ['paginator'],
            ],
        ]);
    }
}
