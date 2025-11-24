<?php

namespace App\Controller;

use App\Models\MouvementInterne;
use App\Models\Vehicule;
use App\Repository\MouvementInterneRepository;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mouvements-internes')]
class MouvementInterneController extends AbstractController
{
    public function __construct(
        private MouvementInterneRepository $mouvementInterneRepository,
        private UserRepository $userRepository,
        private VehiculeRepository $vehiculeRepository
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->query->get('type'),
            'date_from' => $request->query->get('date_from') ? new \DateTime($request->query->get('date_from')) : null,
            'date_to' => $request->query->get('date_to') ? new \DateTime($request->query->get('date_to')) : null,
            'personnel_id' => $request->query->get('personnel_id'),
            'with_vehicule' => $request->query->get('with_vehicule') === 'true',
        ];

        $mouvements = $this->mouvementInterneRepository->findByFilters($filters);
        
        return $this->json([
            'data' => array_map([$this, 'serializeMouvement'], $mouvements),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $mouvement = $this->mouvementInterneRepository->find($id);
        
        if (!$mouvement) {
            return $this->json([
                'error' => 'Mouvement non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'data' => $this->serializeMouvement($mouvement),
        ]);
    }

    #[Route('/last-for-personnel/{personnelId}', methods: ['GET'])]
    public function getLastForPersonnel(int $personnelId): JsonResponse
    {
        // Check if personnel exists
        $personnel = $this->userRepository->find($personnelId);
        if (!$personnel) {
            return $this->json([
                'error' => 'Personnel non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        $lastMovement = $this->mouvementInterneRepository->getLastMovementForPersonnel($personnelId);
        
        if (!$lastMovement) {
            return $this->json([
                'data' => null,
            ]);
        }

        return $this->json([
            'data' => $this->serializeMouvement($lastMovement),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            // Validate required fields
            $requiredFields = ['type', 'id_personnel'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Le champ '$field' est requis");
                }
            }

            // Check if personnel exists
            $personnel = $this->userRepository->find($data['id_personnel']);
            if (!$personnel) {
                return $this->json([
                    'error' => 'Personnel non trouvé',
                ], Response::HTTP_NOT_FOUND);
            }

            // Validate movement type
            if (!in_array($data['type'], ['entree', 'sortie'])) {
                return $this->json([
                    'error' => 'Type de mouvement invalide. Valeurs acceptées: entree, sortie',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Note: consecutive movements of the same type are allowed.
            $lastMovement = $this->mouvementInterneRepository->getLastMovementForPersonnel($data['id_personnel']);

            // Handle vehicle logic
            $vehicule = null;
            $apportVehicule = false;
            
            if (!empty($data['apport_vehicule']) && $data['apport_vehicule'] === true) {
                $apportVehicule = true;
                
                // Find existing vehicle for this personnel
                $vehicule = $this->vehiculeRepository->findOneByPersonnel($data['id_personnel']);
                
                // For exit movements with vehicle, check if vehicle is in parking
                if ($data['type'] === 'sortie') {
                    if (!$vehicule || ($vehicule->getParking() !== 'oui')) {
                        return $this->json([
                            'error' => 'Aucun véhicule n\'est enregistré dans le parking pour ce personnel.',
                        ], Response::HTTP_BAD_REQUEST);
                    }
                }
                
                // For entry movements with vehicle, photo is required
                if ($data['type'] === 'entree') {
                    if (!isset($data['vehicle_photo']) || empty($data['vehicle_photo'])) {
                        return $this->json([
                            'error' => 'La photo du véhicule est requise pour un mouvement d\'entrée avec véhicule.',
                        ], Response::HTTP_BAD_REQUEST);
                    }
                }
                
                if (!$vehicule) {
                    // Create new vehicle record for this personnel
                    // For entry movements, vehicle is entering parking
                    // For exit movements, vehicle is leaving parking
                    $parkingStatus = ($data['type'] === 'entree') ? 'oui' : 'non';
                    $vehicule = new Vehicule(
                        null, // id_vehicule (auto-generated)
                        null, // photo (will be set below)
                        new \DateTime(), // date_enregistrement
                        $parkingStatus, // parking status based on movement type
                        $data['id_personnel'], // id_personnel
                        null // id_personne_externe
                    );
                }
                
                // Handle vehicle photo upload if provided
                if (isset($data['vehicle_photo']) && !empty($data['vehicle_photo'])) {
                    $photoData = base64_decode($data['vehicle_photo']);
                    if ($photoData !== false) {
                        // Generate unique filename
                        $filename = 'vehicle_' . uniqid() . '.jpg';
                        $filepath = $this->getParameter('kernel.project_dir') . '/public/vehicle_photos/' . $filename;
                        
                        // Save the photo
                        file_put_contents($filepath, $photoData);
                        
                        // Update vehicle with photo
                        $vehicule->setPhoto($filename);
                    }
                }
                
                // Update parking status based on movement type
                $parkingStatus = ($data['type'] === 'entree') ? 'oui' : 'non';
                $vehicule->setParking($parkingStatus);
                
                // Save vehicle record
                $this->vehiculeRepository->save($vehicule);
            }

            // Create and save the movement
            $mouvement = new MouvementInterne(
                null,
                $data['type'],
                isset($data['date_heure']) ? new \DateTime($data['date_heure']) : null,
                $apportVehicule,
                $personnel->getId(),
                $vehicule?->getIdVehicule()
            );

            // Set the personnel object for serialization
            $mouvement->setPersonnel($personnel);
            
            // Set the vehicle object if present
            if ($vehicule) {
                $mouvement->setVehicule($vehicule);
            }

            $this->mouvementInterneRepository->save($mouvement);

            return $this->json([
                'data' => $this->serializeMouvement($mouvement),
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la création du mouvement',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[IsGranted('ROLE_MANAGER')]
    #[Route('/{id}', methods: ['DELETE'])]     
    public function delete(int $id): JsonResponse
    {
        $mouvement = $this->mouvementInterneRepository->find($id);
        
        if (!$mouvement) {
            return $this->json([
                'error' => 'Mouvement non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->mouvementInterneRepository->delete(['id_mouvement_interne' => $id]);
            
            return $this->json([
                'message' => 'Mouvement supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression du mouvement',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function serializeMouvement(MouvementInterne $mouvement): array
    {
        $data = [
            'id' => $mouvement->getIdMouvementInterne(),
            'type' => $mouvement->getType(),
            'type_label' => $mouvement->getTypeLabel(),
            'date_heure' => $mouvement->getDateHeure()->format('Y-m-d H:i:s'),
            'apport_vehicule' => $mouvement->getApportVehicule(),
            'personnel' => [
                'id' => $mouvement->getPersonnel()?->getId() ?? $mouvement->getIdPersonnel(),
                'nom' => $mouvement->getPersonnel()?->getNom() ?? $mouvement->getNom(),
                'prenom' => $mouvement->getPersonnel()?->getPrenom() ?? $mouvement->getPrenom(),
                'photo' => $mouvement->getPersonnel()?->getPhoto() ?? $mouvement->getPersonnelPhoto(),
                'nom_fonction' => $mouvement->getPersonnel()?->getNomFonction() ?? $mouvement->getNomFonction(),
                'sexe' => $mouvement->getPersonnel()?->getSexe() ?? $mouvement->getSexe(),
            ],
        ];

        if ($mouvement->getApportVehicule()) {
            $data['vehicule'] = [
                'id' => $mouvement->getVehicule()?->getIdVehicule() ?? $mouvement->getIdVehicule(),
                'photo' => $mouvement->getVehicule()?->getPhoto() ?? $mouvement->getVehiculePhoto(),
            ];
        }

        return $data;
    }
}
