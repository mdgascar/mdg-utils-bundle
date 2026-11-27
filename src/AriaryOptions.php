<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;

/**
 * How the app writes and reads Ariary amounts: the `mdg_utils.ariary` config, as a service.
 *
 * Writing follows the configured grouping, symbol position and decimal separator. Reading
 * accepts every Malagasy separator (space or dot) unless the app writes English-style
 * amounts (`grouping: comma`), in which case it reads exactly that convention.
 */
final readonly class AriaryOptions
{
    public function __construct(
        public Grouping $grouping = Grouping::DEFAULT,
        public SymbolPosition $symbolPosition = SymbolPosition::Before,
        public DecimalSeparator $decimal = DecimalSeparator::DEFAULT,
    ) {
        if ($grouping->value === $decimal->value) {
            throw new \InvalidArgumentException(\sprintf(
                'mdg_utils.ariary: grouping and decimal cannot both be "%s".',
                $grouping->value,
            ));
        }
    }

    /**
     * Example: with the default config, `format(Ariary::fromInt(1500))` returns `'Ar 1 500'`.
     */
    public function format(Ariary $amount): string
    {
        return $amount->format($this->grouping, $this->symbolPosition, $this->decimal);
    }

    /**
     * Turns form or JSON input into an amount: an `Ariary`, a non-negative int or float, or
     * text such as `'Ar 1 500,50'` read with the configured convention.
     *
     * @throws MdgUtilsException with the core error code, or `ErrorCode::InvalidType` for any
     *                           other type
     */
    public function read(mixed $value): Ariary
    {
        return match (true) {
            $value instanceof Ariary => $value,
            \is_int($value) => Ariary::fromInt($value),
            \is_float($value) => Ariary::fromFloat($value),
            \is_string($value) => Ariary::parse($value, $this->readingGrouping(), $this->decimal),
            default => throw new MdgUtilsException(
                ErrorCode::InvalidType,
                \sprintf('An Ariary amount must be an Ariary, int, float or string, got %s.', get_debug_type($value)),
            ),
        };
    }

    /**
     * Like `read()`, but returns `null` instead of throwing.
     */
    public function tryRead(mixed $value): ?Ariary
    {
        try {
            return $this->read($value);
        } catch (MdgUtilsException) {
            return null;
        }
    }

    private function readingGrouping(): ?Grouping
    {
        return $this->grouping === Grouping::Comma ? Grouping::Comma : null;
    }
}
