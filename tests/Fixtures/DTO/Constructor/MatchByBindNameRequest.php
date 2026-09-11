<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Имя параметра конструктора отличается от имени свойства.
 * Поле запроса определяется свойством (title), а параметр конструктора
 * привязывается к нему через BindRequestParam(name: 'title').
 */
#[ValidRequest]
class MatchByBindNameRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(
        #[BindRequestParam(name: 'title')]
        string $heading,
    ) {
        $this->title = $heading;
    }
}
