<?php

namespace App\Controller\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Utilisateur')]
class PostUserPasswordEndpoint extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/api/user/password', name: 'api_user_password', methods: ['POST'])]
    #[OA\Post(
        path: '/api/user/password',
        summary: 'Changer son mot de passe',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['currentPassword', 'newPassword'],
                properties: [
                    new OA\Property(property: 'currentPassword', type: 'string', format: 'password'),
                    new OA\Property(property: 'newPassword', type: 'string', format: 'password', minLength: 8),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mot de passe mis à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];
        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword     = $data['newPassword'] ?? '';

        // Vérifier le mot de passe actuel
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(
                ['errors' => ['currentPassword' => 'Mot de passe actuel incorrect.']],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Valider le nouveau mot de passe
        $violations = $this->validator->validate($newPassword, [
            new Assert\NotBlank(message: 'Le nouveau mot de passe est obligatoire.'),
            new Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
            new Assert\Regex(pattern: '/[A-Z]/', message: 'Le mot de passe doit contenir au moins une majuscule.'),
            new Assert\Regex(pattern: '/[0-9]/', message: 'Le mot de passe doit contenir au moins un chiffre.'),
        ]);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors['newPassword'] = $v->getMessage();
            }

            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->entityManager->flush();

        return $this->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }
}
