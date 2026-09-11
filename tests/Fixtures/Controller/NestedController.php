<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Nested\ContactDTO;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Nested\ProfileRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class NestedController extends AbstractTestController
{
    /**
     * Вложенные объекты и массивы объектов.
     * Violations передаются в контроллер без исключения.
     */
    #[Route('/nested/profile', methods: ['POST'])]
    public function profile(
        ProfileRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'username' => $request->username,
                'settings' => $request->settings !== null ? [
                    'items_per_page' => $request->settings->itemsPerPage,
                    'theme' => $request->settings->theme,
                ] : null,
                'contacts' => array_map(
                    fn(ContactDTO $contact): array => [
                        'type' => $contact->type,
                        'value' => $contact->value,
                    ],
                    $request->contacts,
                ),
            ],
        );
    }
}
