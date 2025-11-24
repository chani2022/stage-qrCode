<?php

namespace App\Controller;

use App\Models\MouvementExterne;
use App\Repository\MouvementExterneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mouvements-externes')]
class MouvementExterneController extends AbstractController
{
    public function __construct(
        private MouvementExterneRepository $mouvementExterneRepository
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->query->get('type'),
            'date_from' => $request->query->get('date_from') ? new \DateTime($request->query->get('date_from')) : null,
            'date_to' => $request->query->get('date_to') ? new \DateTime($request->query->get('date_to')) : null,
            'photo_cin' => $request->query->get('photo_cin'),
        ];
        
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $movements = $this->mouvementExterneRepository->findByFilters($filters);
        
        return $this->json([
            'data' => array_map([$this, 'serializeMouvement'], $movements),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/today', methods: ['GET'])]
    public function today(): JsonResponse
    {
        $movements = $this->mouvementExterneRepository->getTodayMovements();
        
        return $this->json([
            'data' => array_map([$this, 'serializeMouvement'], $movements),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $mouvement = $this->mouvementExterneRepository->find($id);
        
        if (!$mouvement) {
            return $this->json([
                'error' => 'Mouvement externe non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'data' => $this->serializeMouvement($mouvement),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            // Validate required fields
            $requiredFields = ['type', 'photo_cin'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("Le champ '$field' est requis");
                }
            }

            // Validate type
            if (!in_array($data['type'], [MouvementExterne::TYPE_ENTREE, MouvementExterne::TYPE_SORTIE])) {
                throw new \InvalidArgumentException("Le type doit être 'entree' ou 'sortie'");
            }

            // Handle photo_cin - if it's base64, save it as a file
            $photoCinFilename = $data['photo_cin'];
            if ($this->isBase64($data['photo_cin'])) {
                $photoCinFilename = $this->saveBase64Image($data['photo_cin']);
            }

            // Create and save the external movement
            $mouvement = new MouvementExterne(
                null,
                $data['type'],
                $data['date_heure'] ? new \DateTime($data['date_heure']) : new \DateTime(),
                $photoCinFilename,
                $data['motif'] ?? null,
                $data['observations'] ?? null
            );

            $this->mouvementExterneRepository->save($mouvement);

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

    #[IsGranted('ROLE_USER')]
    #[Route('/personne-externe/{photoCin}/last', methods: ['GET'])]
    public function getLastByPhotoCin(string $photoCin): JsonResponse
    {
        $mouvement = $this->mouvementExterneRepository->getLastMovementForPhotoCin($photoCin);
        
        if (!$mouvement) {
            return $this->json([
                'data' => null,
            ], Response::HTTP_OK);
        }

        return $this->json([
            'data' => $this->serializeMouvement($mouvement),
        ]);
    }

    #[IsGranted('ROLE_MANAGER')]
    #[Route('/{id}', methods: ['DELETE'])]     
    public function delete(int $id): JsonResponse
    {
        try {
            $mouvement = $this->mouvementExterneRepository->find($id);
            
            if (!$mouvement) {
                return $this->json([
                    'error' => 'Mouvement externe non trouvé',
                    'debug' => ['id' => $id]
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the photo file if it exists
            $photoCin = $mouvement->getPhotoCin();
            if ($photoCin) {
                $photoPath = $this->getParameter('kernel.project_dir') . '/public/cin_photos/' . $photoCin;
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            $result = $this->mouvementExterneRepository->delete(['id_mouvement_externe' => $id]);
            
            return $this->json([
                'message' => 'Mouvement supprimé avec succès',
                'debug' => ['deleted_id' => $id, 'result' => $result]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression du mouvement',
                'details' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function serializeMouvement(MouvementExterne $mouvement): array
    {
        return [
            'id' => $mouvement->getIdMouvementExterne(),
            'type' => $mouvement->getType(),
            'type_label' => $mouvement->getTypeLabel(),
            'date_heure' => $mouvement->getDateHeure()->format('Y-m-d H:i:s'),
            'photo_cin' => $mouvement->getPhotoCin(),
            'motif' => $mouvement->getMotif(),
            'observations' => $mouvement->getObservations(),
            'date_enregistrement' => $mouvement->getDateEnregistrement()->format('Y-m-d H:i:s'),
            'is_entry' => $mouvement->isEntry(),
            'is_exit' => $mouvement->isExit(),
        ];
    }

    private function isBase64(string $string): bool
    {
        // Check if the string looks like base64 image data
        return base64_decode($string, true) !== false && preg_match('/^[a-zA-Z0-9\/\+=]+$/', $string);
    }

    private function saveBase64Image(string $base64Image): string
    {
        // Remove data URL prefix if present
        if (strpos($base64Image, 'data:image/') === 0) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        }

        // Decode the base64 image
        $imageData = base64_decode($base64Image);
        if ($imageData === false) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }

        // Generate a unique filename
        $filename = 'cin_' . uniqid() . '.jpg';
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/cin_photos/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Save the file
        $filepath = $uploadDir . $filename;
        if (file_put_contents($filepath, $imageData) === false) {
            throw new \RuntimeException('Failed to save image file');
        }

        return $filename;
    }
}
