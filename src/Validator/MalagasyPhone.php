<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Validator;

use Mdgascar\MdgUtils\Phone\PhoneLineType;
use Symfony\Component\Validator\Constraint;

/**
 * The value is a Malagasy phone number written in any common way (`034 12 345 67`,
 * `+261 34 12 345 67`, `020 22 421 19`, ...) or a `PhoneNumber`. Give `type` to accept only
 * mobile or only fixed numbers, e.g. for SMS. `null` and `''` pass: add `NotBlank` to require a
 * value.
 *
 * Example: `#[MalagasyPhone(type: PhoneLineType::Mobile)] public ?string $phone = null;`
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class MalagasyPhone extends Constraint
{
    public const string INVALID_ERROR = '8d3c165d-1e01-4c15-87b4-6ab430a55af2';
    public const string WRONG_TYPE_ERROR = '5f0e3c7a-2b8d-4d61-9a3e-7c4b1e9f2a60';

    protected const array ERROR_NAMES = [
        self::INVALID_ERROR => 'INVALID_MALAGASY_PHONE_ERROR',
        self::WRONG_TYPE_ERROR => 'WRONG_MALAGASY_PHONE_TYPE_ERROR',
    ];

    public string $message = 'This value is not a valid Malagasy phone number.';
    public string $mobileMessage = 'This value is not a valid Malagasy mobile number.';
    public string $fixedMessage = 'This value is not a valid Malagasy fixed-line number.';

    /**
     * @param list<string>|null $groups
     */
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        public ?PhoneLineType $type = null,
    ) {
        parent::__construct(null, $groups, $payload);
        $this->message = $message ?? $this->message;
    }

    /**
     * The message for a number of the wrong kind, or any invalid one.
     */
    public function messageFor(?PhoneLineType $type): string
    {
        return match ($type) {
            PhoneLineType::Mobile => $this->mobileMessage,
            PhoneLineType::Fixed => $this->fixedMessage,
            null => $this->message,
        };
    }
}
