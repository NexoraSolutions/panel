<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\{Profession, Skill, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateUsersTest extends TestCase
{
    use RefreshDatabase;

    protected $defaultData = [
        'name' => 'David',
        'email' => 'david.ramos@nexora.es',
        'password' => '123456',
        'profession_id' => '',
        'bio' => 'Programador de Laravel y Vue.js',
        'twitter' => 'https://twitter.com/SQMBRA',
        'role' => 'user',
        'state' => 'active',
    ];

    /** @test */
    function it_loads_the_edit_user_page()
    {
        $user = factory(User::class)->create();

        $this->get("/usuarios/{$user->id}/editar") // usuarios/5/editar
        ->assertStatus(200)
            ->assertViewIs('users.edit')
            ->assertSee('Editar usuario')
            ->assertViewHas('user', function ($viewUser) use ($user) {
                return $viewUser->id === $user->id;
            });
    }

    /** @test */
    function it_updates_a_user()
    {
        $user = factory(User::class)->create();

        $oldProfession = factory(Profession::class)->create();
        $user->profile->update([
            'profession_id' => $oldProfession->id,
        ]);

        $oldSkill1 = factory(Skill::class)->create();
        $oldSkill2 = factory(Skill::class)->create();
        $user->skills()->attach([$oldSkill1->id, $oldSkill2->id]);

        $newProfession = factory(Profession::class)->create();
        $newSkill1 = factory(Skill::class)->create();
        $newSkill2 = factory(Skill::class)->create();

        $this->put("/usuarios/{$user->id}", [
            'name' => 'David',
            'email' => 'david.ramos@nexora.es',
            'password' => '123456',
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/SQMBRA',
            'role' => 'admin',
            'profession_id' => $newProfession->id,
            'skills' => [$newSkill1->id, $newSkill2->id],
            'state' => 'inactive',
        ])->assertRedirect("/usuarios/{$user->id}");

        $this->assertCredentials([
            'name' => 'David',
            'email' => 'david.ramos@nexora.es',
            'password' => '123456',
            'role' => 'admin',
            'active' => false,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/SQMBRA',
            'profession_id' => $newProfession->id,
        ]);

        $this->assertDatabaseCount('user_skill', 2);

        $this->assertDatabaseHas('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $newSkill1->id,
        ]);

        $this->assertDatabaseHas('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $newSkill2->id,
        ]);
    }

    /** @test */
    function it_detaches_all_the_skills_if_none_is_checked()
    {
        $user = factory(User::class)->create();

        $oldSkill1 = factory(Skill::class)->create();
        $oldSkill2 = factory(Skill::class)->create();
        $user->skills()->attach([$oldSkill1->id, $oldSkill2->id]);

        $this->put("/usuarios/{$user->id}", $this->withData([]))
            ->assertRedirect("/usuarios/{$user->id}");

        $this->assertDatabaseEmpty('user_skill');
    }

    /** @test */
    function the_name_is_required()
    {
        $this->handleValidationExceptions();

        $user = factory(User::class)->create();

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'name' => '',
            ]))
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('users', ['email' => 'david.ramos@nexora.es']);
    }

    /** @test */
    function the_email_must_be_valid()
    {
        $this->handleValidationExceptions();

        $user = factory(User::class)->create();

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'email' => 'correo-no-valido',
            ]))
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['name' => 'David Ramos']);
    }

    /** @test */
    function the_email_must_be_unique()
    {
        $this->handleValidationExceptions();

        factory(User::class)->create([
            'email' => 'existing-email@example.com',
        ]);

        $user = factory(User::class)->create([
            'email' => 'david.ramos@nexora.es'
        ]);

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'email' => 'existing-email@example.com',
            ]))
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['email']);

        //
    }

    /** @test */
    function the_users_email_can_stay_the_same()
    {
        $user = factory(User::class)->create([
            'email' => 'david.ramos@nexora.es'
        ]);

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'name' => 'David Ramos',
                'email' => 'david.ramos@nexora.es',
            ]))
            ->assertRedirect("usuarios/{$user->id}"); // (users.show)

        $this->assertDatabaseHas('users', [
            'name' => 'David Ramos',
            'email' => 'david.ramos@nexora.es',
        ]);
    }

    /** @test */
    function the_password_is_optional()
    {
        $oldPassword = 'CLAVE_ANTERIOR';

        $user = factory(User::class)->create([
            'password' => bcrypt($oldPassword)
        ]);

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'password' => '',
            ]))
            ->assertRedirect("usuarios/{$user->id}");

        $this->assertCredentials([
            'name' => 'David',
            'email' => 'david.ramos@nexora.es',
            'password' => $oldPassword // VERY IMPORTANT!
        ]);
    }

    /** @test */
    function the_role_is_required()
    {
        $this->handleValidationExceptions();

        $user = factory(User::class)->create();

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'role' => '',
            ]))
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'david.ramos@nexora.es']);
    }

    /** @test */
    function the_state_is_required()
    {
        $this->handleValidationExceptions();

        $user = factory(User::class)->create();

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'state' => null,
            ]))
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['state']);

        $this->assertDatabaseMissing('users', ['email' => 'david.ramos@nexora.es']);
    }

    /** @test */
    function the_state_must_be_valid()
    {
        $this->handleValidationExceptions();

        $user = factory(User::class)->create();

        $this->from("usuarios/{$user->id}/editar")
            ->put("usuarios/{$user->id}", $this->withData([
                'state' => 'invalid',
            ]))
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['state']);

        $this->assertDatabaseMissing('users', ['email' => 'david.ramos@nexora.es']);
    }
}
