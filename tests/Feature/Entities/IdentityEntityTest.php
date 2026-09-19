<?php

use App\Entities\IdentityEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes the user profile claims and uses the user id as the subject', function () {
    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@3ag.local',
    ]);

    $entity = new IdentityEntity;
    $entity->setIdentifier($user->getKey());

    expect($entity->getIdentifier())->toBe((string) $user->getKey());
    expect($entity->getClaims())->toBe([
        'email' => 'ada@3ag.local',
        'email_verified' => true,
        'name' => 'Ada Lovelace',
        'preferred_username' => 'ada@3ag.local',
    ]);
});

it('reports an unverified email address as unverified', function () {
    $user = User::factory()->unverified()->create();

    $entity = new IdentityEntity;
    $entity->setIdentifier($user->getKey());

    expect($entity->getClaims()['email_verified'])->toBeFalse();
});
