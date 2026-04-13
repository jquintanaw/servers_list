<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/services-admin')]
#[IsGranted('ROLE_ADMIN')]
class ServiceAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_services_admin')]
    public function index(): Response
    {
        $services = $this->entityManager->getRepository(Service::class)->findAll();

        return $this->render('service/index.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/new', name: 'app_services_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $service = new Service();

        if ($request->isMethod('POST')) {
            $service->setName($request->request->get('name', ''));
            $service->setDescription($request->request->get('description', ''));

            $this->entityManager->persist($service);
            $this->entityManager->flush();

            $this->addFlash('success', 'Servicio creado correctamente.');

            return $this->redirectToRoute('app_services_admin');
        }

        return $this->render('service/new.html.twig', ['service' => $service]);
    }

    #[Route('/{id}/edit', name: 'app_services_edit', methods: ['GET', 'POST'])]
    public function edit(Service $service, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $service->setName($request->request->get('name', ''));
            $service->setDescription($request->request->get('description', ''));

            $this->entityManager->flush();

            $this->addFlash('success', 'Servicio actualizado correctamente.');

            return $this->redirectToRoute('app_services_admin');
        }

        return $this->render('service/edit.html.twig', ['service' => $service]);
    }

    #[Route('/{id}/delete', name: 'app_services_delete', methods: ['POST'])]
    public function delete(Service $service, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_service' . $service->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_services_admin');
        }

        $this->entityManager->remove($service);
        $this->entityManager->flush();

        $this->addFlash('success', 'Servicio eliminado correctamente.');

        return $this->redirectToRoute('app_services_admin');
    }
}