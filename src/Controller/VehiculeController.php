<?php

namespace App\Controller;

use App\Models\Vehicule;
use App\Repository\VehiculeRepository;
use App\Repository\UserRepository;
use App\Repository\MouvementInterneRepository;
use App\Models\MouvementInterne;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vehicules')]
class VehiculeController extends AbstractController
{
    public function __construct(
        private VehiculeRepository $vehiculeRepository,
        private UserRepository $userRepository,
        private MouvementInterneRepository $mouvementInterneRepository
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'personnel_id' => $request->query->get('personnel_id'),
            'parking' => $request->query->get('parking'),
            'date' => $request->query->get('date'),
        ];

        $vehicules = $this->vehiculeRepository->findBy($filters);
        
        return $this->json([
            'data' => array_map([$this, 'serializeVehicule'], $vehicules),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/toggle-parking', methods: ['POST'])]
    public function toggleParking(int $id, Request $request): JsonResponse
    {
        $vehicule = $this->vehiculeRepository->find($id);

        if (!$vehicule) {
            return $this->json([
                'error' => 'Véhicule non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($vehicule->getIdPersonnel() === null) {
            return $this->json([
                'error' => 'Ce véhicule n\'est pas associé à un personnel actif',
            ], Response::HTTP_BAD_REQUEST);
        }

        $currentStatus = $vehicule->getParking() === 'oui';
        $newStatus = !$currentStatus;

        try {
            $vehicule->setParking($newStatus ? 'oui' : 'non');
            $now = new \DateTime();
            $vehicule->setDateEnregistrement($now);

            $this->vehiculeRepository->save($vehicule);

            $mouvement = new MouvementInterne(
                null,
                $newStatus ? MouvementInterne::TYPE_ENTREE : MouvementInterne::TYPE_SORTIE,
                $now,
                true,
                $vehicule->getIdPersonnel(),
                $vehicule->getIdVehicule()
            );

            $this->mouvementInterneRepository->save($mouvement);

            return $this->json([
                'data' => $this->serializeVehicule($vehicule),
                'movement' => [
                    'id' => $mouvement->getIdMouvementInterne(),
                    'type' => $mouvement->getType(),
                    'date_heure' => $mouvement->getDateHeure()->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la mise à jour du statut du véhicule',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $vehicule = $this->vehiculeRepository->find($id);
        
        if (!$vehicule) {
            return $this->json([
                'error' => 'Véhicule non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'data' => $this->serializeVehicule($vehicule),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            // Validate required fields
            if (empty($data['photo'])) {
                throw new \InvalidArgumentException("La photo du véhicule est requise");
            }

            if (empty($data['id_personnel'])) {
                throw new \InvalidArgumentException("Un propriétaire (personnel) doit être spécifié");
            }

            $owner = $this->userRepository->find($data['id_personnel']);
            if (!$owner) {
                return $this->json([
                    'error' => 'Personnel non trouvé',
                ], Response::HTTP_NOT_FOUND);
            }

            // Create and save the vehicle
            $vehicule = new Vehicule(
                null,
                $data['photo'],
                new \DateTime(),
                $data['parking'] ?? 'non',
                $data['id_personnel'] ?? null,
                null
            );

            $this->vehiculeRepository->save($vehicule);

            return $this->json([
                'data' => $this->serializeVehicule($vehicule),
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la création du véhicule',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $vehicule = $this->vehiculeRepository->find($id);
        
        if (!$vehicule) {
            return $this->json([
                'error' => 'Véhicule non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        try {
            if (isset($data['parking'])) {
                $vehicule->setParking($data['parking']);
            }
            
            if (isset($data['photo'])) {
                $vehicule->setPhoto($data['photo']);
            }

            $this->vehiculeRepository->save($vehicule);

            return $this->json([
                'data' => $this->serializeVehicule($vehicule),
            ]);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la mise à jour du véhicule',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[IsGranted('ROLE_MANAGER')]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $vehicule = $this->vehiculeRepository->find($id);
        
        if (!$vehicule) {
            return $this->json([
                'error' => 'Véhicule non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // Delete the vehicle photo file if it exists
            $photo = $vehicule->getPhoto();
            if ($photo) {
                $photoPath = $this->getParameter('kernel.project_dir') . '/public/vehicle_photos/' . $photo;
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            $result = $this->vehiculeRepository->delete(['id_vehicule' => $id]);
            
            if ($result > 0) {
                return $this->json([
                    'message' => 'Véhicule supprimé avec succès',
                ]);
            } else {
                return $this->json([
                    'error' => 'Échec de la suppression du véhicule',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression du véhicule',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function serializeVehicule(Vehicule $vehicule): array
    {
        return [
            'id' => $vehicule->getIdVehicule(),
            'photo' => $vehicule->getPhoto(),
            'date_enregistrement' => $vehicule->getDateEnregistrement()->format('Y-m-d H:i:s'),
            'parking' => $vehicule->getParking(),
            'owner_type' => $vehicule->getOwnerType(),
            'owner_id' => $vehicule->getOwnerId(),
        ];
    }
}
