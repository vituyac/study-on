<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    #[Route(name: 'app_profile_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(BillingClient $billingClient, CourseRepository $courseRepository): Response
    {
        $user = $this->getUser();
        $billingUser = $billingClient->getCurrentUser($this->getUser()->getApiToken());

        try {
            $transactions = $billingClient->transactions($user->getApiToken(), []);
            $coursesByCode = [];
            foreach ($courseRepository->findAll() as $course) {
                $coursesByCode[$course->getCode()] = $course;
            }
            $billingAvailable = true;
        } catch (\Throwable) {
            $transactions = [];
            $coursesByCode = [];
            $billingAvailable = false;
        }

        return $this->render('profile/show.html.twig', [
            'user' => $billingUser,
            'transactions' => $transactions,
            'coursesByCode' => $coursesByCode,
            'billingAvailable' => $billingAvailable,
        ]);
    }
}
