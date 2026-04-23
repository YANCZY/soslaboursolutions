<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserType;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $this->skipUnlessFortifyHas(Features::registration());

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->first();
        $adminUserType = UserType::query()->where('user_type_name', 'Admin')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($adminUserType);
        $this->assertSame($adminUserType->id, $user->user_type_id);
    }
}
