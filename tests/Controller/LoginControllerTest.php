<?php

namespace App\Tests\Controller;

use App\Tests\LoginUserTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    use LoginUserTrait;

    public function testShow(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Войти')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();
    }

    public function testRedirectToCoursesWhenUser(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $crawler = $client->request('GET', '/login');
        $this->assertResponseRedirects('/courses', 302);

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Войти')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Войти', [
            'email' => 'user01@mail.ru',
            'password' => 'password',
        ]);
        $this->assertResponseRedirects('/courses', 302);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testUnsuccessfulLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Войти')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Войти', [
            'email' => 'user01@mail.ru',
            'password' => 'wrongpassword',
        ]);
        $this->assertResponseRedirects('/login', 302);

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', 'Неверный email или пароль');
    }

    #[DataProvider('invalidUserDataProvider')]
    public function testLoginValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Войти')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Войти', $formData);
        $this->assertResponseRedirects('/login', 302);

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', $expectedError);
    }

    public static function invalidUserDataProvider(): iterable
    {
        yield 'base user' => [[
            'email' => '',
            'password' => 'password',
        ], 'Введите email'];

        yield 'admin user' => [[
            'email' => 'user01@mail.ru',
            'password' => '',
        ], 'Введите пароль'];

        yield 'invalid email' => [[
            'email' => 'invalid-email',
            'password' => 'password',
        ], 'Некорректный email'];

        yield 'unavailable service' => [[
            'email' => 'unavailable@example.com',
            'password' => 'password',
        ], 'Сервис временно недоступен'];
    }
}
