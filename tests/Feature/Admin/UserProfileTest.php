<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\{Profession, User, UserProfile};
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsersProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $defaultData = [
        'name' => 'David',
        'email' => 'david.ramos@nexora.es',
        'bio' => 'Programador de Laravel y Vue.js',
        'twitter' => 'https://twitter.com/SQMBRA',
    ];

    /** @test */
    function a_user_can_edit_its_profile()
    {
        $user = factory(User::class)->create();
        $user->profile()->save(factory(UserProfile::class)->make());

        $newProfession = factory(Profession::class)->create();

        //$this->actingAs($user);

        $response = $this->get('/editar-perfil/');

        $response->assertStatus(200);

        $response = $this->put('/editar-perfil/', [
            'name' => 'David',
            'email' => 'david.ramos@nexora.es',
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/SQMBRA',
            'profession_id' => $newProfession->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'David',
            'email' => 'david.ramos@nexora.es',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/SQMBRA',
            'profession_id' => $newProfession->id,
        ]);
    }

    /** @test */
    function the_user_cannot_change_its_role()
    {
        $user = factory(User::class)->create([
            'role' => 'user'
        ]);

        $response = $this->put('/editar-perfil/', $this->withData([
            'role' => 'admin',
        ]));

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'user',
        ]);
    }

    /** @test */
    function the_user_cannot_change_its_password()
    {
        factory(User::class)->create([
            'password' => bcrypt('old123'),
        ]);

        $response = $this->put('/editar-perfil/', $this->withData([
            'email' => 'david.ramos@nexora.es',
            'password' => 'new456'
        ]));

        $response->assertRedirect();

        $this->assertCredentials([
            'email' => 'david.ramos@nexora.es',
            'password' => 'old123',
        ]);
    }
}
