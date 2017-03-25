<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class EasyEcomAuthTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $user = factory(User::class)->create();
        $secret = str_random();
        $expires = time() + 60;
        $email = $user->email;
        $token = hash_hmac('sha512', "${email}:${expires}", $secret);

        $query = http_build_query(compact('email', 'expires', 'token'));

        // Override services.easyecom.secret
        config(['services.easyecom.secret' => $secret]);
        $response = $this->get('/login/services/easyecom?'.$query);

        $response->assertRedirect('/home');
    }
}
