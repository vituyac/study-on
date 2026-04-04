<?php

namespace App\Form\DataTransformer;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseToIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private CourseRepository $courseRepository,
    ) {
    }

    public function transform($course): string
    {
        if (null === $course) {
            return '';
        }

        return $course->getId();
    }

    public function reverseTransform($courseId): ?Course
    {
        if (!$courseId) {
            return null;
        }

        $course = $this->courseRepository->find($courseId);

        if (null === $course) {
            throw new TransformationFailedException(sprintf(
                'Курса с id "%s" не существует!',
                $courseId
            ));
        }

        return $course;
    }
}