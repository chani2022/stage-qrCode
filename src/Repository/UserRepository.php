<?php

namespace App\Repository;

use App\Models\User;
use Doctrine\DBAL\Connection;

class UserRepository extends AbstractRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->tableName = 'personnel';
    }

    /**
     * Vérifier si les données envoyé sont bien presents dans la table.
     * 
     * @param string $identifier
     * @param string $hashedPassword
     * @return bool
     */
    public function authenticatedUser(string $identifier, string $hashedPassword): bool
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('id_personnel', 'nom', 'prenom', 'motsdepasse', 'nom_privilege', 'login')
            ->from($this->tableName, $this->tableName);
        if (is_numeric($identifier)) {
            $qb->where($this->tableName . '.id_personnel = :identifier');
        } else {
            $qb->where($this->tableName . '.login = :identifier');
        }
        $dataUser = $qb->andWhere($this->tableName . '.motsdepasse = :password')
            ->setParameter('identifier', $identifier)
            ->setParameter('password', $hashedPassword)
            ->executeQuery()
            ->fetchAssociative();
        return $dataUser ? true : false;
    }
    /**
     * Charger l'utilisateur via son identifiant
     * 
     * @param string $identifier
     * @return User
     */
    public function findByIdentifier(string $identifier): User
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('id_personnel', 'nom', 'prenom', 'motsdepasse', 'nom_privilege', 'login')
            ->from($this->tableName, $this->tableName);
        if (is_numeric($identifier)) {
            $qb->where($this->tableName . '.id_personnel = :identifier');
        } else {
            $qb->where($this->tableName . '.login = :identifier');
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

    public function findAll(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('id_personnel', 'nom', 'prenom', 'nom_privilege', 'login')
            ->from($this->tableName, $this->tableName)
            ->where($this->tableName . '.actif = :actif')
            ->setParameter('actif', 'Oui')
            ->orderBy('id_personnel')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
