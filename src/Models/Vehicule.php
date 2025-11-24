<?php

namespace App\Models;

class Vehicule
{
    private ?int $idVehicule;
    private ?string $photo;
    private ?\DateTime $dateEnregistrement;
    private ?string $parking;
    private ?int $idPersonnel;

    public function __construct(
        ?int $idVehicule = null,
        ?string $photo = null,
        ?\DateTime $dateEnregistrement = null,
        ?string $parking = 'non',
        ?int $idPersonnel = null
    ) {
        $this->idVehicule = $idVehicule;
        $this->photo = $photo;
        $this->dateEnregistrement = $dateEnregistrement ?: new \DateTime();
        $this->setParking($parking);
        $this->idPersonnel = $idPersonnel;
    }

    // Getters
    public function getIdVehicule(): ?int { return $this->idVehicule; }
    public function getPhoto(): ?string { return $this->photo; }
    public function getDateEnregistrement(): ?\DateTime { return $this->dateEnregistrement; }
    public function getParking(): ?string { return $this->parking; }
    public function getIdPersonnel(): ?int { return $this->idPersonnel; }

    // Setters with validation
    public function setPhoto(?string $photo): self { 
        $this->photo = $photo;
        return $this;
    }

    public function setDateEnregistrement(\DateTime $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;
        return $this;
    }

    public function setIdVehicule(int $idVehicule): self {
        $this->idVehicule = $idVehicule;
        return $this;
    }
    
    public function setParking(?string $parking): self {
        if ($parking !== null && !in_array($parking, ['oui', 'non'])) {
            throw new \InvalidArgumentException("Parking must be 'oui' or 'non'");
        }
        $this->parking = $parking;
        return $this;
    }

    // Helper methods
    public function isInParking(): bool
    {
        return $this->parking === 'oui';
    }

    public function getOwnerType(): ?string
    {
        if ($this->idPersonnel !== null) {
            return 'personnel';
        }
        return null;
    }

    public function getOwnerId(): ?int
    {
        return $this->idPersonnel;
    }

    public function setOwner(int $id, string $type): void
    {
        if ($type !== 'personnel') {
            throw new \InvalidArgumentException("Owner type must be 'personnel'");
        }
        
        $this->idPersonnel = $id;
    }
}
