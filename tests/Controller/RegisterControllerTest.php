<?php

namespace App\Tests\Controller;

use App\Tests\LoginUserTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    use LoginUserTrait;

    public function testShow(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Регистрация')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();
    }

    public function testRedirectToProfileWhenUser(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $crawler = $client->request('GET', '/register');
        $this->assertResponseRedirects('/profile', 302);

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testSuccessfulRegister(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Регистрация')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Зарегистрироваться', [
            'register[email]' => 'user03@mail.ru',
            'register[password][first]' => 'password',
            'register[password][second]' => 'password',
        ]);
        $this->assertResponseRedirects('/courses', 302);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('invalidRegisterDataProvider')]
    public function testRegisterValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Регистрация')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Зарегистрироваться', $formData);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', $expectedError);
    }

    public static function invalidRegisterDataProvider(): iterable
    {
        yield 'empty email' => [[
            'register[email]' => '',
            'register[password][first]' => 'password',
            'register[password][second]' => 'password',
        ], 'Введите email'];

        yield 'invalid email' => [[
            'register[email]' => 'invalid-email',
            'register[password][first]' => 'password',
            'register[password][second]' => 'password',
        ], 'Некорректный email'];

        yield 'empty password' => [[
            'register[email]' => 'user03@mail.ru',
            'register[password][first]' => '',
            'register[password][second]' => '',
        ], 'Введите пароль'];

        yield 'short password' => [[
            'register[email]' => 'user03@mail.ru',
            'register[password][first]' => '123',
            'register[password][second]' => '123',
        ], 'Пароль должен быть не менее 6 символов'];

        yield 'passwords do not match' => [[
            'register[email]' => 'user03@mail.ru',
            'register[password][first]' => 'password1',
            'register[password][second]' => 'password2',
        ], 'Пароли не совпадают'];

        yield 'unique email' => [[
            'register[email]' => 'user01@mail.ru',
            'register[password][first]' => 'password',
            'register[password][second]' => 'password',
        ], 'Пользователь с таким email уже существует'];

        yield 'unavailable service' => [[
            'register[email]' => 'unavailable@example.com',
            'register[password][first]' => 'password',
            'register[password][second]' => 'password',
        ], 'Сервис временно недоступен. Попробуйте зарегистрироваться позднее'];
    }
}
