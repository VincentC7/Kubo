<?php

namespace App\Controller;

use App\Dto\UserSettingsDto;
use App\Repository\UserSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/settings', name: 'api_settings_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Paramètres')]
#[OA\Patch(path: '/api/settings', summary: 'Met à jour les préférences utilisateur')]
#[OA\Response(response: 200, description: 'Préférences mises à jour')]
#[OA\Response(response: 400, description: 'Validation')]
class PatchSettingsEndpoint extends AbstractController
{
    private const VALID_DIETARY_PREFS = ['vegetarien', 'vegan', 'sans-gluten', 'sans-lactose', 'halal', 'casher'];
    private const VALID_VIEW_MODES    = ['week', 'list'];

    public function __invoke(
        Request $request,
        UserSettingsRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $settings = $repository->findOneByUser($user);

        if ($settings === null) {
            return $this->json(['error' => 'Paramètres non trouvés'], Response::HTTP_NOT_FOUND);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $errors = [];

        if (array_key_exists('portionsDefault', $data)) {
            $val = $data['portionsDefault'];
            if (!is_int($val) || $val < 1 || $val > 20) {
                $errors['portionsDefault'][] = 'Entier entre 1 et 20.';
            } else {
                $settings->setPortionsDefault($val);
            }
        }

        if (array_key_exists('mealsGoal', $data)) {
            $val = $data['mealsGoal'];
            if (!is_int($val) || $val < 1 || $val > 21) {
                $errors['mealsGoal'][] = 'Entier entre 1 et 21.';
            } else {
                $settings->setMealsGoal($val);
            }
        }

        if (array_key_exists('viewMode', $data)) {
            $val = $data['viewMode'];
            if (!in_array($val, self::VALID_VIEW_MODES, true)) {
                $errors['viewMode'][] = 'Doit être "week" ou "list".';
            } else {
                $settings->setViewMode($val);
            }
        }

        if (array_key_exists('dietaryPrefs', $data)) {
            $val = $data['dietaryPrefs'];
            if (!is_array($val)) {
                $errors['dietaryPrefs'][] = 'Doit être un tableau.';
            } else {
                $invalid = array_filter($val, fn ($v) => !in_array($v, self::VALID_DIETARY_PREFS, true));
                if (!empty($invalid)) {
                    $errors['dietaryPrefs'][] = 'Valeurs invalides : ' . implode(', ', $invalid);
                } else {
                    $settings->setDietaryPrefs($val);
                }
            }
        }

        if (array_key_exists('notifications', $data)) {
            $val = $data['notifications'];
            if (!is_array($val)) {
                $errors['notifications'][] = 'Doit être un objet.';
            } else {
                $notifs = $settings->getNotifications();
                foreach (['planningReminder', 'expiryAlert'] as $key) {
                    if (array_key_exists($key, $val)) {
                        if (!is_bool($val[$key])) {
                            $errors["notifications.$key"][] = 'Doit être un booléen.';
                        } else {
                            $notifs[$key] = $val[$key];
                        }
                    }
                }
                if (empty($errors)) {
                    $settings->setNotifications($notifs);
                }
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $settings->touch();
        $em->flush();

        return $this->json(['data' => UserSettingsDto::fromEntity($settings)]);
    }
}
