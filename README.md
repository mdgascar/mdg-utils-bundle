# mdg-utils-bundle

[![CI](https://github.com/mdgascar/mdg-utils-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/mdgascar/mdg-utils-bundle/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/mdgascar/mdg-utils-bundle)](https://packagist.org/packages/mdgascar/mdg-utils-bundle)

Symfony validation constraints, Twig filters and Doctrine types for Malagasy mobile phone
numbers, Ariary amounts, number words and dates. Symfony 7.4 LTS and 8, PHP 8.3+
(Symfony 8 needs PHP 8.4).

All the parsing and formatting comes from [mdgascar/mdg-utils](https://github.com/mdgascar/mdg-utils-php),
which follows the shared [mdg-utils spec](https://github.com/mdgascar/mdg-utils-spec). This
bundle only wires it into Symfony. Docs and playground:
[mdgascar.github.io/mdg-utils-docs](https://mdgascar.github.io/mdg-utils-docs/).

```sh
composer require mdgascar/mdg-utils-bundle
```

With Symfony Flex the bundle is enabled for you. Otherwise add it to `config/bundles.php`:

```php
Mdgascar\MdgUtilsBundle\MdgUtilsBundle::class => ['all' => true],
```

Each part needs its library and is skipped without it: the constraints need
`symfony/validator`, the filters `twig/twig`, and the types DoctrineBundle (DBAL 4).

## Validation

```php
use Mdgascar\MdgUtilsBundle\Validator\AriaryAmount;
use Mdgascar\MdgUtilsBundle\Validator\MalagasyPhone;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderInput
{
    #[Assert\NotBlank, MalagasyPhone]
    public ?string $phone = null;

    #[Assert\NotBlank, AriaryAmount]
    public ?string $total = null;
}
```

| Constraint | Passes for | Fails for |
| --- | --- | --- |
| `MalagasyPhone` | `034 12 345 67`, `+261 34 12 345 67`, a `PhoneNumber` | `031 12 345 67` (no operator), anything not text |
| `AriaryAmount` | `Ar 1 500`, `1.500 Ar`, `1 500,50`, `1500` from JSON, an `Ariary` | `-1`, `1.5` (a dot is not a decimal point by default), `abc` |

`null` and `''` pass, as with Symfony's own constraints: add `NotBlank` to require a value.
Messages come in English and French (domain `validators`) and can be changed per use with
`message:`, e.g. `#[MalagasyPhone(message: 'Use a Malagasy mobile number.')]`. The violation
codes are `MalagasyPhone::INVALID_ERROR` and `AriaryAmount::INVALID_ERROR`.

## Doctrine types

The bundle registers two column types with DoctrineBundle.

```php
use Doctrine\ORM\Mapping as ORM;
use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Phone\PhoneNumber;

#[ORM\Entity]
class Order
{
    #[ORM\Column(type: 'mg_phone')] // VARCHAR(16), stored as E.164: +261341234567
    public ?PhoneNumber $phone = null;

    #[ORM\Column(type: 'mg_ariary', precision: 20, scale: 2)] // DECIMAL
    public ?Ariary $total = null;
}

$order->phone = PhoneNumber::parse('034 12 345 67');
$order->total = Ariary::fromInt(1500);
```

The entity holds the value objects, so parse input once (or validate it first, then
parse). A stored value that is not a valid number or amount fails with Doctrine's
`ValueNotConvertible`.

## Twig

```twig
{{ order.total|ariary }}                    {# Ar 1 500 #}
{{ order.phone|mg_phone }}                  {# 034 12 345 67 #}
{{ order.phone|mg_phone('international') }} {# +261 34 12 345 67 #}
{{ 2026|mg_words }}                         {# enina amby roapolo sy roa arivo #}
{{ 30000000|mg_words('traditional') }}      {# telo safatsiroa #}
{{ order.createdAt|mg_date }}               {# 27 Novambra 2026 #}
{{ order.createdAt|mg_date('numeric') }}    {# 27/11/2026 #}
```

Each filter takes the typed value (`Ariary`, `PhoneNumber`, any `DateTimeInterface`) or
the raw one (an int, phone text, an ISO date such as `2026-11-27`), and prints nothing for
`null`. The format names are the `PhoneNumberFormat`, `DateStyle` and `NumberSystem` values
(`e164`, `international`, `national`; `short`, `medium`, `long`, `full`, `numeric`;
`standard`, `traditional`); an
unknown name throws `ValueError`. Invalid input throws `MdgUtilsException`.

## Configuration

Only Ariary amounts have options:

```yaml
# config/packages/mdg_utils.yaml
mdg_utils:
    ariary:
        grouping: space        # space (Ar 1 500), dot (Ar 1.500) or comma (Ar 1,500)
        symbol_position: before # before (Ar 1 500) or after (1 500 Ar)
        decimal: comma         # comma (Ar 1 500,50) or dot
```

The `ariary` filter writes with these options. `AriaryAmount` reads every Malagasy
separator (space or dot) with the configured decimal separator; with `grouping: comma` it
reads English-style text such as `1,500.50` instead. An unknown value, or the same
character for grouping and decimal, fails when the container is built.

## Versioning

This bundle has its own version number, following Semantic Versioning, and it says which
`mdgascar/mdg-utils` versions it works with. Changes are listed in [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and the organisation
[contributing guide](https://github.com/mdgascar/.github/blob/main/CONTRIBUTING.md).

## License

[MIT](LICENSE)
