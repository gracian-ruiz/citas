<?php

// src/Controller/CitaController.php

namespace App\Controller;

use App\Entity\Persona;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class CitaController extends AbstractController
{
    #[Route('/citas', name: 'citas_panel')]
    public function index(EntityManagerInterface $em): Response
    {
        // Obtener todas las citas ordenadas por fecha y hora
        $citas = $em->getRepository(Persona::class)->findBy([], ['fechaHoraCita' => 'ASC']);

        return $this->render('citas.html.twig', [
            'citas' => $citas,
        ]);
    }
}
