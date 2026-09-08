<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\AbstractInTypeRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\CardPayment;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\CryptoPayment;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\EmailNotification;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\Notification;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\OptionalSubclassRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\PaymentMethodInterface;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\PrimitiveInTypeRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveByAbstractRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveByInterfaceRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveByMixedRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveByObjectRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveBySubclassGroupsRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveBySubclassRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveByUntypedRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\ResolveObjectOrIdRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\SmsNotification;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups\UnresolvableAbstractRequest;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class TypeGroupsController extends AbstractTestController
{
    // =========================================================================
    // Тип свойства — интерфейс
    // =========================================================================

    #[Route('/type-groups/interface', methods: ['POST'])]
    public function byInterface(
        #[ValidRequest(groups: ['Default'])]
        ResolveByInterfaceRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describePayment($request->method);
    }

    // =========================================================================
    // Тип свойства — абстрактный класс
    // =========================================================================

    #[Route('/type-groups/abstract', methods: ['POST'])]
    public function byAbstract(
        #[ValidRequest(groups: ['Default'])]
        ResolveByAbstractRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describePayment($request->method);
    }

    // =========================================================================
    // Тип свойства — обычный класс, но нужен наследник
    // =========================================================================

    #[Route('/type-groups/subclass', methods: ['POST'])]
    public function bySubclass(
        #[ValidRequest(groups: ['Default'])]
        ResolveBySubclassRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeNotification($request->notification);
    }

    // =========================================================================
    // Тип свойства — object
    // =========================================================================

    #[Route('/type-groups/object', methods: ['POST'])]
    public function byObject(
        #[ValidRequest(groups: ['Default'])]
        ResolveByObjectRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describePayment($request->method);
    }

    // =========================================================================
    // Тип свойства — mixed
    // =========================================================================

    #[Route('/type-groups/mixed', methods: ['POST'])]
    public function byMixed(
        #[ValidRequest(groups: ['Default'])]
        ResolveByMixedRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describePayment($request->method);
    }

    // =========================================================================
    // Свойство без типа
    // =========================================================================

    #[Route('/type-groups/untyped', methods: ['POST'])]
    public function byUntyped(
        #[ValidRequest(groups: ['Default'])]
        ResolveByUntypedRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describePayment($request->method);
    }

    // =========================================================================
    // Два Assert\Type с группами: обычный класс с подклассами
    // =========================================================================

    #[Route('/type-groups/subclass/email', methods: ['POST'])]
    public function subclassEmail(
        #[ValidRequest(groups: ['Default', 'Email'])]
        ResolveBySubclassGroupsRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeNotification($request->notification);
    }

    #[Route('/type-groups/subclass/sms', methods: ['POST'])]
    public function subclassSms(
        #[ValidRequest(groups: ['Default', 'Sms'])]
        ResolveBySubclassGroupsRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeNotification($request->notification);
    }

    // =========================================================================
    // Два Assert\Type с группами на mixed: объект или примитивный id
    // =========================================================================

    #[Route('/type-groups/object-or-id/full', methods: ['POST'])]
    public function objectOrIdFull(
        #[ValidRequest(groups: ['Default', 'Full'])]
        ResolveObjectOrIdRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse([
            'type' => get_debug_type($request->method),
            'method' => $request->method instanceof CardPayment
                ? ['class' => 'CardPayment', 'cardNumber' => $request->method->cardNumber]
                : $request->method,
        ]);
    }

    #[Route('/type-groups/object-or-id/ref', methods: ['POST'])]
    public function objectOrIdRef(
        #[ValidRequest(groups: ['Default', 'Ref'])]
        ResolveObjectOrIdRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse([
            'type' => get_debug_type($request->method),
            'method' => $request->method,
        ]);
    }

    // =========================================================================
    // Кейс A: конкретный класс в тайпхинте + один Assert\Type(наследник) с группой.
    // С группой Email берётся наследник, без неё — класс из тайпхинта.
    // =========================================================================

    #[Route('/type-groups/optional-subclass/with-group', methods: ['POST'])]
    public function optionalSubclassWithGroup(
        #[ValidRequest(groups: ['Default', 'Email'])]
        OptionalSubclassRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeNotification($request->notification);
    }

    #[Route('/type-groups/optional-subclass/without-group', methods: ['POST'])]
    public function optionalSubclassWithoutGroup(
        #[ValidRequest(groups: ['Default'])]
        OptionalSubclassRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeNotification($request->notification);
    }

    // =========================================================================
    // Кейс B: тайпхинт — абстрактный класс, Assert\Type только для группы Card,
    // а запрос приходит с группой Default. Ожидается ValidRequestException.
    // =========================================================================

    #[Route('/type-groups/unresolvable-abstract', methods: ['POST'])]
    public function unresolvableAbstract(
        #[ValidRequest(groups: ['Default'])]
        UnresolvableAbstractRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describePayment($request->method);
    }

    // =========================================================================
    // Кейс C: тайпхинт — интерфейс, Assert\Type — абстрактный класс.
    // Ожидается ValidRequestException.
    // =========================================================================

    #[Route('/type-groups/abstract-in-type', methods: ['POST'])]
    public function abstractInType(
        #[ValidRequest(groups: ['Default'])]
        AbstractInTypeRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describePayment($request->method);
    }

    // =========================================================================
    // Кейс D: тайпхинт — интерфейс, Assert\Type — примитив.
    // Ожидается ValidRequestException.
    // =========================================================================

    #[Route('/type-groups/primitive-in-type', methods: ['POST'])]
    public function primitiveInType(
        #[ValidRequest(groups: ['Default'])]
        PrimitiveInTypeRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['type' => get_debug_type($request->method)]);
    }

    private function describePayment(object $method): JsonResponse
    {
        $data = [
            'class' => new ReflectionClass($method)->getShortName(),
            'label' => $method instanceof PaymentMethodInterface ? $method->getLabel() : null,
        ];

        if ($method instanceof CardPayment) {
            $data['currency'] = $method->currency;
            $data['cardNumber'] = $method->cardNumber;
        }

        if ($method instanceof CryptoPayment) {
            $data['currency'] = $method->currency;
            $data['walletAddress'] = $method->walletAddress;
        }

        return new JsonResponse($data);
    }

    private function describeNotification(Notification $notification): JsonResponse
    {
        $data = [
            'class' => new ReflectionClass($notification)->getShortName(),
            'subject' => $notification->subject,
        ];

        if ($notification instanceof EmailNotification) {
            $data['to'] = $notification->to;
        }

        if ($notification instanceof SmsNotification) {
            $data['phone'] = $notification->phone;
        }

        return new JsonResponse($data);
    }
}
