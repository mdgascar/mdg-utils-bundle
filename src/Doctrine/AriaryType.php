<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\MdgUtilsException;

/**
 * The `mg_ariary` column type: an `Ariary` stored in a DECIMAL column. Set `precision` and
 * `scale` on the mapping as for any decimal column.
 *
 * Example: `#[ORM\Column(type: 'mg_ariary', precision: 20, scale: 2)] private ?Ariary $total = null;`
 */
final class AriaryType extends Type
{
    public const string NAME = 'mg_ariary';
    // A DECIMAL column, as the driver returns it.
    private const string COLUMN_DIGITS = '/^[0-9]+(?:\.[0-9]+)?$/D';

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Ariary
    {
        try {
            return match (true) {
                $value === null => null,
                \is_int($value) => Ariary::fromInt($value),
                \is_float($value) => Ariary::fromFloat($value),
                // Most drivers return DECIMAL columns as text such as "1500.20": read it exactly.
                \is_string($value) && preg_match(self::COLUMN_DIGITS, $value) === 1 => Ariary::fromText($value),
                default => throw ValueNotConvertible::new($value, Ariary::class),
            };
        } catch (MdgUtilsException $exception) {
            throw ValueNotConvertible::new($value, Ariary::class, null, $exception);
        }
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof Ariary => $value->digits,
            default => throw InvalidType::new($value, self::NAME, ['null', Ariary::class]),
        };
    }
}
