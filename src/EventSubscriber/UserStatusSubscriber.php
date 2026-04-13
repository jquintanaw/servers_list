<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\CheckPassportEvent;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserStatusSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'security.check_passport' => ['onCheckPassport', -10],
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() === User::STATUS_DISABLED) {
            throw new CustomUserMessageAccountStatusException('Usuario deshabilitado. Contacte al administrador.');
        }

        if ($user->getStatus() === User::STATUS_MAINTENANCE) {
            throw new CustomUserMessageAccountStatusException('Sistema en mantenimiento. Intente más tarde.');
        }
    }
}
