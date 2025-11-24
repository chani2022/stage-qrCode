<?php

namespace App\Models;

class MouvementExterne
{
    public const TYPE_ENTREE = 'entree';
    public const TYPE_SORTIE = 'sortie';

    private ?int $idMouvementExterne;
    private string $type;
    private \DateTime $dateHeure;
    private ?string $photoCin;
    private ?string $motif;
    private ?string $observations;
    private \DateTime $dateEnregistrement;

    public function __construct(
        ?int $idMouvementExterne = null,
        ?string $type = null,
        ?\DateTime $dateHeure = null,
        ?string $photoCin = null,
        ?string $motif = null,
        ?string $observations = null,
        ?\DateTime $dateEnregistrement = null
    ) {
        $this->idMouvementExterne = $idMouvementExterne;
        $this->type = $type ?? '';
        $this->dateHeure = $dateHeure ?? new \DateTime();
        $this->photoCin = $photoCin;
        $this->motif = $motif;
        $this->observations = $observations;
        $this->dateEnregistrement = $dateEnregistrement ?? new \DateTime();
    }

    // Getters
    public function getIdMouvementExterne(): ?int { 
        return $this->idMouvementExterne; 
    }
    
    public function getType(): string { 
        return $this->type; 
    }
    
    public function getDateHeure(): \DateTime { 
        return $this->dateHeure; 
    }
    
    public function getPhotoCin(): ?string { 
        return $this->photoCin; 
    }
    
    public function getMotif(): ?string { 
        return $this->motif; 
    }
    
    public function getObservations(): ?string { 
        return $this->observations; 
    }
    
    public function getDateEnregistrement(): \DateTime { 
        return $this->dateEnregistrement; 
    }
    

    // Setters
    public function setIdMouvementExterne(?int $idMouvementExterne): self { 
        $this->idMouvementExterne = $idMouvementExterne; 
        return $this; 
    }
    
    public function setType(string $type): self { 
        $this->type = $type; 
        return $this; 
    }
    
    public function setDateHeure(\DateTime $dateHeure): self { 
        $this->dateHeure = $dateHeure; 
        return $this; 
    }
    
    public function setPhotoCin(?string $photoCin): self { 
        $this->photoCin = $photoCin; 
        return $this; 
    }
    
    public function setMotif(?string $motif): self { 
        $this->motif = $motif; 
        return $this; 
    }
    
    public function setObservations(?string $observations): self { 
        $this->observations = $observations; 
        return $this; 
    }
    
    public function setDateEnregistrement(\DateTime $dateEnregistrement): self { 
        $this->dateEnregistrement = $dateEnregistrement; 
        return $this; 
    }
    

    // Helper methods
    public function getTypeLabel(): string 
    { 
        return $this->type === self::TYPE_ENTREE ? 'Entrée' : 'Sortie'; 
    }
    
    public function isEntry(): bool 
    { 
        return $this->type === self::TYPE_ENTREE; 
    }
    
    public function isExit(): bool 
    { 
        return $this->type === self::TYPE_SORTIE; 
    }
    
    public function toArray(): array 
    { 
        return [
            'id_mouvement_externe' => $this->idMouvementExterne,
            'type' => $this->type,
            'date_heure' => $this->dateHeure->format('Y-m-d H:i:s'),
            'photo_cin' => $this->photoCin,
            'motif' => $this->motif,
            'observations' => $this->observations,
            'date_enregistrement' => $this->dateEnregistrement->format('Y-m-d H:i:s'),
        ]; 
    }
}
