<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\NowDateTimePatcher;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\ToImmutableDateTimePatcher;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Свойство createdAt (DateTimeInterface) описано вне конструктора
 * и имеет патчер, возвращающий DateTime.
 *
 * Параметр конструктора createdAt сопоставляется с этим свойством по имени,
 * но имеет тип DateTimeImmutable и собственный патчер, который принимает на вход
 * результат первого патчера (DateTime) и преобразует его в DateTimeImmutable.
 *
 * Итоговое значение свойства должно быть DateTimeImmutable.
 */
#[ValidRequest]
class DoublePatcherRequest
{
    #[Assert\NotBlank]
    public string $title;

    #[BindRequestParam(patcher: NowDateTimePatcher::class)]
    #[Assert\NotNull]
    public DateTimeInterface $createdAt;

    public function __construct(
        string $title,

        #[BindRequestParam(patcher: ToImmutableDateTimePatcher::class)]
        DateTimeImmutable $createdAt,
    ) {
        $this->title = $title;
        $this->createdAt = $createdAt;
    }
}
