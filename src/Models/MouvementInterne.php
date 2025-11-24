<?php

namespace App\Models;

class MouvementInterne
{
    public const TYPE_ENTREE = 'entree';
    public const TYPE_SORTIE = 'sortie';

    private ?int $idMouvementInterne;
    private string $type;
    private \DateTime $dateHeure;
    private bool $apportVehicule;
    private int $idPersonnel;
    private ?int $idVehicule;
    private ?Vehicule $vehicule = null;
    private ?User $personnel = null;
    
    // Properties for joined data
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?string $nomFonction = null;
    private ?string $sexe = null;
    private ?string $personnelPhoto = null;
    private ?string $vehiculePhoto = null;

    public function __construct(
        ?int $idMouvementInterne = null,
        string $type,
        ?\DateTime $dateHeure = null,
        bool $apportVehicule = false,
        int $idPersonnel,
        ?int $idVehicule = null
    ) {
        $this->idMouvementInterne = $idMouvementInterne;
        $this->setType($type);
        $this->dateHeure = $dateHeure ?: new \DateTime();
        $this->apportVehicule = $apportVehicule;
        $this->idPersonnel = $idPersonnel;
        
        if ($apportVehicule && $idVehicule === null) {
            throw new \InvalidArgumentException('Vehicle ID is required when apportVehicule is true');
        }
        $this->idVehicule = $idVehicule;
    }

    // Getters
    public function getIdMouvementInterne(): ?int { 
        return $this->idMouvementInterne; 
    }
    
    public function getType(): string { 
        return $this->type; 
    }
    
    public function getDateHeure(): \DateTime { 
        return $this->dateHeure; 
    }
    
    public function getApportVehicule(): bool { 
        return $this->apportVehicule; 
    }
    
    public function getIdPersonnel(): int { 
        return $this->idPersonnel; 
    }
    
    public function getIdVehicule(): ?int { 
        return $this->idVehicule; 
    }
    
    public function getVehicule(): ?Vehicule {
        return $this->vehicule;
    }
    
    public function getPersonnel(): ?User {
        return $this->personnel;
    }
    
    // Getters for joined data
    public function getNom(): ?string {
        return $this->nom;
    }
    
    public function getPrenom(): ?string {
        return $this->prenom;
    }
    
    public function getNomFonction(): ?string {
        return $this->nomFonction;
    }
    
    public function getSexe(): ?string {
        return $this->sexe;
    }
    
    public function getPersonnelPhoto(): ?string {
        return $this->personnelPhoto;
    }
    
    public function getVehiculePhoto(): ?string {
        return $this->vehiculePhoto;
    }

    // Setters with validation
    public function setType(string $type): self {
        if (!in_array($type, [self::TYPE_ENTREE, self::TYPE_SORTIE])) {
            throw new \InvalidArgumentException("Le type doit être 'entree' ou 'sortie'");
        }
        $this->type = $type;
        return $this;
    }
    
    public function setDateHeure(\DateTime $dateHeure): self {
        $this->dateHeure = $dateHeure;
        return $this;
    }
    
    public function setApportVehicule(bool $apportVehicule): self {
        $this->apportVehicule = $apportVehicule;
        return $this;
    }
    
    public function setIdMouvementInterne(int $idMouvementInterne): self {
        $this->idMouvementInterne = $idMouvementInterne;
        return $this;
    }
    
    public function setVehicule(?Vehicule $vehicule): self {
        $this->vehicule = $vehicule;
        $this->idVehicule = $vehicule ? $vehicule->getIdVehicule() : null;
        $this->apportVehicule = $vehicule !== null;
        return $this;
    }
    
    public function setPersonnel(?User $personnel): self {
        $this->personnel = $personnel;
        if ($personnel !== null) {
            $this->idPersonnel = $personnel->getId();
        }
        return $this;
    }
    
    // Setters for joined data
    public function setNom(?string $nom): self {
        $this->nom = $nom;
        return $this;
    }
    
    public function setPrenom(?string $prenom): self {
        $this->prenom = $prenom;
        return $this;
    }
    
    public function setNomFonction(?string $nomFonction): self {
        $this->nomFonction = $nomFonction;
        return $this;
    }
    
    public function setSexe(?string $sexe): self {
        $this->sexe = $sexe;
        return $this;
    }
    
    public function setPersonnelPhoto(?string $personnelPhoto): self {
        $this->personnelPhoto = $personnelPhoto;
        return $this;
    }
    
    public function setVehiculePhoto(?string $vehiculePhoto): self {
        $this->vehiculePhoto = $vehiculePhoto;
        return $this;
    }

    // Helper methods
    public function isEntree(): bool
    {
        return $this->type === self::TYPE_ENTREE;
    }

    public function isSortie(): bool
    {
        return $this->type === self::TYPE_SORTIE;
    }
    
    public function getTypeLabel(): string
    {
        return $this->isEntree() ? 'Entrée' : 'Sortie';
    }
    
    public function getTypeIcon(): string
    {
        return $this->isEntree() ? 'login' : 'logout';
    }
    
    public function getTypeColor(): string
    {
        return $this->isEntree() ? 'success' : 'warning';
    }
}
