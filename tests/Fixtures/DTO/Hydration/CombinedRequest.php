<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

/**
 * Комбинированная сборка: каждое свойство гидрируется своей стратегией,
 * имена свойств, параметров конструктора и полей запроса совпадают,
 * поэтому #[BindRequestParam] не требуется.
 *
 * - viaConstructor        — через параметр конструктора;
 * - viaConstructorDefault — через параметр конструктора (со значением по умолчанию);
 * - viaSetter             — через публичный сеттер (нет в конструкторе);
 * - viaPublic             — через публичное свойство (нет в конструкторе, нет сеттера);
 * - viaReflection         — через рефлексию (приватное, нет в конструкторе, нет сеттера).
 */
#[ValidRequest]
class CombinedRequest
{
    private string $viaConstructor;

    private string $viaConstructorDefault;

    private string $viaSetter;

    public string $viaPublic;

    private readonly string $viaReflection;

    private string $viaDefault = 'default-value';

    public function __construct( string $viaConstructor, string $viaConstructorDefault = 'constructor-default-value') {
        $this->viaConstructor = $viaConstructor;
        $this->viaConstructorDefault = $viaConstructorDefault;
    }

    public function setViaSetter(string $value): void
    {
        $this->viaSetter = $value;
    }

    public function getViaConstructor(): string
    {
        return $this->viaConstructor;
    }

    public function getViaConstructorNullable(): ?string
    {
        return $this->viaConstructorNullable;
    }

    public function getViaConstructorDefault(): string
    {
        return $this->viaConstructorDefault;
    }

    public function getViaSetter(): string
    {
        return $this->viaSetter;
    }

    public function getViaReflection(): string
    {
        return $this->viaReflection;
    }

    public function getViaDefault(): string
    {
        return $this->viaDefault;
    }
}
