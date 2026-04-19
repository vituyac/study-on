<?php

namespace App\Security;

use App\Service\BillingClient;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly BillingClient $billingClient,
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
        try {
            $data = $this->billingClient->getCurrentUser($identifier);
        } catch (\Throwable) {
            throw new UserNotFoundException('Token expired or user not found.');
        }

        return (new User())
            ->setEmail($data['email'])
            ->setRoles($data['roles'] ?? ['ROLE_USER'])
            ->setBalance((string) $data['balance'])
            ->setApiToken($identifier);
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

        if ($token === null) {
            throw new UserNotFoundException();
        }

        return $this->loadUserByIdentifier($token);
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
