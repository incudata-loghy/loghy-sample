<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexRedirectLoginPageWhenNotLoggedIn()
    {
        $response = $this->get(route('home'));
        $response->assertRedirect(route('login'));
    }

    public function testIndexStatusIsOK()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
    }
}
