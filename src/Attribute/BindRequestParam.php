<?php

namespace Cryonighter\ValidRequestBundle\Attribute;

use Attribute;
use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Binds a parameter or property to a specific key in the request payload.
 *
 * This attribute is used to map a constructor parameter or class property
 * to a specific field in the incoming request payload. It is particularly
 * useful in scenarios where automatic deserialization is performed, allowing
 * for precise control over which payload fields correspond to which class
 * properties or constructor parameters.
 *
 * Usage:
 * ```php
 * use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
 * use Symfony\Component\Validator\Constraints as Assert;
 *
 * class ExampleRequest
 * {
 *     public function __construct(
 *         #[BindRequestParam('username')]
 *         #[Assert\NotBlank]
 *         public string $user,
 *
 *         #[BindRequestParam('email')]
 *         #[Assert\Email]
 *         public string $emailAddress,
 *     ) {}
 * }
 * ```
 *
 * In this example, the `user` parameter is bound to the `username` field in the
 * request payload, and the `emailAddress` parameter is bound to the `email` field.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class BindRequestParam
{
    private(set) ValidationGroups $validationGroups;

    /**
     * @param string|null $name                            The name of the field in the request payload
     * @param RequestValueSource|null $source              The source of the parameter
     * @param string|GroupSequence|array|null $groups      The validation groups to apply to the parameter
     * @param class-string<PatcherInterface>|null $patcher The patcher to apply to the parameter
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?RequestValueSource $source = null,
        public readonly string|GroupSequence|array|null $groups = null,
        public readonly ?string $patcher = null,
    ) {
        if ($this->patcher !== null && !is_a($this->patcher, PatcherInterface::class, true)) {
            throw new InvalidArgumentException('Patcher class "' . $this->patcher . '" must implement PatcherInterface');
        }

        $this->validationGroups = new ValidationGroups($this->groups);
    }
}
