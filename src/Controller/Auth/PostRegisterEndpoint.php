<?php

namespace App\Controller\Auth;

use App\Dto\RegisterDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[OA\Tag(name: 'Authentification')]
class PostRegisterEndpoint extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
        #[Target('registerApi')]
        private readonly RateLimiterFactory $registerApiLimiter,
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Créer un nouveau compte utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'MonMotDePasse1', minLength: 8),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé avec succès'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 409, description: 'Email déjà utilisé'),
            new OA\Response(response: 429, description: 'Trop de tentatives — réessayez dans 1 heure'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        // Rate limiting par IP : 3 créations/heure
        $limiter = $this->registerApiLimiter->create($request->getClientIp());
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de tentatives de création de compte. Réessayez dans 1 heure.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['X-RateLimit-Remaining' => $limit->getRemainingTokens()],
            );
        }

        // Désérialisation
        $data = json_decode($request->getContent(), true);

        $dto = new RegisterDto();
        $dto->firstName = trim($data['firstName'] ?? '');
        $dto->lastName = trim($data['lastName'] ?? '');
        $dto->email = strtolower(trim($data['email'] ?? ''));
        $dto->password = $data['password'] ?? '';

        // Validation
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Vérification unicité email
        if ($this->userRepository->findOneBy(['email' => $dto->email]) !== null) {
            return $this->json(
                ['error' => 'Un compte existe déjà avec cet email.'],
                Response::HTTP_CONFLICT,
            );
        }

        // Création du user
        $user = new User();
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setEmail($dto->email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $dto->password),
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(
            ['message' => 'Compte créé avec succès.'],
            Response::HTTP_CREATED,
        );
    }
}
