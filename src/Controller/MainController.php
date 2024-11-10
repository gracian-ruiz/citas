<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/hacker', name: 'template_inicio')]
    public function index(): Response
    {
        // Datos del archivo
        $data = [
            ["Patata", "oi8", "oo"],
            ["ElMejor", "oF8", "Fo"],
            ["BoLiTa", "0123456789", "23"],
            ["Azul", "01", "01100"],
            ["OtRo", "54?t", "?4?"],
            ["Manolita", "kju2aq", "u2ka"],
            ["PiMiEnTo", "_-/.!#", "#_"]
        ];

        // Función para decodificar una puntuación
        function decode_score($digits, $encoded_score)
        {
            $base = strlen($digits);
            $score = 0;

            // Convertimos cada carácter a su valor en la base dada
            for ($i = 0; $i < strlen($encoded_score); $i++) {
                $char = $encoded_score[$i];
                $position = strpos($digits, $char); // Encuentra la posición en los dígitos
                $score = $score * $base + $position;
            }
            return $score;
        }

        // Decodificar las puntuaciones
        $decodedData = [];
        foreach ($data as $entry) {
            list($username, $digits, $encoded_score) = $entry;
            $decoded_score = decode_score($digits, $encoded_score);
            $decodedData[] = "$username,$decoded_score";
        }

        // Renderizar la vista con los datos decodificados
        return $this->render('hacker.html.twig', [
            'decodedData' => $decodedData,
        ]);
    }
}
