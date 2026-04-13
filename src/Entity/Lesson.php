<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Введите название урока')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Название урока должно быть не длиннее {{ limit }} символов'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Введите описание урока')]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    #[Assert\LessThanOrEqual(
        value: 10000,
        message: 'Порядок должен быть не больше {{ compared_value }}'
    )]
    #[Assert\Positive(message: 'Порядок должен быть положительным')]
    private ?int $ordering = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getOrdering(): ?int
    {
        return $this->ordering;
    }

    public function setOrdering(?int $ordering): static
    {
        $this->ordering = $ordering;

        return $this;
    }
}
