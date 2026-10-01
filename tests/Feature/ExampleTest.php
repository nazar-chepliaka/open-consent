<?php

use App\Models\User;

test('the public home page guides guests to authentication', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Open Consent')
        ->assertSee('Персональний архів правових документів, договорів і згод.')
        ->assertSee('Створити обліковий запис')
        ->assertSee('Увійти')
        ->assertSee(route('register', absolute: false))
        ->assertSee(route('login', absolute: false));
});

test('the public home page guides authenticated users to the dashboard', function () {
    $user = User::factory()->create(['name' => 'Nazar User']);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Вітаємо, Nazar User.')
        ->assertSee('Перейти до Open Consent')
        ->assertSee(route('dashboard', absolute: false))
        ->assertDontSee('Створити обліковий запис');
});
