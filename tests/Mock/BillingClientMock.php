<?php

namespace App\Tests\Mock;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Exception\BillingValidationException;
use App\Service\BillingClient;

final class BillingClientMock extends BillingClient
{
    private array $users = [
        'user01@mail.ru' => [
            'password' => 'password',
            'roles' => ['ROLE_USER'],
            'balance' => '1000.00',
        ],
        'user02@mail.ru' => [
            'password' => 'password',
            'roles' => ['ROLE_SUPER_ADMIN'],
            'balance' => '100.00',
        ],
    ];

    public function __construct()
    {
    }

    public function auth(string $email, string $password): string
    {
        if ($email === 'unavailable@example.com') {
            throw new BillingUnavailableException();
        }

        if ($email === '') {
            throw new BillingValidationException('Email не может быть пустым.', [], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BillingValidationException('Некорректный email.', [], 422);
        }

        if ($password === '') {
            throw new BillingValidationException('Пароль не может быть пустым.', [], 422);
        }

        $user = $this->users[$email] ?? null;

        if ($user === null || $user['password'] !== $password) {
            throw new BillingException('Invalid credentials.', 401);
        }

        return $email;
    }

    public function register(string $email, string $password): array
    {
        if ($email === 'unavailable@example.com') {
            throw new BillingUnavailableException();
        }

        $violations = [];

        if ($email === '') {
            $violations['email'] = 'Введите email';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $violations['email'] = 'Некорректный email';
        }

        if ($password === '') {
            $violations['password'] = 'Введите пароль';
        } elseif (mb_strlen($password) < 6) {
            $violations['password'] = 'Пароль должен быть не менее 6 символов';
        }

        if (isset($this->users[$email])) {
            $violations['email'] = 'Пользователь с таким email уже существует';
        }

        if ($violations !== []) {
            throw new BillingValidationException('Ошибки валидации', $violations, 422);
        }

        $this->users[$email] = [
            'password' => $password,
            'roles' => ['ROLE_USER'],
            'balance' => '0.00',
        ];

        return [
            'token' => $email,
            'roles' => ['ROLE_USER'],
        ];
    }

    public function getCurrentUser(string $token): array
    {
        $email = $token;

        if ($email === 'unavailable@example.com') {
            throw new BillingUnavailableException();
        }

        $user = $this->users[$email] ?? null;

        return [
            'email' => $email,
            'roles' => $user['roles'],
            'balance' => $user['balance'],
        ];
    }
}
