<?php

namespace App\Security\Voter;

use App\Entity\Course;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CourseVoter extends Voter
{
    public const ACCESS = 'COURSE_ACCESS';

    public function __construct(
        private BillingClient $billingClient,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ACCESS && $subject instanceof Course;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $course = $subject;
        return $this->canAccessCourse($course, $token);
    }

    private function canAccessCourse(Course $course, TokenInterface $token): bool
    {
        try {
            $billingCourses = array_column(
                $this->billingClient->courses(),
                null,
                'code'
            );

            $code = $course->getCode();

            if (!isset($billingCourses[$code])) {
                return false;
            }

            $type = $billingCourses[$code]['type'];

            if ($type === 'FREE') {
                return true;
            }

            $user = $token->getUser();

            if (!$user instanceof User) {
                return false;
            }

            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
                return true;
            }

            $transactions = array_column(
                $this->billingClient->transactions($user->getApiToken(), [
                    'type' => 'PAYMENT',
                    'course_code' => $code,
                    'skip_expired' => true,
                ]),
                null,
                'courseCode'
            );

            return isset($transactions[$code]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
