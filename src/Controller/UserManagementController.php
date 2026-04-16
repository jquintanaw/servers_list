<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/usuarios')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'app_users')]
    public function index(): Response
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        return $this->render('user_management/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $tenants = $this->entityManager->getRepository(Tenant::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $contentType = $request->headers->get('Content-Type', '');
            
            if (str_contains($contentType, 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $email = $data['email'] ?? '';
                $password = $data['password'] ?? '';
                $status = $data['status'] ?? 'enabled';
                $fullName = $data['fullName'] ?? null;
                $address = $data['address'] ?? null;
                $city = $data['city'] ?? null;
                $country = $data['country'] ?? null;
                $socialNetworks = $data['socialNetworks'] ?? null;
                $tenantId = $data['tenant'] ?? null;
            } else {
                $email = $request->request->get('email', '');
                $password = $request->request->get('password', '');
                $status = $request->request->get('status', 'enabled');
                $fullName = $request->request->get('fullName');
                $address = $request->request->get('address');
                $city = $request->request->get('city');
                $country = $request->request->get('country');
                $socialNetworks = $request->request->get('socialNetworks');
                $tenantId = $request->request->get('tenant');
            }

            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->json(['error' => 'El email ya está en uso.'], 400);
                }
                $this->addFlash('error', 'El email ya está en uso.');
                return $this->render('user_management/new.html.twig', [
                    'user' => $user,
                    'tenants' => $tenants,
                ]);
            }

            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setStatus($status);
            $user->setRoles(['ROLE_USER']);

            if ($tenantId) {
                $tenant = $this->entityManager->getRepository(Tenant::class)->find((int)$tenantId);
                $user->setTenant($tenant);
            }

            $user->setFullName($fullName);
            $user->setAddress($address);
            $user->setCity($city);
            $user->setCountry($country);
            $user->setSocialNetworks($socialNetworks);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json(['success' => true, 'userId' => $user->getId()]);
            }

            $this->addFlash('success', 'Usuario creado correctamente.');

            return $this->redirectToRoute('app_users');
        }

        return $this->render('user_management/new.html.twig', [
            'user' => $user,
            'tenants' => $tenants,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_users_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $isJson = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
            
            if ($isJson) {
                $data = json_decode($request->getContent(), true);
                $email = $data['email'] ?? '';
                $status = $data['status'] ?? 'enabled';
                $password = $data['password'] ?? '';
                $roles = $data['roles'] ?? [];
                $fullName = $data['fullName'] ?? null;
                $address = $data['address'] ?? null;
                $city = $data['city'] ?? null;
                $country = $data['country'] ?? null;
                $socialNetworks = $data['socialNetworks'] ?? null;
                $tenantId = $data['tenant'] ?? null;
            } else {
                $email = $request->request->get('email', '');
                $status = $request->request->get('status', 'enabled');
                $password = $request->request->get('password', '');
                $roles = $request->request->all('roles') ?: [];
                $fullName = $request->request->get('fullName');
                $address = $request->request->get('address');
                $city = $request->request->get('city');
                $country = $request->request->get('country');
                $socialNetworks = $request->request->get('socialNetworks');
                $tenantId = $request->request->get('tenant');
            }

            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->json(['error' => 'El email ya está en uso.'], 400);
                }
                $this->addFlash('error', 'El email ya está en uso.');
                return $this->render('user_management/edit.html.twig', [
                    'user' => $user,
                    'tenants' => $this->entityManager->getRepository(Tenant::class)->findBy([], ['name' => 'ASC']),
                ]);
            }

            if (!in_array('ROLE_USER', $roles, true)) {
                $roles[] = 'ROLE_USER';
                $this->addFlash('warning', 'Se agregó el rol base ROLE_USER.');
            }

            $currentUser = $this->getUser();
            $isSelfAdmin = $currentUser instanceof User && $currentUser->getId() === $user->getId();
            $removingOwnAdmin = $isSelfAdmin 
                && in_array('ROLE_ADMIN', $user->getRoles(), true) 
                && !in_array('ROLE_ADMIN', $roles, true);

            if ($removingOwnAdmin) {
                $this->addFlash('error', 'No puede remover su propio rol de administrador.');
                return $this->render('user_management/edit.html.twig', [
                    'user' => $user,
                    'tenants' => $this->entityManager->getRepository(Tenant::class)->findBy([], ['name' => 'ASC']),
                ]);
            }

            $user->setEmail($email);
            $user->setStatus($status);
            $user->setRoles($roles);
            
            if ($tenantId) {
                $tenant = $this->entityManager->getRepository(Tenant::class)->find((int)$tenantId);
                $user->setTenant($tenant);
            } else {
                $user->setTenant(null);
            }
            
            $user->setFullName($fullName);
            $user->setAddress($address);
            $user->setCity($city);
            $user->setCountry($country);
            $user->setSocialNetworks($socialNetworks);

            if (!empty($password)) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            }

            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                error_log('Flush error: ' . $e->getMessage());
                throw $e;
            }

            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json(['success' => true, 'reload' => true]);
            }

            $this->addFlash('success', 'Usuario actualizado correctamente.');
 
            return $this->redirectToRoute('app_users');
        }

        return $this->render('user_management/edit.html.twig', [
            'user' => $user,
            'tenants' => $this->entityManager->getRepository(Tenant::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_users_delete', methods: ['POST'])]
    public function delete(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_users');
        }

        $isLastAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true)
            && $this->isLastAdmin();

        if ($isLastAdmin) {
            $this->addFlash('error', 'No se puede eliminar el último usuario administrator.');
            return $this->redirectToRoute('app_users');
        }

        $email = $user->getEmail();

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Usuario "' . $email . '" eliminado correctamente.');

        return $this->redirectToRoute('app_users');
    }

    private function isLastAdmin(): bool
    {
        $admins = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', '"ROLE_ADMIN"')
            ->getQuery()
            ->getResult();

        return count($admins) <= 1;
    }
}