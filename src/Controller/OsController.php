<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\OperatingSystem;
use App\Entity\OperatingSystemVersion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/operating-systems')]
#[IsGranted('ROLE_ADMIN')]
class OsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_admin_os')]
    public function index(): Response
    {
        $oss = $this->entityManager->getRepository(OperatingSystem::class)->findBy([], ['name' => 'ASC']);

        return $this->render('os/index.html.twig', [
            'operatingSystems' => $oss,
        ]);
    }

    #[Route('/new', name: 'app_admin_os_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $os = new OperatingSystem();

        if ($request->isMethod('POST')) {
            try {
                $os->setName($request->request->get('name', ''));
                $os->setColor($request->request->get('color', 'info'));

                $this->entityManager->persist($os);
                $this->entityManager->flush();

                $this->addFlash('success', 'Sistema operativo "' . $os->getName() . '" creado correctamente.');

                return $this->redirectToRoute('app_admin_os');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo crear el sistema operativo. Intente nuevamente.');
            }
        }

        return $this->render('os/new.html.twig', ['operatingSystem' => $os]);
    }

    #[Route('/{id}/edit', name: 'app_admin_os_edit', methods: ['GET', 'POST'])]
    public function edit(OperatingSystem $os, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $newName = $request->request->get('name', '');
                
                $os->setName($newName);
                $os->setColor($request->request->get('color', 'info'));

                $connection = $this->entityManager->getConnection();
                $osId = $os->getId();
                
                $connection->executeStatement(
                    'UPDATE server SET os = :newName WHERE operating_system_version_id IN (SELECT id FROM operating_system_version WHERE operating_system_id = :osId)',
                    ['newName' => $newName, 'osId' => $osId]
                );

                $this->entityManager->flush();

                $this->addFlash('success', 'Sistema operativo actualizado correctamente.');

                return $this->redirectToRoute('app_admin_os');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo actualizar. Intente nuevamente.');
            }
        }

        $versions = $this->entityManager->getRepository(OperatingSystemVersion::class)->findByOperatingSystem($os->getId());

        return $this->render('os/edit.html.twig', [
            'operatingSystem' => $os,
            'versions' => $versions,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_os_delete', methods: ['POST'])]
    public function delete(OperatingSystem $os, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_os_' . $os->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_admin_os');
        }

        $name = $os->getName();

        try {
            $this->entityManager->remove($os);
            $this->entityManager->flush();

            $this->addFlash('success', 'Sistema operativo "' . $name . '" eliminado correctamente.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'No se pudo eliminar el sistema operativo.');
        }

        return $this->redirectToRoute('app_admin_os');
    }

    #[Route('/{id}/version/add', name: 'app_admin_os_version_add', methods: ['POST'])]
    public function addVersion(OperatingSystem $os, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('add_version_' . $os->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_admin_os_edit', ['id' => $os->getId()]);
        }

        $versionName = $request->request->get('version', '');

        if (empty($versionName)) {
            $this->addFlash('error', 'La versión no puede estar vacía.');
            return $this->redirectToRoute('app_admin_os_edit', ['id' => $os->getId()]);
        }

        try {
            $version = new OperatingSystemVersion();
            $version->setOperatingSystem($os);
            $version->setVersion($versionName);
            $version->setColor('info');

            $this->entityManager->persist($version);
            $this->entityManager->flush();

            $this->addFlash('success', 'Versión "' . $versionName . '" agregada correctamente.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'No se pudo agregar la versión.');
        }

        return $this->redirectToRoute('app_admin_os_edit', ['id' => $os->getId()]);
    }

    #[Route('/version/{id}/delete', name: 'app_admin_os_version_delete', methods: ['POST'])]
    public function deleteVersion(OperatingSystemVersion $version, Request $request): Response
    {
        $osId = $version->getOperatingSystem()->getId();

        if (!$this->isCsrfTokenValid('delete_version_' . $version->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_admin_os_edit', ['id' => $osId]);
        }

        try {
            $this->entityManager->remove($version);
            $this->entityManager->flush();

            $this->addFlash('success', 'Versión eliminada correctamente.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'No se pudo eliminar la versión.');
        }

        return $this->redirectToRoute('app_admin_os_edit', ['id' => $osId]);
    }
}