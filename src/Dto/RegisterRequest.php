<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank(message: 'Введите email')]
    #[Assert\Email(message: 'Некорректный email')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Введите пароль')]
    #[Assert\Length(
        min: 6,
        minMessage: 'Пароль должен быть не менее {{ limit }} символов'
    )]
    public ?string $password = null;
}
