<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ServerTag;
use App\Entity\Server;
use App\Repository\ServerTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tags')]
#[IsGranted('ROLE_ADMIN')]
class TagController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_admin_tags')]
    public function index(): Response
    {
        $tags = $this->entityManager->getRepository(ServerTag::class)->findBy([], ['name' => 'ASC']);

        return $this->render('tag/index.html.twig', [
            'tags' => $tags,
        ]);
    }

    #[Route('/new', name: 'app_admin_tags_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tag = new ServerTag();

        if ($request->isMethod('POST')) {
            try {
                $tag->setName($request->request->get('name', ''));
                $tag->setColor($request->request->get('color', 'primary'));

                $this->entityManager->persist($tag);
                $this->entityManager->flush();

                $this->addFlash('success', 'Tag "' . $tag->getName() . '" creado correctamente.');

                return $this->redirectToRoute('app_admin_tags');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo crear el tag. Intente nuevamente.');
            }
        }

        return $this->render('tag/new.html.twig', ['tag' => $tag]);
    }

    #[Route('/{id}/edit', name: 'app_admin_tags_edit', methods: ['GET', 'POST'])]
    public function edit(ServerTag $tag, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $tag->setName($request->request->get('name', ''));
                $tag->setColor($request->request->get('color', 'primary'));

                $this->entityManager->flush();

                $this->addFlash('success', 'Tag actualizado correctamente.');

                return $this->redirectToRoute('app_admin_tags');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo actualizar el tag. Intente nuevamente.');
            }
        }

        return $this->render('tag/edit.html.twig', ['tag' => $tag]);
    }

    #[Route('/{id}/delete', name: 'app_admin_tags_delete', methods: ['POST'])]
    public function delete(ServerTag $tag, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_tag_' . $tag->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_admin_tags');
        }

        $name = $tag->getName();

        try {
            $this->entityManager->remove($tag);
            $this->entityManager->flush();

            $this->addFlash('success', 'Tag "' . $name . '" eliminado correctamente.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'No se pudo eliminar el tag. Puede tener servidores asociados.');
        }

        return $this->redirectToRoute('app_admin_tags');
    }
}