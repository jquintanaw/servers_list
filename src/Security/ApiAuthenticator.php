<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization') ?? '', 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get('Authorization') ?? '', 7);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['apiToken' => $token]);

        if (!$user || !$user->isEnabled()) {
            throw new AuthenticationException('Invalid API token');
        }

        $roles = ['ROLE_API'];
        
        if (in_array('ROLE_API_GET', $user->getRoles(), true)) {
            $roles[] = 'ROLE_API_GET';
        }
        if (in_array('ROLE_API_POST', $user->getRoles(), true)) {
            $roles[] = 'ROLE_API_POST';
        }
        if (in_array('ROLE_API_PUT', $user->getRoles(), true)) {
            $roles[] = 'ROLE_API_PUT';
        }
        if (in_array('ROLE_API_DELETE', $user->getRoles(), true)) {
            $roles[] = 'ROLE_API_DELETE';
        }

        $user->setRoles(array_merge($user->getRoles(), $roles));

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn () => $user));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Invalid or missing API token'], Response::HTTP_UNAUTHORIZED);
    }
}