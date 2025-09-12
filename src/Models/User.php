<?php

namespace App\Models;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends AbstractQueryBuilder implements UserInterface
{
    const TABLENAME = 'personnel';

    private int $id;
    private ?string $nom;
    private ?string $prenom;
    private array $roles = [];
    private string $identifier;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function getId(): int
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

    public function setId(int $id): static
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

    public function findByIdentifier(string $identifier): ?array
    {
        return $this->getQueryBuilder()
            ->select(['id_personnel', 'nom', 'prenom'])
            ->from(self::TABLENAME, self::TABLENAME)
            ->where(self::TABLENAME . '.id_personnel = :identifier')
            ->setParameter('identifier', $identifier)
            ->executeQuery()
            ->fetchAssociative()
        ;

        // return $qb->executeQuery()->fetchOne();
    }

    public function findUser(array $criteria): ?User
    {
        $qb = $this->getQueryBuilder()
            ->from(self::TABLENAME, self::TABLENAME);
        foreach ($criteria as $field => $value) {
            $bind = ':' . $field;
            $qb->andWhere(self::TABLENAME . '.' . $field . $bind)
                ->setParameter($field, $value);
        }

        return $qb->executeQuery()->fetchOne();
    }

    public function setProperties(array $properties): void
    {
        foreach ($properties as $propertie => $value) {
            $setter = 'set' . ucfirst($propertie);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }
}
