<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\File\FileRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\File\NullableFileRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class FileController extends AbstractTestController
{
    /**
     * Файл обязателен (NotNull на поле avatar).
     */
    #[Route('/file/upload', methods: ['POST'])]
    public function upload(
        FileRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'title' => $request->title,
                'avatar_name' => $request->avatar->getClientOriginalName(),
                'avatar_mime' => $request->avatar->getClientMimeType(),
            ],
        );
    }

    /**
     * Файл необязателен (nullable без NotNull).
     */
    #[Route('/file/upload-nullable', methods: ['POST'])]
    public function uploadNullable(
        NullableFileRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'title' => $request->title,
                'has_avatar' => $request->avatar !== null,
            ],
        );
    }
}
