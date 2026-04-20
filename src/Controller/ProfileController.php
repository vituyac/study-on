<?php

namespace App\Controller;

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
    public function show(BillingClient $billingClient): Response
    {
        $user = $billingClient->getCurrentUser($this->getUser()->getApiToken());

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }
}
