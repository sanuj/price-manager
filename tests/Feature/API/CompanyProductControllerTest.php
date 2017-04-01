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
        factory(CompanyProduct::class, 3)->create();

        $response = $this->get('/api/products?'.http_build_query([
                '_schema' => [
                    'products' => [
                        'id' => true,
                    ],
                ],
            ]))->assertJsonStructure(['products' => ['*' => ['id']]]);
    }
}
