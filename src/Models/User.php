<?php

namespace App\Models;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends AbstractQueryBuilder implements UserInterface
{
    const TABLENAME = 'personnel';

    private array $roles = [];
    private string $identifier;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
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

    public function findByIdentifier(string $identifier): ?User
    {
        $qb = $this->getQueryBuilder()
            ->from(self::TABLENAME, self::TABLENAME)
            ->where(self::TABLENAME . '.matricule = :identifier')
            ->setParameter('identifier', $identifier)
            ->executeQuery()
            ->fetchOne();

        return $qb->executeQuery()->fetchOne();
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
}
