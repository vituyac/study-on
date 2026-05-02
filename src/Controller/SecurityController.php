<?php

namespace App\Controller;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Exception\BillingValidationException;
use App\Form\RegisterType;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        BillingClient $billingClient,
        UserAuthenticatorInterface $authenticator,
        BillingAuthenticator $billingAuthenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile_show');
        }

        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $registerData = $billingClient->register($data['email'], $data['password']);
                $token = $registerData['token'];
                $refreshToken = $registerData['refreshToken'];
                $currentUser = $billingClient->getCurrentUser($token);
            } catch (BillingUnavailableException) {
                $form->addError(new FormError('Сервис временно недоступен. Попробуйте зарегистрироваться позднее'));
            } catch (BillingValidationException $e) {
                foreach ($e->getViolations() as $field => $message) {
                    if ($form->has($field)) {
                        $form->get($field)->addError(new FormError($message));
                    } else {
                        $form->addError(new FormError($message));
                    }
                }
            } catch (BillingException $e) {
                $form->addError(new FormError($e->getMessage()));
            }

            if (isset($currentUser, $token, $refreshToken)) {
                $user = (new User())
                    ->setEmail($currentUser['email'])
                    ->setRoles($data['roles'] ?? ['ROLE_USER'])
                    ->setBalance((string) $currentUser['balance'])
                    ->setApiToken($token)
                    ->setRefreshToken($refreshToken);

                return $authenticator->authenticateUser(
                    $user,
                    $billingAuthenticator,
                    $request,
                );
            }
        }

        return $this->render('security/register.html.twig', [
            'registerForm' => $form,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }
}
