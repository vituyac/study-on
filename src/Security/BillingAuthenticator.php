<?php

namespace App\Security;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Exception\BillingValidationException;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private BillingClient $billingClient,
    ) {
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        try {
            $token = $this->billingClient->auth($email, $password);
            $data = $this->billingClient->getCurrentUser($token);
        } catch (BillingUnavailableException) {
            throw new CustomUserMessageAuthenticationException('Сервис временно недоступен');
        } catch (BillingValidationException $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        } catch (BillingException) {
            throw new CustomUserMessageAuthenticationException('Неверный email или пароль');
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        $loadUser = function () use ($data, $token): User {
            return (new User())
                ->setEmail($data['email'])
                ->setRoles($data['roles'] ?? ['ROLE_USER'])
                ->setBalance((string) $data['balance'])
                ->setApiToken($token);
        };

        return new SelfValidatingPassport(
            new UserBadge($email, $loadUser),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
