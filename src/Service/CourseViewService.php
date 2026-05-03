<?php

namespace App\Service;

use App\Dto\CourseView;
use App\Entity\Course;
use App\Security\User;
use App\Service\BillingClient;

class CourseViewService
{
    public function __construct(
        private BillingClient $billingClient,
    ) {
    }

    public function createList(array $courses, ?User $user): array
    {
        $billingCourses = [];
        $transactions = [];

        try {
            $billingCourses = array_column(
                $this->billingClient->courses(),
                null,
                'code'
            );

            if ($user) {
                $transactions = array_column(
                    $this->billingClient->transactions($user->getApiToken(), [
                        'skip_expired' => true,
                    ]),
                    null,
                    'courseCode'
                );
            }
        } catch (\Throwable) {
            return array_map(
                fn (Course $course) => new CourseView(
                    course: $course,
                    type: null,
                    price: null,
                    purchased: null,
                    expiresAt: null,
                    billingAvailable: false,
                ),
                $courses
            );
        }

        $result = [];

        foreach ($courses as $course) {
            $code = $course->getCode();

            $billingCourse = $billingCourses[$code] ?? null;

            $type = $billingCourse['type'] ?? null;
            $price = $billingCourse['price'] ?? null;

            $transaction = $transactions[$code] ?? null;

            $purchased = null;
            $expiresAt = null;

            if ($transaction && $type !== 'FREE') {
                $purchased = true;

                if ($type === 'RENT' && isset($transaction['expiresAt'])) {
                    $expiresAt = new \DateTimeImmutable($transaction['expiresAt']);
                }
            }

            $result[] = new CourseView(
                course: $course,
                type: $type,
                price: $price,
                purchased: $purchased,
                expiresAt: $expiresAt,
            );
        }

        return $result;
    }
}
