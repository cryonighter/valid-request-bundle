<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Параметр конструктора и свойство совпадают по имени — сопоставляются автоматически.
 */
#[ValidRequest]
class MatchByNameRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
}
