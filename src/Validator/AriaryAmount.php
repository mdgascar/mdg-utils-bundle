<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * The value is an Ariary amount: text such as `Ar 1 500` or `1 500,50` (read with the
 * `mdg_utils.ariary` config), a non-negative number, or an `Ariary`. `null` and `''` pass:
 * add `NotBlank` to require a value.
 *
 * Example: `#[AriaryAmount] public ?string $total = null;`
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class AriaryAmount extends Constraint
{
    public const string INVALID_ERROR = '37aa2558-4ea5-47ea-890d-1da7c5e7773a';

    protected const array ERROR_NAMES = [self::INVALID_ERROR => 'INVALID_ARIARY_AMOUNT_ERROR'];

    public string $message = 'This value is not a valid Ariary amount.';

    /**
     * @param list<string>|null $groups
     */
    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
        $this->message = $message ?? $this->message;
    }
}
