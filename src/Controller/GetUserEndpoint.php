<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/user', name: 'api_user_get', methods: ['GET'])]
#[OA\Tag(name: 'Utilisateur')]
#[OA\Get(path: '/api/user', summary: 'Retourne le profil de l\'utilisateur connecté')]
#[OA\Response(response: 200, description: 'Profil utilisateur')]
class GetUserEndpoint extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'data' => [
                'id'        => (string) $user->getId(),
                'email'     => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
                'roles'     => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }
}
