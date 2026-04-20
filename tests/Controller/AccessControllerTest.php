<?php

namespace App\Tests\Controller;

use App\Tests\LoginUserTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessControllerTest extends WebTestCase
{
    use LoginUserTrait;

    public function testGuestIsRedirectedToLoginWhenOpeningLesson(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Открыть')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseRedirects('/login', 302);
    }

    public function testGuestRedirectedToLoginWhenCreateCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->request('GET', '/courses/new');
        $this->assertResponseRedirects('/login', 302);
    }

    public function testUserCannotCreateCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $client->request('GET', '/courses/new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);

        $client->request('GET', '/courses/new');
        $this->assertResponseIsSuccessful();
    }
}
