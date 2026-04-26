<?php

namespace App\Controller;

use App\Dto\UserSettingsDto;
use App\Repository\UserSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/settings', name: 'api_settings_get', methods: ['GET'])]
#[OA\Tag(name: 'Paramètres')]
#[OA\Get(path: '/api/settings', summary: 'Retourne les préférences de l\'utilisateur')]
#[OA\Response(response: 200, description: 'Préférences utilisateur')]
class GetSettingsEndpoint extends AbstractController
{
    public function __invoke(UserSettingsRepository $repository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $settings = $repository->findOneByUser($user);

        if ($settings === null) {
            return $this->json(['error' => 'Paramètres non trouvés'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['data' => UserSettingsDto::fromEntity($settings)]);
    }
}
