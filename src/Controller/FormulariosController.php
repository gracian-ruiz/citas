<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Persona;
use App\Form\PersonaValidationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class FormulariosController extends AbstractController
{
    #[Route('/formularios/validacion', name: 'formularios_validacion')]
    public function validacion(Request $request, EntityManagerInterface $em): Response
    {
        $persona = new Persona();
        $form = $this->createForm(PersonaValidationType::class, $persona);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verifica si la persona ya existe por su DNI
            $personaExistente = $em->getRepository(Persona::class)->findOneBy(['dni' => $persona->getDni()]);

            if ($personaExistente) {
                // Verifica si la persona tiene una cita existente
                $fechaHoraCitaExistente = $personaExistente->getFechaHoraCita();

                if ($fechaHoraCitaExistente) {
                    $ahora = new \DateTime();  // Fecha actual

                    if ($fechaHoraCitaExistente > $ahora) {
                        // Si la cita ya es futura, no permitimos asignar una nueva
                        $fechaHoraCitaPendiente = $fechaHoraCitaExistente->format('Y-m-d H:i:s');
                        return $this->json([
                            'estado' => 'error',
                            'mensaje' => 'Ya tienes una cita pendiente para la fecha ' . $fechaHoraCitaPendiente . '. No puedes crear una nueva hasta que se haya realizado.'
                        ], 400);
                    }                    

                    // Si la cita ya es pasada, procedemos a asignar una nueva cita
                    $fechaHoraCita = $this->asignarSiguienteCitaDisponible($em);
                    if ($fechaHoraCita === null) {
                        return $this->json([
                            'estado' => 'error',
                            'mensaje' => 'No hay citas disponibles'
                        ], 400);
                    }

                    $personaExistente->setFechaHoraCita($fechaHoraCita);  // Asigna la nueva cita
                    $personaExistente->setTipoCita('REVISION');  // Cambiar tipo de cita a 'REVISION'
                    $em->persist($personaExistente);
                    $em->flush();

                    // Enviar correo de confirmación de cita
/*                     $this->sendCitaConfirmationEmail(
                        $personaExistente->getEmail(), 
                        $personaExistente->getFechaHoraCita()->format('Y-m-d H:i:s'),
                        $personaExistente->getTipoCita()
                    ); */

                    return $this->json([
                        'estado' => 'ok',
                        'mensaje' => 'Se asignó una nueva cita de revisión',
                        'fechaHoraCita' => $personaExistente->getFechaHoraCita()->format('Y-m-d H:i:s'),
                        'tipoCita' => 'REVISION',
                    ], 200);
                }
            } else {
                // Si la persona no existe, permitimos solo 'PRIMERA CONSULTA'
                $persona->setTipoCita('PRIMERA CONSULTA');

                // Asigna la cita automática
                $fechaHoraCita = $this->asignarSiguienteCitaDisponible($em);
                if ($fechaHoraCita === null) {
                    return $this->json([
                        'estado' => 'error',
                        'mensaje' => 'No hay citas disponibles'
                    ], 400);
                }

                $persona->setFechaHoraCita($fechaHoraCita);
                $em->persist($persona);
                $em->flush();


                // Enviar correo de confirmación de cita
/*                 $this->sendCitaConfirmationEmail(
                    $persona->getEmail(),
                    $persona->getFechaHoraCita()->format('Y-m-d H:i:s'),
                    $persona->getTipoCita()
                ); */

                return $this->json([
                    'estado' => 'ok',
                    'mensaje' => 'Se creó el registro exitosamente',
                    'fechaHoraCita' => $persona->getFechaHoraCita()->format('Y-m-d H:i:s'),
                    'tipoCita' => $persona->getTipoCita(),
                ], 201);
            }
        }

        // Si no es válido o no se ha enviado el formulario
        return $this->render('formularios/validacion.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function asignarSiguienteCitaDisponible(EntityManagerInterface $em): ?\DateTime
    {
        $horaInicio = new \DateTime('10:00');
        $horaFin = new \DateTime('22:00');
        $intervalo = new \DateInterval('PT1H');

        // Obtener la última cita registrada
        $ultimaCita = $em->getRepository(Persona::class)->createQueryBuilder('p')
            ->orderBy('p.fechaHoraCita', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($ultimaCita) {
            $fechaActual = clone $ultimaCita->getFechaHoraCita();
            $fechaActual->add($intervalo); // La siguiente hora después de la última cita
        } else {
            $fechaActual = new \DateTime();
            
            // Ajustar la fecha actual al próximo día laboral si es necesario
            if (!$this->esDiaLaboral($fechaActual)) {
                $fechaActual = $this->ajustarAlProximoDiaLaboral($fechaActual);
            } else {
                // Si es un día laboral, ajusta la hora a las 10:00 AM
                $fechaActual->setTime(10, 0);
            }
        }

        // Establecer horaFin a las 10 PM del mismo día
        $horaFin = clone $fechaActual; 
        $horaFin->setTime(22, 0);

        while (true) {
            // Si llegamos al fin del día, mover al próximo día laboral y reiniciar la hora
            if ($fechaActual >= $horaFin) {
                $fechaActual = (clone $horaInicio)->setDate($fechaActual->format('Y'), $fechaActual->format('m'), $fechaActual->format('d'));
                $fechaActual->add(new \DateInterval('P1D'));
                $fechaActual = $this->ajustarAlProximoDiaLaboral($fechaActual);

                // Actualizar horaFin para el nuevo día
                $horaFin = clone $fechaActual; 
                $horaFin->setTime(22, 0);
            }

            // Verifica si la fecha y hora están disponibles
            $citaExistente = $em->getRepository(Persona::class)->findOneBy(['fechaHoraCita' => $fechaActual]);
            if ($citaExistente === null) {
                return $fechaActual; // La hora está libre
            }

            // Incrementa la hora
            $fechaActual->add($intervalo);
        }

        return null; // No hay citas disponibles
    }

    private array $festivos = [
        '2024-11-12', // Ejemplo de festivo (Navidad)
        '2024-01-01', // Año Nuevo
        // Agregar más fechas festivas según sea necesario
    ];

    private function esDiaLaboral(\DateTime $fecha): bool
    {
        // 1 (Lunes) a 5 (Viernes) son días laborales
        $esLaboral = $fecha->format('N') >= 1 && $fecha->format('N') <= 5;
        
        // Verificar si la fecha es un festivo
        if (in_array($fecha->format('Y-m-d'), $this->festivos)) {
            $esLaboral = false;
        }
        
        return $esLaboral;
    }

    private function ajustarAlProximoDiaLaboral(\DateTime $fecha): \DateTime
    {
        // Si no es un día laboral, ajusta al próximo día laboral
        while (!$this->esDiaLaboral($fecha)) {
            $fecha->add(new \DateInterval('P1D'));
        }

        // Ajusta la hora a las 10:00 AM
        return $fecha->setTime(10, 0);
    }

        // Función para enviar el correo de confirmación de cita
        private function sendCitaConfirmationEmail($clienteEmail, $fechaHoraCita, $tipoCita)
        {
            $email = (new Email())
                ->from('tu-correo@tudominio.com')  // Aquí va tu correo
                ->to(new Address($clienteEmail))
                ->subject('Confirmación de Cita')
                ->html(
                    "<p>Estimado cliente,</p>
                    <p>Su cita ha sido confirmada con los siguientes detalles:</p>
                    <ul>
                        <li><strong>Fecha y hora de la cita:</strong> {$fechaHoraCita}</li>
                        <li><strong>Tipo de cita:</strong> {$tipoCita}</li>
                    </ul>
                    <p>Gracias por confiar en nosotros.</p>"
                );
    
            // Enviar el correo
            $this->mailer->send($email);
        }
}
