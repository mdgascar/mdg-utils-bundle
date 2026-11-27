<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Tests;

use Mdgascar\MdgUtils\Ariary\Ariary;
use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtils\ErrorCode;
use Mdgascar\MdgUtils\MdgUtilsException;
use Mdgascar\MdgUtilsBundle\AriaryOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AriaryOptionsTest extends TestCase
{
    private const int AMOUNT = 1500;
    private const float DECIMAL_AMOUNT = 1500.5;

    public function testAnEmptyConfigKeepsTheCoreDefaults(): void
    {
        $options = AriaryOptions::fromConfig([]);

        self::assertSame(Grouping::Space, $options->grouping);
        self::assertSame(SymbolPosition::Before, $options->symbolPosition);
        self::assertSame(DecimalSeparator::Comma, $options->decimal);
    }

    public function testAnUnknownValueNamesTheKeyAndTheChoices(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mdg_utils.ariary.grouping must be one of "space", "dot", "comma", got \'tab\'.');

        AriaryOptions::fromConfig([AriaryOptions::GROUPING => 'tab']);
    }

    public function testANonTextValueIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mdg_utils.ariary.decimal must be one of "comma", "dot", got true.');

        AriaryOptions::fromConfig([AriaryOptions::DECIMAL => true]);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function readableAmounts(): iterable
    {
        yield 'an Ariary' => [Ariary::fromInt(self::AMOUNT)];
        yield 'an int' => [self::AMOUNT];
        yield 'a float' => [(float) self::AMOUNT];
        yield 'Malagasy text with spaces' => ['Ar 1 500'];
        yield 'Malagasy text with a dot' => ['1.500 Ar'];
    }

    #[DataProvider('readableAmounts')]
    public function testReadAcceptsAnAmountInEveryFormAnAppHasIt(mixed $value): void
    {
        self::assertEquals(self::AMOUNT, (new AriaryOptions())->read($value)->amount);
    }

    public function testEnglishStyleOptionsWriteAndReadEnglishStyleText(): void
    {
        $options = new AriaryOptions(Grouping::Comma, SymbolPosition::After, DecimalSeparator::Dot);

        self::assertSame("1,500.5\u{00A0}Ar", $options->format(Ariary::fromFloat(self::DECIMAL_AMOUNT)));
        self::assertSame(self::DECIMAL_AMOUNT, $options->read('1,500.5')->amount);
        self::assertNull($options->tryRead('1 500'));
    }

    public function testReadRejectsOtherTypesWithInvalidType(): void
    {
        try {
            (new AriaryOptions())->read([self::AMOUNT]);
            self::fail('Expected MdgUtilsException');
        } catch (MdgUtilsException $exception) {
            self::assertSame(ErrorCode::InvalidType, $exception->errorCode);
        }
    }

    public function testTryReadReturnsNullForInvalidInput(): void
    {
        $options = new AriaryOptions();

        self::assertNull($options->tryRead(-1));
        self::assertNull($options->tryRead('abc'));
    }
}
