<?php

namespace App\Tests\Controller;

use App\Tests\LoginUserTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    use LoginUserTrait;

    #[DataProvider('userDataProvider')]
    public function testShow(string $email, string $role, string $balance): void
    {
        $client = static::createClient();
        $client->disableReboot();

        if ($role === 'Администратор') {
            $this->loginAsAdmin($client);
        } else {
            $this->loginAsUser($client);
        }

        $crawler = $client->getCrawler();
        $link = $crawler->selectLink('Профиль')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Профиль');
        $this->assertSelectorTextContains('body', $email);
        $this->assertSelectorTextContains('body', $role);
        $this->assertSelectorTextContains('body', $balance);
    }

    public static function userDataProvider(): iterable
    {
        yield 'base user' => [
            'email' => 'user01@mail.ru',
            'role' => 'Пользователь',
            'balance' => '1000.00',
        ];

        yield 'admin user' => [
            'email' => 'user02@mail.ru',
            'role' => 'Администратор',
            'balance' => '100.00',
        ];
    }
}
