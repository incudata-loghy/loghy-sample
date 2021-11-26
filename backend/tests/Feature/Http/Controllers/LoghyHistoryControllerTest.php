<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\LoghyHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoghyHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testDeleteRedirectLoginPageWhenNotLoggedIn()
    {
        $response = $this->delete(route('loghy_history.destroy'));
        $response->assertRedirect(route('login'));
    }

    public function testDeleteLoghyHistoryIsDeleted()
    {
        $user = User::factory()
            ->has(LoghyHistory::factory()->count(3))
            ->create();

        $response = $this->actingAs($user)
            ->delete(route('loghy_history.destroy'));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseCount('loghy_history', 0);
    }

    public function testDeleteAnotherUserDataIsNotDeleted()
    {
        $user = User::factory()->create();
        User::factory()
            ->has(LoghyHistory::factory()->count(3))
            ->create();

        $response = $this->actingAs($user)
            ->delete(route('loghy_history.destroy'));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseCount('loghy_history', 3);
    }
}
