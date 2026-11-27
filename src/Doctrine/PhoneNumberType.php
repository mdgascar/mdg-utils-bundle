<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Phone\PhoneNumber;

/**
 * The `mg_phone` column type: a `PhoneNumber` stored as E.164 text (`+261341234567`).
 *
 * Example: `#[ORM\Column(type: 'mg_phone')] private ?PhoneNumber $phone = null;`
 */
final class PhoneNumberType extends Type
{
    public const string NAME = 'mg_phone';

    // "+" and at most 15 digits (ITU-T E.164).
    private const int E164_MAX_LENGTH = 16;

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column + ['length' => self::E164_MAX_LENGTH]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PhoneNumber
    {
        if ($value === null) {
            return null;
        }
        if (!\is_string($value)) {
            throw ValueNotConvertible::new($value, PhoneNumber::class);
        }
        try {
            return PhoneNumber::parse($value);
        } catch (MdgUtilsException $exception) {
            throw ValueNotConvertible::new($value, PhoneNumber::class, null, $exception);
        }
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof PhoneNumber => $value->e164,
            default => throw InvalidType::new($value, self::NAME, ['null', PhoneNumber::class]),
        };
    }
}
