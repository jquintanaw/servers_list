<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/mi-perfil')]
class ProfileController extends AbstractController
{
    private ValidatorInterface $validator;
    private TokenStorageInterface $tokenStorage;
    private EntityManagerInterface $em;

    public function __construct(
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em
    ) {
        $this->validator = $validator;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    private function getCurrentUser(): User
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token || null === $token->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $user = $token->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }

    #[Route('', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getCurrentUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Perfil actualizado correctamente');

            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'socialNetworks' => $user->getSocialNetworks() ?? [],
        ]);
    }

    #[Route('/datos', name: 'api_profile_get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getCurrentUser();

        return $this->json([
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'address' => $user->getAddress(),
            'city' => $user->getCity(),
            'country' => $user->getCountry(),
            'socialNetworks' => $user->getSocialNetworks(),
            'avatarUrl' => $user->getAvatarPath(),
        ]);
    }

    #[Route('/datos', name: 'api_profile_put', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $constraints = new Assert\Collection([
            'fullName' => [
                new Assert\Length(['max' => 100]),
            ],
            'address' => [
                new Assert\Length(['max' => 255]),
            ],
            'city' => [
                new Assert\Length(['max' => 100]),
            ],
            'country' => [
                new Assert\Length(['max' => 100]),
            ],
            'socialNetworks' => [
                new Assert\Type(['type' => 'array']),
            ],
        ]);

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 422);
        }

        if (isset($data['fullName'])) {
            $user->setFullName($data['fullName']);
        }
        if (isset($data['address'])) {
            $user->setAddress($data['address']);
        }
        if (isset($data['city'])) {
            $user->setCity($data['city']);
        }
        if (isset($data['country'])) {
            $user->setCountry($data['country']);
        }
        if (isset($data['socialNetworks'])) {
            $user->setSocialNetworks($data['socialNetworks']);
        }

        $this->em->flush();

        return $this->json([
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'address' => $user->getAddress(),
            'city' => $user->getCity(),
            'country' => $user->getCountry(),
            'socialNetworks' => $user->getSocialNetworks(),
            'avatarUrl' => $user->getAvatarPath(),
        ]);
    }

    #[Route('/avatar', name: 'api_profile_avatar_post', methods: ['POST'])]
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            $user = $this->getCurrentUser();

            $file = $request->files->get('avatar');

            if (null === $file) {
                return $this->json(['error' => 'No file uploaded'], 400);
            }

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                return $this->json(['error' => 'Invalid file type. Allowed: jpg, png, gif, webp'], 422);
            }

            if ($file->getSize() > $maxSize) {
                return $this->json(['error' => 'File too large. Maximum size: 2MB'], 422);
            }

            $user->removeAvatar();

            $extension = $file->getClientOriginalExtension();
            $filename = sprintf('%s.%s', bin2hex(random_bytes(16)), $extension);
            
            $uploadDir = '/tmp/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $file->move($uploadDir, $filename);
            
            $webPath = '/var/www/public/uploads/avatars';
            if (!is_dir($webPath)) {
                mkdir($webPath, 0777, true);
            }
            
            if (copy($uploadDir . '/' . $filename, $webPath . '/' . $filename)) {
                unlink($uploadDir . '/' . $filename);
            }
            $user->setAvatarFilename($filename);

            $this->em->persist($user);
            $this->em->flush();

            $connection = $this->em->getConnection();
            $connection->executeStatement(
                'UPDATE "user" SET avatar_filename = :filename WHERE id = :id',
                ['filename' => $filename, 'id' => $user->getId()]
            );

            return $this->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'avatarFilename' => $filename,
                'avatarUrl' => '/uploads/avatars/' . $filename,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/avatar', name: 'api_profile_avatar_delete', methods: ['DELETE'])]
    public function deleteAvatar(): JsonResponse
    {
        $user = $this->getCurrentUser();

        if (null === $user->getAvatarFilename()) {
            return $this->json(['error' => 'No avatar to delete'], 400);
        }

        $user->removeAvatar();

        $this->em->flush();

        return $this->json(['success' => true]);
    }
}