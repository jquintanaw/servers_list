<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

final class LoginEmailValidationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['onCheckPassport', 512]];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        if (!$event->getAuthenticator() instanceof FormLoginAuthenticator) {
            return;
        }

        $passport = $event->getPassport();
        if (!$passport->hasBadge(UserBadge::class)) {
            return;
        }

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        $identifier = $badge->getUserIdentifier();

        if ('' === $identifier || !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // Same key as BadCredentialsException so the UI stays generic (no email leak).
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }
    }
}
