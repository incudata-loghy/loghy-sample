<?php

use App\Facades\Loghy;
use App\Models\SocialIdentity;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->socialIdentity = SocialIdentity::factory()->for($this->user)->create();
    $this->uri = route('social_identities.destroy', ['socialIdentity' => $this->socialIdentity]);
});

it('delete social identity', function () {
    Loghy::shouldReceive('deleteUser')->with($this->socialIdentity->loghy_id)->andReturn(true);

    actingAs($this->user)->delete($this->uri)
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Disconnected ✅');
    
    assertModelMissing($this->socialIdentity);
});

it('redirect to login page when not authenticated', function () {
    delete($this->uri)
        ->assertRedirect('login');
});

it("respond Forbidden(403) when requested another user's social identity", function () {
    $anotherSocialIdentity = SocialIdentity::factory()->for(User::factory())->create();

    actingAs($this->user)
        ->delete(route('social_identities.destroy', ['socialIdentity' => $anotherSocialIdentity]))
        ->assertForbidden();
});

it('has error when failed to delete user in Loghy', function () {
    Loghy::shouldReceive('deleteUser')->with($this->socialIdentity->loghy_id)->andThrow(\Exception::class);

    actingAs($this->user)->delete($this->uri)
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Failed to disconnect.');
});