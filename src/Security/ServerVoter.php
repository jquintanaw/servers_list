<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Server;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ServerVoter extends Voter
{
    public const VIEW_PASSWORD = 'VIEW_PASSWORD';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW_PASSWORD, self::EDIT, self::DELETE], true)
            && $subject instanceof Server;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW_PASSWORD => $this->canViewPassword($user),
            self::EDIT => $this->canEdit($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canViewPassword(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    private function canEdit(User $user): bool
    {
        return in_array('ROLE_SERVICE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    private function canDelete(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
