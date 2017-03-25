<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RegistrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_registration_with_referrer()
    {
        $user = factory(User::class)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/register?referrer='.$user->id)
                    ->type('name', 'John Doe')
                    ->type('company', 'Foo Company')
                    ->type('email', 'john@example.com')
                    ->type('password', 'password')
                    ->type('password_confirmation', 'password')
                    ->press('Register')
                    ->assertPathIs('/home');
        });

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('companies', ['name' => 'Foo Company']);
        $this->assertDatabaseHas('companies', ['referrer_id' => $user->id]);
    }
}
