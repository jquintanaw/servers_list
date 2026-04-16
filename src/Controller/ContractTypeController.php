<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contract-types')]
#[IsGranted('ROLE_ADMIN')]
class ContractTypeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_contract_types')]
    public function index(): Response
    {
        $contractTypes = $this->entityManager->getRepository(ContractType::class)->findAll();

        return $this->render('contract_type/index.html.twig', [
            'contractTypes' => $contractTypes,
        ]);
    }

    #[Route('/new', name: 'app_contract_types_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $contractType = new ContractType();

        if ($request->isMethod('POST')) {
            $contractType->setName($request->request->get('name', ''));
            $contractType->setDescription($request->request->get('description'));
            $contractType->setPrice($request->request->get('price'));

            $this->entityManager->persist($contractType);
            $this->entityManager->flush();

            $this->addFlash('success', 'Tipo de contrato creado correctamente.');

            return $this->redirectToRoute('app_contract_types');
        }

        return $this->render('contract_type/new.html.twig', [
            'contractType' => $contractType,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contract_types_edit', methods: ['GET', 'POST'])]
    public function edit(ContractType $contractType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $contractType->setName($request->request->get('name', ''));
            $contractType->setDescription($request->request->get('description'));
            $contractType->setPrice($request->request->get('price'));

            $this->entityManager->flush();

            $this->addFlash('success', 'Tipo de contrato actualizado correctamente.');

            return $this->redirectToRoute('app_contract_types');
        }

        return $this->render('contract_type/edit.html.twig', [
            'contractType' => $contractType,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_contract_types_delete', methods: ['POST'])]
    public function delete(ContractType $contractType, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $contractType->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_contract_types');
        }

        if ($contractType->getTenants()->count() > 0) {
            $this->addFlash('error', 'No se puede eliminar el tipo de contrato porque tiene clientes asociados.');
            return $this->redirectToRoute('app_contract_types');
        }

        $this->entityManager->remove($contractType);
        $this->entityManager->flush();

        $this->addFlash('success', 'Tipo de contrato eliminado correctamente.');

        return $this->redirectToRoute('app_contract_types');
    }
}