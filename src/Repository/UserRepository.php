<?php

namespace App\Repository;

use App\Models\User;
use Doctrine\DBAL\Connection;

class UserRepository
{
    public const TABLENAME = 'personnel';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function authenticatedUser(string $identifier, string $hashedPassword): bool
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('id_personnel', 'nom', 'prenom', 'motsdepasse', 'nom_privilege', 'login')
            ->from(self::TABLENAME, self::TABLENAME);
        if (is_numeric($identifier)) {
            $qb->where(self::TABLENAME . '.id_personnel = :identifier');
        } else {
            $qb->where(self::TABLENAME . '.login = :identifier');
        }
        $dataUser = $qb->andWhere(self::TABLENAME . '.motsdepasse = :password')
            ->setParameter('identifier', $identifier)
            ->setParameter('password', $hashedPassword)
            ->executeQuery()
            ->fetchAssociative();
        return $dataUser ? true : false;
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('id_personnel', 'nom', 'prenom', 'motsdepasse', 'nom_privilege', 'login')
            ->from(self::TABLENAME, self::TABLENAME);
        if (is_numeric($identifier)) {
            $qb->where(self::TABLENAME . '.id_personnel = :identifier');
        } else {
            $qb->where(self::TABLENAME . '.login = :identifier');
        }
        $dataUser = $qb->setParameter('identifier', $identifier)
            ->executeQuery()
            ->fetchAssociative();

        return new User(
            $dataUser['id_personnel'],
            $dataUser['nom'],
            $dataUser['prenom'],
            $dataUser['nom_privilege'],
            $dataUser['login']
        );
    }
}
