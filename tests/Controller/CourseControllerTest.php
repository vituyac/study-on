<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertCount(4, $crawler->selectLink('Перейти к курсу'));
    }

    public function testShow(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertCount(4, $crawler->selectLink('Открыть'));
    }

    public function testCourseShowNotFound(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/1000');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCourseEditNotFound(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/1000/edit');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateCourse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/courses');
        $client->clickLink('Добавить курс');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Создать', [
            'course[code]' => 'new-course',
            'course[title]' => 'Новый курс',
            'course[description]' => 'Описание нового курса',
        ]);

        $this->assertResponseRedirects('/courses', 303);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->assertCount(5, $crawler->selectLink('Перейти к курсу'));
        $this->assertSelectorTextContains('body', 'Новый курс');
        $this->assertSelectorTextContains('body', 'Описание нового курса');
    }

    #[DataProvider('invalidCourseDataProvider')]
    public function testCreateCourseValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();

        $client->request('GET', '/courses');
        $client->clickLink('Добавить курс');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Создать', $formData);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', $expectedError);
    }

    public function testEditCourse(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->clickLink('Редактировать');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Сохранить', [
            'course[code]' => 'edit-course',
            'course[title]' => 'Изменённый курс',
            'course[description]' => 'Описание изменённого курса',
        ]);

        $this->assertResponseRedirects($link->getUri(), 303);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', 'Изменённый курс');
        $this->assertSelectorTextContains('body', 'Описание изменённого курса');
    }

    #[DataProvider('invalidCourseDataProvider')]
    public function testEditCourseValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->clickLink('Редактировать');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Сохранить', $formData);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', $expectedError);
    }

    public function testDeleteCourse(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Перейти к курсу')->first()->link();
        $client->click($link);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Удалить');

        $this->assertResponseRedirects('/courses', 303);
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->assertCount(3, $crawler->selectLink('Перейти к курсу'));
    }

    public static function invalidCourseDataProvider(): iterable
    {
        yield 'unique code' => [[
            'course[code]' => 'web-security',
            'course[title]' => 'Новый курс',
            'course[description]' => 'Описание нового курса',
        ], 'Курс с таким кодом уже существует'];

        yield 'empty code' => [[
            'course[code]' => '',
            'course[title]' => 'Новый курс',
            'course[description]' => 'Описание нового курса',
        ], 'Введите код курса'];

        yield 'empty title' => [[
            'course[code]' => 'new-course',
            'course[title]' => '',
            'course[description]' => 'Описание нового курса',
        ], 'Введите название курса'];

        yield 'long code' => [[
            'course[code]' => str_repeat('a', 256),
            'course[title]' => 'Новый курс',
            'course[description]' => 'Описание нового курса',
        ], 'Код курса должен быть не длиннее 255 символов'];

        yield 'long title' => [[
            'course[code]' => 'new-course',
            'course[title]' => str_repeat('a', 256),
            'course[description]' => 'Описание нового курса',
        ], 'Название курса должно быть не длиннее 255 символов'];

        yield 'long description' => [[
            'course[code]' => 'new-course',
            'course[title]' => 'Новый курс',
            'course[description]' => str_repeat('a', 1001),
        ], 'Описание не должно превышать 1000 символов'];
    }
}
