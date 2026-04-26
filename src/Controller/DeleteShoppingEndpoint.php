<?php

namespace App\Controller;

use App\Repository\ShoppingListRepository;
use App\Service\WeekHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/shopping', name: 'api_shopping_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Shopping')]
#[OA\Delete(path: '/api/shopping', summary: 'Vide entièrement la liste de courses de la semaine')]
#[OA\Parameter(name: 'week', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '2026-W18'))]
#[OA\Response(response: 204, description: 'Vidée')]
#[OA\Response(response: 400, description: 'Paramètre week manquant ou invalide')]
class DeleteShoppingEndpoint extends AbstractController
{
    public function __invoke(
        Request $request,
        ShoppingListRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $week = $request->query->get('week');

        if (empty($week) || !WeekHelper::validate((string) $week)) {
            return $this->json(
                ['error' => 'Paramètre week manquant ou invalide. Attendu : YYYY-Www'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $list = $repository->findOneByUserAndWeek($user, (string) $week);
        if ($list !== null) {
            $em->remove($list);
            $em->flush();
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
