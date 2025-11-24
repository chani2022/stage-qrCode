<?php

namespace App\Repository;

use App\Models\MouvementInterne;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class MouvementInterneRepository extends AbstractRepository
{
    protected string $tableName = 'appm_mouvement_interne';

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function find(int $id): ?MouvementInterne
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id_mouvement_interne = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * @return MouvementInterne[]
     */
    public function findByFilters(array $filters = []): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('m.*, p.nom, p.prenom, p.photo as personnel_photo, p.nom_fonction, p.sexe, v.photo as vehicule_photo, v.id_vehicule')
            ->from($this->tableName, 'm')
            ->leftJoin('m', 'personnel', 'p', 'm.id_personnel = p.id_personnel')
            ->leftJoin('m', 'appm_vehicule', 'v', 'm.id_vehicule = v.id_vehicule')
            ->orderBy('m.date_heure', 'DESC');

        if (!empty($filters['type'])) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('m.date_heure >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from'], Types::DATETIME_MUTABLE);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('m.date_heure <= :dateTo')
               ->setParameter('dateTo', $filters['date_to'], Types::DATETIME_MUTABLE);
        }

        if (!empty($filters['personnel_id'])) {
            $qb->andWhere('m.id_personnel = :personnelId')
               ->setParameter('personnelId', $filters['personnel_id']);
        }

        if (!empty($filters['with_vehicule'])) {
            $qb->andWhere('m.apport_vehicule = true');
        }

        $data = $qb->executeQuery()->fetchAllAssociative();

        return array_map([$this, 'hydrate'], $data);
    }

    public function save(MouvementInterne $mouvement): void
    {
        $data = [
            'type' => $mouvement->getType(),
            'date_heure' => $mouvement->getDateHeure()->format('Y-m-d H:i:s'),
            'apport_vehicule' => $mouvement->getApportVehicule() ? 1 : 0,
            'id_personnel' => $mouvement->getIdPersonnel(),
            'id_vehicule' => $mouvement->getIdVehicule(),
        ];

        if ($mouvement->getIdMouvementInterne() === null) {
            $this->connection->insert($this->tableName, $data);
            $mouvement->setIdMouvementInterne((int)$this->connection->lastInsertId());
        } else {
            $this->connection->update(
                $this->tableName,
                $data,
                ['id_mouvement_interne' => $mouvement->getIdMouvementInterne()]
            );
        }
    }

    public function delete(array $criteria): int|string
    {
        return parent::delete($criteria);
    }

    public function getLastMovementForPersonnel(int $personnelId): ?MouvementInterne
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id_personnel = :personnelId')
            ->orderBy('date_heure', 'DESC')
            ->setMaxResults(1)
            ->setParameter('personnelId', $personnelId)
            ->executeQuery()
            ->fetchAssociative();

        return $data ? $this->hydrate($data) : null;
    }

    private function hydrate(array $data): MouvementInterne
    {
        $mouvement = new MouvementInterne(
            $data['id_mouvement_interne'],
            $data['type'],
            new \DateTime($data['date_heure']),
            (bool)$data['apport_vehicule'],
            (int)$data['id_personnel'],
            $data['id_vehicule'] ? (int)$data['id_vehicule'] : null
        );
        
        // Set joined data if available
        if (isset($data['nom'])) {
            $mouvement->setNom($data['nom']);  
        }
        if (isset($data['prenom'])) {
            $mouvement->setPrenom($data['prenom']);
        }
        if (isset($data['personnel_photo'])) { 
            $mouvement->setPersonnelPhoto($data['personnel_photo']);
        }
        if (isset($data['nom_fonction'])) {
            $mouvement->setNomFonction($data['nom_fonction']);
        }
        if (isset($data['sexe'])) {
            $mouvement->setSexe($data['sexe']);
        }
        if (isset($data['vehicule_photo'])) {  
            $mouvement->setVehiculePhoto($data['vehicule_photo']);
        }
        
        return $mouvement;
    }
}
