<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ArticleRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\PatcherObjectRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class PatcherController extends AbstractTestController
{
    /**
     * Патчер на уровне свойства: slug генерируется из title если не передан.
     */
    #[Route('/patcher/article', methods: ['POST'])]
    public function article(
        ArticleRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'title' => $request->title,
                'slug' => $request->slug,
            ],
        );
    }

    /**
     * Патчер на уровне объекта: createdAt подставляется патчером если не передан.
     */
    #[Route('/patcher/object', methods: ['POST'])]
    public function object(
        PatcherObjectRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'name' => $request->name,
                'created_at' => $request->createdAt,
            ],
        );
    }
}
