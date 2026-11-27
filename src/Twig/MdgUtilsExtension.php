<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Twig;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Date\DateStyle;
use Mdgascar\MdgUtils\Date\MalagasyDate;
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtils\Phone\PhoneNumberFormat;
use Mdgascar\MdgUtils\Words\NumberWords;
use Mdgascar\MdgUtilsBundle\AriaryOptions;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * The `ariary`, `mg_phone`, `mg_words` and `mg_date` filters. Each takes the typed value or
 * the raw one an app usually has at hand, and prints `''` for `null`.
 *
 * Invalid input throws `MdgUtilsException`, like the core: a wrong value in a template is a
 * bug to see, not text to hide.
 */
final class MdgUtilsExtension extends AbstractExtension
{
    private const string NOTHING = '';

    public function __construct(
        private readonly AriaryOptions $ariary,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('ariary', $this->ariary(...)),
            new TwigFilter('mg_phone', $this->phone(...)),
            new TwigFilter('mg_words', $this->words(...)),
            new TwigFilter('mg_date', $this->date(...)),
        ];
    }

    /**
     * `{{ order.total|ariary }}` prints `Ar 1 500` with the default config.
     */
    public function ariary(Ariary|int|float|null $amount): string
    {
        return $amount === null ? self::NOTHING : $this->ariary->format($this->ariary->read($amount));
    }

    /**
     * `{{ user.phone|mg_phone }}` prints `034 12 345 67`; `|mg_phone('international')` prints
     * `+261 34 12 345 67`.
     */
    public function phone(PhoneNumber|string|null $phone, PhoneNumberFormat|string $format = PhoneNumberFormat::National): string
    {
        if ($phone === null) {
            return self::NOTHING;
        }
        $format = \is_string($format) ? PhoneNumberFormat::from($format) : $format;

        return ($phone instanceof PhoneNumber ? $phone : PhoneNumber::parse($phone))->format($format);
    }

    /**
     * `{{ 21|mg_words }}` prints `iraika amby roapolo`.
     */
    public function words(int|float|null $number): string
    {
        return match (true) {
            $number === null => self::NOTHING,
            \is_int($number) => NumberWords::fromInt($number),
            default => NumberWords::fromFloat($number),
        };
    }

    /**
     * `{{ order.createdAt|mg_date }}` prints `27 Novambra 2026`; `|mg_date('numeric')` prints
     * `27/11/2026`. Text must be an ISO date such as `2026-11-27`.
     */
    public function date(\DateTimeInterface|string|null $date, DateStyle|string $style = DateStyle::DEFAULT): string
    {
        $style = \is_string($style) ? DateStyle::from($style) : $style;

        return match (true) {
            $date === null => self::NOTHING,
            $date instanceof \DateTimeInterface => MalagasyDate::fromDateTime($date)->format($style),
            default => MalagasyDate::fromString($date)->format($style),
        };
    }
}
