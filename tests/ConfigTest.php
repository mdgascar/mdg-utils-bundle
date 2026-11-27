<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Tests;

use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtilsBundle\AriaryOptions;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class ConfigTest extends KernelTestCase
{
    public function testWithoutConfigTheCoreDefaultsApply(): void
    {
        $options = $this->service($this->boot(), AriaryOptions::class);

        self::assertSame(Grouping::Space, $options->grouping);
        self::assertSame(SymbolPosition::Before, $options->symbolPosition);
        self::assertSame(DecimalSeparator::Comma, $options->decimal);
    }

    public function testEachOptionCanBeSet(): void
    {
        $options = $this->service(
            $this->boot(['ariary' => ['grouping' => 'comma', 'symbol_position' => 'after', 'decimal' => 'dot']]),
            AriaryOptions::class,
        );

        self::assertSame(Grouping::Comma, $options->grouping);
        self::assertSame(SymbolPosition::After, $options->symbolPosition);
        self::assertSame(DecimalSeparator::Dot, $options->decimal);
    }

    public function testAnUnknownValueFailsWhenTheContainerIsBuilt(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "tab" is not allowed for path "mdg_utils.ariary.grouping"');

        $this->boot(['ariary' => ['grouping' => 'tab']]);
    }

    public function testTheSameGroupingAndDecimalFailsWhenTheContainerIsBuilt(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('grouping and decimal cannot be the same character');

        $this->boot(['ariary' => ['grouping' => 'dot', 'decimal' => 'dot']]);
    }

    public function testTheOptionsObjectAlsoRejectsTheSameGroupingAndDecimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mdg_utils.ariary: grouping and decimal cannot both be "comma".');

        new AriaryOptions(Grouping::Comma, SymbolPosition::Before, DecimalSeparator::Comma);
    }
}
