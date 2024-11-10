<?php

// src/Entity/Persona.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'persona')]
class Persona
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $dni = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $telefono = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $tipoCita = null; // Campo para tipo de cita

    #[ORM\Column(type: "datetime", nullable: true)] // Permitir valores NULL
    private ?\DateTime $fechaHoraCita = null; // Campo para fecha y hora de la cita

    // Métodos getter y setter

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): self
    {
        $this->dni = $dni;
        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): self
    {
        $this->telefono = $telefono;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getTipoCita(): ?string
    {
        return $this->tipoCita;
    }

    public function setTipoCita(string $tipoCita): self
    {
        $this->tipoCita = $tipoCita;
        return $this;
    }

    public function getFechaHoraCita(): ?\DateTime
    {
        return $this->fechaHoraCita;
    }

    public function setFechaHoraCita(?\DateTime $fechaHoraCita): self
    {
        $this->fechaHoraCita = $fechaHoraCita;
        return $this;
    }
}
