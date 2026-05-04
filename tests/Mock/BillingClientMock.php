<?php

namespace App\Tests\Mock;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Exception\BillingValidationException;
use App\Service\BillingClient;

final class BillingClientMock extends BillingClient
{
    private const TOKEN_USER01 = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VybmFtZSI6InVzZXIwMUBtYWlsLnJ1IiwiZXhwIjo5OTk5OTk5OTk5fQ.fake_sig';
    private const TOKEN_USER02 = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VybmFtZSI6InVzZXIwMkBtYWlsLnJ1IiwiZXhwIjo5OTk5OTk5OTk5fQ.fake_sig';
    private const REFRESH_USER01 = 'refresh_token_user01';
    private const REFRESH_USER02 = 'refresh_token_user02';

    private array $users = [
        'user01@mail.ru' => [
            'password' => 'password',
            'roles' => ['ROLE_USER'],
            'balance' => '1000.00',
            'token' => self::TOKEN_USER01,
            'refreshToken' => self::REFRESH_USER01,
        ],
        'user02@mail.ru' => [
            'password' => 'password',
            'roles' => ['ROLE_SUPER_ADMIN'],
            'balance' => '100.00',
            'token' => self::TOKEN_USER02,
            'refreshToken' => self::REFRESH_USER02,
        ],
    ];

    private array $tokenMap = [
        self::TOKEN_USER01 => 'user01@mail.ru',
        self::TOKEN_USER02 => 'user02@mail.ru',
    ];

    private array $refreshMap = [
        self::REFRESH_USER01 => 'user01@mail.ru',
        self::REFRESH_USER02 => 'user02@mail.ru',
    ];

    private array $courses = [
        [
            'code' => 'php-basics',
            'type' => 'RENT',
            'price' => '100.00',
        ],
        [
            'code' => 'symfony-start',
            'type' => 'FULL',
            'price' => '200.00',
        ],
        [
            'code' => 'doctrine-practice',
            'type' => 'FREE',
            'price' => '0.00',
        ],
        [
            'code' => 'web-security',
            'type' => 'FULL',
            'price' => '150.00',
        ],
    ];

    private array $transactions = [
        [
            'id' => 1,
            'type' => 'PAYMENT',
            'amount' => '100.00',
            'createdAt' => '2026-05-03T23:22:08+00:00',
            'expiresAt' => '2026-05-10T23:22:08+00:00',
            'courseCode' => 'php-basics',
        ],
        [
            'id' => 2,
            'type' => 'DEPOSIT',
            'amount' => '1600.00',
            'createdAt' => '2026-05-03T23:22:07+00:00',
            'expiresAt' => null,
            'courseCode' => null,
        ],
    ];

    public function __construct()
    {
    }

    public function auth(string $email, string $password): array
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

        return [
            'token' => $user['token'],
            'refreshToken' => $user['refreshToken'],
        ];
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

        $newToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VybmFtZSI6Im5ld191c2VyIiwiZXhwIjo5OTk5OTk5OTk5fQ.fake_sig_new';
        $newRefresh = 'refresh_token_new_' . $email;

        $this->users[$email] = [
            'password' => $password,
            'roles' => ['ROLE_USER'],
            'balance' => '0.00',
            'token' => $newToken,
            'refreshToken' => $newRefresh,
        ];
        $this->tokenMap[$newToken] = $email;
        $this->refreshMap[$newRefresh] = $email;

        return [
            'token' => $newToken,
            'refreshToken' => $newRefresh,
        ];
    }

    public function refreshToken(string $token): array
    {
        $email = $this->refreshMap[$token] ?? null;

        if ($email === null) {
            throw new BillingException('Invalid refresh token.', 401);
        }

        $user = $this->users[$email];

        return [
            'token' => $user['token'],
            'refreshToken' => $user['refreshToken'],
        ];
    }

    public function courses(): array
    {
        return $this->courses;
    }

    public function course(string $code): array
    {
        foreach ($this->courses as $course) {
            if ($course['code'] === $code) {
                return $course;
            }
        }

        throw new BillingException('Course not found.', 404);
    }

    public function transactions(string $token, array $filters): array
    {
        return $this->transactions;
    }

    public function pay(string $token, string $code): array
    {
        $email = $this->tokenMap[$token] ?? null;

        if ($email === null) {
            throw new BillingException('Unauthorized.', 401);
        }

        return [
            'success' => true,
            'courseCode' => $code,
        ];
    }

    public function getCurrentUser(string $token): array
    {
        $email = $this->tokenMap[$token] ?? null;

        if ($email === null || $email === 'unavailable@example.com') {
            throw new BillingUnavailableException();
        }

        $user = $this->users[$email];

        return [
            'email' => $email,
            'roles' => $user['roles'],
            'balance' => $user['balance'],
        ];
    }
}
