<?php

namespace App\Security;

use App\Service\BillingClient;
use App\Service\JwtPayloadDecoder;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UserProvider implements UserProviderInterface
{
    private const REFRESH_TOKEN_COOKIE = 'BILLING_REFRESH_TOKEN';

    public function __construct(
        private readonly BillingClient $billingClient,
        private readonly JwtPayloadDecoder $jwtPayloadDecoder,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $refreshToken = $request?->cookies->get(self::REFRESH_TOKEN_COOKIE);

        if ($refreshToken === null) {
            throw new UserNotFoundException();
        }

        try {
            $tokens = $this->billingClient->refreshToken($refreshToken);
            $data = $this->billingClient->getCurrentUser($tokens['token']);
        } catch (\Throwable) {
            throw new UserNotFoundException();
        }

        return (new User())
            ->setEmail($data['email'])
            ->setRoles($data['roles'] ?? ['ROLE_USER'])
            ->setBalance((string) $data['balance'])
            ->setApiToken($tokens['token'])
            ->setRefreshToken($tokens['refreshToken']);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }

        $token = $user->getApiToken();
        $refreshToken = $user->getRefreshToken();

        if ($token === null && $refreshToken === null) {
            throw new UserNotFoundException();
        }

        $payload = $this->jwtPayloadDecoder->decode($token);

        $exp = $payload['exp'] ?? null;

        if (!is_numeric($exp)) {
            throw new UserNotFoundException();
        }

        if ($exp <= time() + 10) {
            try {
                $tokens = $this->billingClient->refreshToken($refreshToken);
            } catch (\Throwable) {
                throw new UserNotFoundException();
            }

            $user->setApiToken($tokens['token']);
            $user->setRefreshToken($tokens['refreshToken']);
        }

        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
