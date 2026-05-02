<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class BillingRefreshTokenCookieSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'BILLING_REFRESH_TOKEN';

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->request->getBoolean('_remember_me')) {
            return;
        }

        $user = $event->getUser();

        $refreshToken = $user->getRefreshToken();

        if ($refreshToken === null) {
            return;
        }

        $event->getResponse()->headers->setCookie(
            Cookie::create(self::COOKIE_NAME)
                ->withValue($refreshToken)
                ->withExpires(time() + 60 * 60 * 24 * 7)
                ->withPath('/')
                ->withSecure($event->getRequest()->isSecure())
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
    }
}
