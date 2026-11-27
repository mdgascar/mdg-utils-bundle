<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * The value is a Malagasy mobile number written in any common way (`034 12 345 67`,
 * `+261 34 12 345 67`, ...) or a `PhoneNumber`. `null` and `''` pass: add `NotBlank` to
 * require a value.
 *
 * Example: `#[MalagasyPhone] public ?string $phone = null;`
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class MalagasyPhone extends Constraint
{
    public const string INVALID_ERROR = '8d3c165d-1e01-4c15-87b4-6ab430a55af2';

    protected const array ERROR_NAMES = [self::INVALID_ERROR => 'INVALID_MALAGASY_PHONE_ERROR'];

    public string $message = 'This value is not a valid Malagasy mobile number.';

    /**
     * @param list<string>|null $groups
     */
    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
        $this->message = $message ?? $this->message;
    }
}
