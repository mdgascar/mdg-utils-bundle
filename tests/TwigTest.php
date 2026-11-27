<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Tests;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtils\Phone\PhoneNumber;
use Mdgascar\MdgUtilsBundle\Twig\MdgUtilsExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Environment;
use Twig\Error\RuntimeError;

final class TwigTest extends KernelTestCase
{
    private const int AMOUNT = 1500;
    private const float DECIMAL_AMOUNT = 1500.5;
    private const string E164 = '+261341234567';
    private const int WORDS_NUMBER = 21;
    private const int ORDINAL_NUMBER = 3;
    private const int TRADITIONAL_ORDINAL = 30_000_000;
    private const float WORDS_DECIMAL = 0.5;
    // 30 000 000: 'telopolo tapitrisa' in the standard system.
    private const int TRADITIONAL_NUMBER = 30_000_000;
    // A float this large has lost its decimals; text keeps them (core spec words rule 13).
    private const string EXACT_TEXT = '2000000000000000.9';
    private const string ISO_DATE = '2026-11-27';

    /**
     * @return iterable<string, array{string, mixed, string}>
     */
    public static function filters(): iterable
    {
        yield 'ariary from an int' => ['{{ v|ariary }}', self::AMOUNT, self::nbsp('Ar 1 500')];
        yield 'ariary from an Ariary' => ['{{ v|ariary }}', Ariary::fromFloat(self::DECIMAL_AMOUNT), self::nbsp('Ar 1 500,5')];
        yield 'phone from text' => ['{{ v|mg_phone }}', self::E164, '034 12 345 67'];
        yield 'phone with a format' => ["{{ v|mg_phone('international') }}", PhoneNumber::parse(self::E164), '+261 34 12 345 67'];
        yield 'words from an int' => ['{{ v|mg_spell }}', self::WORDS_NUMBER, 'iraika amby roapolo'];
        yield 'ordinal' => ['{{ v|mg_ordinal }}', self::ORDINAL_NUMBER, 'fahatelo'];
        yield 'ordinal of one' => ['{{ v|mg_ordinal }}', 1, 'voalohany'];
        yield 'ordinal in the traditional system' => ["{{ v|mg_ordinal('traditional') }}", self::TRADITIONAL_ORDINAL, 'fahatelo safatsiroa'];
        yield 'words from a float' => ['{{ v|mg_spell }}', self::WORDS_DECIMAL, 'aotra faingo dimy'];
        yield 'spell exact decimal text' => ['{{ v|mg_spell }}', self::EXACT_TEXT, 'roa tapitrisa lavitrisa faingo sivy'];
        yield 'words in the traditional system' => ["{{ v|mg_spell('traditional') }}", self::TRADITIONAL_NUMBER, 'telo safatsiroa'];
        yield 'date from a DateTime' => ['{{ v|mg_date }}', new \DateTimeImmutable(self::ISO_DATE), '27 Novambra 2026'];
        yield 'date from text with a style' => ["{{ v|mg_date('numeric') }}", self::ISO_DATE, '27/11/2026'];
        foreach (['ariary', 'mg_phone', 'mg_spell', 'mg_ordinal', 'mg_date'] as $filter) {
            yield "{$filter} prints nothing for null" => ["{{ v|{$filter} }}", null, ''];
        }
    }

    #[DataProvider('filters')]
    public function testEachFilterPrintsTheValueInMalagasy(string $template, mixed $value, string $expected): void
    {
        self::assertSame($expected, $this->render($this->twig(), $template, $value));
    }

    public function testAriaryFollowsTheConfig(): void
    {
        $twig = $this->twig(['ariary' => ['grouping' => 'dot', 'symbol_position' => 'after']]);

        self::assertSame(self::nbsp('1.500 Ar'), $this->render($twig, '{{ v|ariary }}', self::AMOUNT));
    }

    public function testAnInvalidValueThrows(): void
    {
        try {
            $this->render($this->twig(), '{{ v|mg_phone }}', '031 12 345 67');
            self::fail('Expected an exception');
        } catch (RuntimeError $error) {
            self::assertInstanceOf(MdgUtilsException::class, $error->getPrevious());
        }
    }

    public function testAnUnknownFormatNameThrows(): void
    {
        try {
            $this->render($this->twig(), "{{ v|mg_phone('fancy') }}", self::E164);
            self::fail('Expected an exception');
        } catch (RuntimeError $error) {
            self::assertInstanceOf(\ValueError::class, $error->getPrevious());
        }
    }

    public function testWithoutTwigTheExtensionIsNotRegistered(): void
    {
        self::assertFalse($this->boot([], minimal: true)->has(MdgUtilsExtension::class));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function twig(array $config = []): Environment
    {
        return $this->service($this->boot($config), Environment::class);
    }

    private function render(Environment $twig, string $template, mixed $value): string
    {
        return $twig->createTemplate($template)->render(['v' => $value]);
    }

    /**
     * The core writes U+00A0 (no-break space) between groups and after the symbol.
     */
    private static function nbsp(string $text): string
    {
        return str_replace(' ', "\u{00A0}", $text);
    }
}
