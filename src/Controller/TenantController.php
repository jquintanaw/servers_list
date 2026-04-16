<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tenants')]
class TenantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_tenants')]
    public function index(): Response
    {
        $tenants = $this->entityManager->getRepository(Tenant::class)->findAll();

        return $this->render('tenant/index.html.twig', [
            'tenants' => $tenants,
        ]);
    }

    #[Route('/new', name: 'app_tenants_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $tenant = new Tenant();

        if ($request->isMethod('POST')) {
            $tenant->setName($request->request->get('name', ''));
            $tenant->setAddress($request->request->get('address', ''));
            
            $contractTypeId = $request->request->get('contractType');
            $contractType = $this->entityManager->getRepository(\App\Entity\ContractType::class)->find($contractTypeId);
            if ($contractType) {
                $tenant->setContractType($contractType);
            }

            $this->entityManager->persist($tenant);
            $this->entityManager->flush();

            $this->addFlash('success', 'Cliente creado correctamente.');

            return $this->redirectToRoute('app_tenants');
        }

        $contractTypes = $this->entityManager->getRepository(\App\Entity\ContractType::class)->findAll();

        return $this->render('tenant/new.html.twig', [
            'tenant' => $tenant,
            'contractTypes' => $contractTypes,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tenants_edit', methods: ['GET', 'POST'])]
    public function edit(Tenant $tenant, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $tenant->setName($request->request->get('name', ''));
            $tenant->setAddress($request->request->get('address', ''));

            $this->entityManager->flush();

            $this->addFlash('success', 'Cliente actualizado correctamente.');

            return $this->redirectToRoute('app_tenants');
        }

        $contractTypes = $this->entityManager->getRepository(\App\Entity\ContractType::class)->findAll();

        return $this->render('tenant/edit.html.twig', [
            'tenant' => $tenant,
            'contractTypes' => $contractTypes,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_tenants_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Tenant $tenant, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $tenant->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_tenants');
        }

        if ($tenant->getServers()->count() > 0 || $tenant->getUsers()->count() > 0) {
            $this->addFlash('error', 'No se puede eliminar el cliente porque tiene servidores o usuarios asociados.');
            return $this->redirectToRoute('app_tenants');
        }

        $this->entityManager->remove($tenant);
        $this->entityManager->flush();

        $this->addFlash('success', 'Cliente eliminado correctamente.');

        return $this->redirectToRoute('app_tenants');
    }
}