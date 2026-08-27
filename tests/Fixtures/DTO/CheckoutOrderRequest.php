<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest(groups: ['Default', 'Create'])]
class CheckoutOrderRequest
{
    public function __construct(
        // id заказа приходит из route-атрибутов и только при обновлении
        #[BindRequestParam(source: RequestValueSource::attributes, groups: ['Update'])]
        #[Assert\Positive]
        public ?int $orderId,

        // имя поля в запросе отличается от имени свойства
        #[BindRequestParam(name: 'customer_email')]
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        // вложенный объект
        #[Assert\Valid]
        public DeliveryAddressRequest $deliveryAddress,

        // массив объектов
        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\Type(OrderLineRequest::class),
        ])]
        #[Assert\Valid]
        public array $lines,

        // nullable объект — при отсутствии данных станет null
        #[Assert\Valid]
        public ?GiftWrapRequest $giftWrap,

        // файл через multipart
        #[BindRequestParam(source: RequestValueSource::files)]
        #[Assert\File(maxSize: '256k', mimeTypes: ['application/pdf'])]
        public ?UploadedFile $invoice,

        // значения по умолчанию — поля становятся необязательными
        #[Assert\Choice(choices: ['standard', 'express'])]
        public string $shippingMethod = 'standard',

        #[Assert\PositiveOrZero]
        public int $bonusPoints = 0,

        #[Assert\Length(max: 500)]
        public ?string $comment = null,
    ) {
    }
}
