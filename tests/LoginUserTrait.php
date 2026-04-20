<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait LoginUserTrait
{
    private function login(KernelBrowser $client, string $email, string $password): void
    {
        $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Войти', [
            'email' => $email,
            'password' => $password,
        ]);
        $this->assertResponseRedirects('/courses', 302);

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    private function loginAsUser(KernelBrowser $client): void
    {
        $this->login($client, 'user01@mail.ru', 'password');
    }

    private function loginAsAdmin(KernelBrowser $client): void
    {
        $this->login($client, 'user02@mail.ru', 'password');
    }
}
