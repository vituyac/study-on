<?php

namespace App\Dto;

use App\Entity\Course;

class CourseView
{
    public function __construct(
        public Course $course,
        public ?string $type,
        public ?string $price,
        public ?bool $purchased,
        public ?\DateTimeImmutable $expiresAt,
        public bool $billingAvailable = true,
    ) {
    }

    public function isFree(): bool
    {
        return $this->type === 'FREE';
    }

    public function isRent(): bool
    {
        return $this->type === 'RENT';
    }

    public function isFull(): bool
    {
        return $this->type === 'FULL';
    }
}
