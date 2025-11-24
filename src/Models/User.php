<?php

namespace App\Models;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use JsonSerializable;

class User implements UserInterface, PasswordAuthenticatedUserInterface, JsonSerializable
{

    private ?int $id = null;
    private ?string $identifier = null;
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?string $login = null;
    private ?string $password = null;
    private array $roles = [];
    private ?string $nomPrivilege = null;
    private ?string $cin = null;
    private ?string $dateEmbauche = null;
    private ?string $telephone = null;
    private ?string $adresse = null;
    private ?string $nomFonction = null;
    private ?string $photo = null;
    private ?string $sexe = null;
    private ?string $motsdepasse = null;
    private ?string $actif = null;

    public function __construct(
        int $id,
        string $nom,
        string $prenom,
        string $roles,
        string $login,
        ?string $cin = null,
        ?string $dateEmbauche = null,
        ?string $telephone = null,
        ?string $adresse = null,
        ?string $nomFonction = null,
        ?string $photo = null,
        ?string $sexe = null,
        ?string $motsdepasse = null,
        ?string $actif = null
    ) {
        $this->id = $id;
        $this->identifier = (string)$id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->login = $login;
        $this->nomPrivilege = $roles; // Store the raw nom_privilege value
        $this->cin = $cin;
        $this->dateEmbauche = $dateEmbauche;
        $this->telephone = $telephone;
        $this->adresse = $adresse;
        $this->nomFonction = $nomFonction;
        $this->photo = $photo;
        $this->sexe = $sexe;
        $this->motsdepasse = $motsdepasse;
        $this->actif = $actif;
        $this->roles = $this->assignRoles($roles, $nomFonction); // Transform to Symfony roles
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function setIdentifier(?string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function setRoles(string $roles): static
    {
        $this->roles = $this->assignRoles($roles);

        return $this;
    }

    public function getRoles(): array
    {
        if (count($this->roles) == 0) {
            return ['ROLE_USER'];
        }
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function eraseCredentials(): void {}

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function getDateEmbauche(): ?string
    {
        return $this->dateEmbauche;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getNomFonction(): ?string
    {
        return $this->nomFonction;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function getMotsdepasse(): ?string
    {
        return $this->motsdepasse;
    }

    public function getActif(): ?string
    {
        return $this->actif;
    }

    public function getNomPrivilege(): ?string
    {
        return $this->nomPrivilege;
    }

    private function assignRoles(string $roles, ?string $nomFonction): array
    {
        $roleList = match ($roles) {
            'admin', 'superadmin', 'default' => ['ROLE_ADMIN'],
            default => ['ROLE_USER'],
        };

        if ($nomFonction) {
            $normalized = strtolower(trim($nomFonction));
            $fonctionRole = match ($normalized) {
                'securite' => 'ROLE_SECURITY',
                'responsable personnel', 'directeur de plateau', 'directeur général', 'directeur' => 'ROLE_MANAGER',
                default => null,
            };

            if ($fonctionRole && !in_array($fonctionRole, $roleList, true)) {
                $roleList[] = $fonctionRole;
            }
        }

        return array_values(array_unique($roleList));
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'login' => $this->login,
            'roles' => $this->roles,
            'identifier' => $this->identifier,
            'nom_privilege' => $this->nomPrivilege,
            'cin' => $this->cin,
            'date_embauche' => $this->dateEmbauche,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'nom_fonction' => $this->nomFonction,
            'photo' => $this->photo,
            'sexe' => $this->sexe,
        ];
    }
}
