<?php

namespace App\Controller\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Utilisateur')]
class PatchUserEndpoint extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/user', name: 'api_user_update', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/user',
        summary: 'Mettre à jour le prénom et le nom',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profil mis à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Cet endpoint nécessite une authentification JWT (ROLE_USER).');
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $firstName = isset($data['firstName']) ? trim($data['firstName']) : null;
        $lastName  = isset($data['lastName'])  ? trim($data['lastName'])  : null;

        $errors = [];

        if ($firstName !== null) {
            $violations = $this->validator->validate($firstName, [
                new Assert\NotBlank(message: 'Le prénom ne peut pas être vide.'),
                new Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'),
            ]);
            foreach ($violations as $v) {
                $errors['firstName'][] = $v->getMessage();
            }
        }

        if ($lastName !== null) {
            $violations = $this->validator->validate($lastName, [
                new Assert\NotBlank(message: 'Le nom ne peut pas être vide.'),
                new Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'),
            ]);
            foreach ($violations as $v) {
                $errors['lastName'][] = $v->getMessage();
            }
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        if ($firstName !== null) {
            $user->setFirstName($firstName);
        }
        if ($lastName !== null) {
            $user->setLastName($lastName);
        }

        $this->entityManager->flush();

        return $this->json([
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'email'     => $user->getEmail(),
        ]);
    }
}
