<?php

use App\Models\User;
use App\Models\Vault;
use App\Services\VaultService;
use Illuminate\Support\Facades\Hash;

test('registration page is accessible to guests', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Створити обліковий запис');
});

test('authenticated users cannot register again', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('register'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->actingAs($user)
        ->post(route('register.store'), [
            'name' => 'Second Account',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(User::query()->where('email', 'second@example.com')->exists())->toBeFalse();
});

test('valid registration creates user personal vault owner membership and authenticates', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Nazar User',
        'email' => 'nazar@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'nazar@example.com')->firstOrFail();
    $vault = Vault::query()->where('owner_id', $user->id)->firstOrFail();

    expect($user->name)->toBe('Nazar User')
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($vault->name)->toBe('Особистий архів')
        ->and($user->vaults()->whereKey($vault->id)->wherePivot('role', 'owner')->exists())->toBeTrue();
});

test('duplicate email is rejected', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register', absolute: false))
        ->assertSessionHasErrors('email');

    expect(User::count())->toBe(1)
        ->and(Vault::count())->toBe(0);
});

test('invalid email is rejected', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register', absolute: false))
        ->assertSessionHasErrors('email');

    expect(User::count())->toBe(0)
        ->and(Vault::count())->toBe(0);
});

test('password confirmation mismatch is rejected', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])
        ->assertRedirect(route('register', absolute: false))
        ->assertSessionHasErrors('password');

    expect(User::count())->toBe(0)
        ->and(Vault::count())->toBe(0);
});

test('failed validation does not create user or vault records', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('register', absolute: false))
        ->assertSessionHasErrors(['name', 'email', 'password']);

    expect(User::count())->toBe(0)
        ->and(Vault::count())->toBe(0);
});

test('vault creation failure rolls back the registered user', function () {
    $this->mock(VaultService::class, function ($mock) {
        $mock->shouldReceive('createForUser')->once()->andThrow(new RuntimeException('Vault failed.'));
    });

    $this->withoutExceptionHandling();

    try {
        $this->post(route('register.store'), [
            'name' => 'Rollback User',
            'email' => 'rollback@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Vault failed.');
    }

    expect(User::count())->toBe(0)
        ->and(Vault::count())->toBe(0);
});
