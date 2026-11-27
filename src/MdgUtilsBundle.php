<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle;

use Mdgascar\MdgUtils\Ariary\DecimalSeparator;
use Mdgascar\MdgUtils\Ariary\Grouping;
use Mdgascar\MdgUtils\Ariary\SymbolPosition;
use Mdgascar\MdgUtilsBundle\Doctrine\AriaryType;
use Mdgascar\MdgUtilsBundle\Doctrine\PhoneNumberType;
use Mdgascar\MdgUtilsBundle\Twig\MdgUtilsExtension;
use Mdgascar\MdgUtilsBundle\Validator\AriaryAmountValidator;
use Mdgascar\MdgUtilsBundle\Validator\MalagasyPhoneValidator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Validator\ConstraintValidator;
use Twig\Extension\AbstractExtension;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Registers what the app has the libraries for: the validators with symfony/validator, the
 * Twig filters with twig/twig, and the Doctrine types when DoctrineBundle is enabled.
 */
final class MdgUtilsBundle extends AbstractBundle
{
    private const string DOCTRINE = 'doctrine';
    private const string ARIARY = 'ariary';
    private const string GROUPING = 'grouping';
    private const string SYMBOL_POSITION = 'symbol_position';
    private const string DECIMAL = 'decimal';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode(self::ARIARY)
                    ->info('How the ariary filter, the AriaryAmount constraint and the mg_ariary type write and read amounts.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode(self::GROUPING)
                            ->info('Thousands separator: space (Ar 1 500), dot (Ar 1.500) or comma (Ar 1,500, English style; amounts are then read in that style only).')
                            ->enumFqcn(Grouping::class)
                            ->defaultValue(Grouping::DEFAULT)
                        ->end()
                        ->enumNode(self::SYMBOL_POSITION)
                            ->info('before (Ar 1 500) or after (1 500 Ar).')
                            ->enumFqcn(SymbolPosition::class)
                            ->defaultValue(SymbolPosition::Before)
                        ->end()
                        ->enumNode(self::DECIMAL)
                            ->info('Decimal separator: comma (Ar 1 500,50) or dot. It must differ from the grouping.')
                            ->enumFqcn(DecimalSeparator::class)
                            ->defaultValue(DecimalSeparator::DEFAULT)
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(self::sameGroupingAndDecimal(...))
                        ->thenInvalid('grouping and decimal cannot be the same character: %s')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<mixed> $config the processed config: the tree has checked every value
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $services = $configurator->services();
        // The config tree has already checked each option and turned it into its enum.
        $ariary = \is_array($config[self::ARIARY]) ? $config[self::ARIARY] : [];
        $services->set(AriaryOptions::class)->args([
            $ariary[self::GROUPING],
            $ariary[self::SYMBOL_POSITION],
            $ariary[self::DECIMAL],
        ]);

        if (class_exists(ConstraintValidator::class)) {
            $services->set(MalagasyPhoneValidator::class)->tag('validator.constraint_validator');
            $services->set(AriaryAmountValidator::class)
                ->args([service(AriaryOptions::class)])
                ->tag('validator.constraint_validator');
        }
        if (class_exists(AbstractExtension::class)) {
            $services->set(MdgUtilsExtension::class)
                ->args([service(AriaryOptions::class)])
                ->tag('twig.extension');
        }
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        // The class can be installed without the bundle being enabled; only an enabled bundle
        // accepts "doctrine" config.
        if (!$container->hasExtension(self::DOCTRINE)) {
            return;
        }
        $container->prependExtensionConfig(self::DOCTRINE, [
            'dbal' => [
                'types' => [
                    PhoneNumberType::NAME => PhoneNumberType::class,
                    AriaryType::NAME => AriaryType::class,
                ],
            ],
        ]);
    }

    /**
     * @param array{grouping: Grouping, symbol_position: SymbolPosition, decimal: DecimalSeparator} $ariary
     */
    private static function sameGroupingAndDecimal(array $ariary): bool
    {
        return $ariary[self::GROUPING]->value === $ariary[self::DECIMAL]->value;
    }
}
