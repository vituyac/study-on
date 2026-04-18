<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons');
        $this->assertResponseIsSuccessful();

        $this->assertCount(17, $crawler->selectLink('Перейти к курсу'));
    }

    public function testShow(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Открыть')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains(
            'body',
            'На этом уроке студент познакомится со способами установки PHP, '
            . 'проверкой версии в консоли и запуском первого PHP-скрипта. '
            . 'Также рассматриваются теги PHP и базовая структура файла.'
        );
    }

    public function testShowNotFound(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/1000');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditNotFound(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/1000/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateLesson(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $courseLink = $link->getUri();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $lessonsCount = $crawler->selectLink('Открыть')->count();

        $link = $crawler->selectLink('Добавить урок')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Создать', [
            'lesson[title]' => 'Новый урок',
            'lesson[content]' => 'Описание нового урока',
            'lesson[ordering]' => 5,
        ]);

        $this->assertResponseRedirects($courseLink, 303);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->assertCount($lessonsCount + 1, $crawler->selectLink('Открыть'));
        $this->assertSelectorTextContains('body', 'Новый урок');
    }

    #[DataProvider('invalidLessonDataProvider')]
    public function testCreateValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Добавить урок')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Создать', $formData);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', $expectedError);
    }

    public function testEdit(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Открыть')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->clickLink('Редактировать');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Сохранить', [
            'lesson[title]' => 'Изменённый урок',
            'lesson[content]' => 'Описание изменённого урока',
            'lesson[ordering]' => 10,
        ]);

        $this->assertResponseRedirects($link->getUri(), 303);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', 'Изменённый урок');
        $this->assertSelectorTextContains('body', 'Описание изменённого урока');
    }

    #[DataProvider('invalidLessonDataProvider')]
    public function testEditValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Открыть')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->clickLink('Редактировать');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Сохранить', $formData);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', $expectedError);
    }

    public function testDelete(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $courseLink = $link->getUri();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $lessonsCount = $crawler->selectLink('Открыть')->count();

        $link = $crawler->selectLink('Открыть')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Удалить');

        $this->assertResponseRedirects($courseLink, 303);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->assertCount($lessonsCount - 1, $crawler->selectLink('Открыть'));
    }

    public static function invalidLessonDataProvider(): iterable
    {
        yield 'empty title' => [[
            'lesson[title]' => '',
            'lesson[content]' => 'Описание урока',
            'lesson[ordering]' => 1,
        ], 'Введите название урока'];

        yield 'empty content' => [[
            'lesson[title]' => 'Новый урок',
            'lesson[content]' => '',
            'lesson[ordering]' => 1,
        ], 'Введите описание урока'];

        yield 'long title' => [[
            'lesson[title]' => str_repeat('a', 256),
            'lesson[content]' => 'Описание урока',
            'lesson[ordering]' => 1,
        ], 'Название урока должно быть не длиннее 255 символов'];

        yield 'large ordering' => [[
            'lesson[title]' => 'Новый урок',
            'lesson[content]' => 'Описание урока',
            'lesson[ordering]' => 10001,
        ], 'Порядок должен быть не больше 10000'];

        yield 'negative ordering' => [[
            'lesson[title]' => 'Новый урок',
            'lesson[content]' => 'Описание урока',
            'lesson[ordering]' => -1,
        ], 'Порядок должен быть положительным'];

        yield 'string ordering' => [[
            'lesson[title]' => 'Новый урок',
            'lesson[content]' => 'Описание урока',
            'lesson[ordering]' => 'string',
        ], 'Порядок должен быть числом'];
    }
}
