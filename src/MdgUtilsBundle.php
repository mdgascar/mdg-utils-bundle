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

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode(self::ARIARY)
                    ->info('How the ariary filter, the AriaryAmount constraint and the mg_ariary type write and read amounts.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode(AriaryOptions::GROUPING)
                            ->info('Thousands separator: space (Ar 1 500), dot (Ar 1.500) or comma (Ar 1,500, English style; amounts are then read in that style only).')
                            ->values(self::values(Grouping::cases()))
                            ->defaultValue(Grouping::DEFAULT->value)
                        ->end()
                        ->enumNode(AriaryOptions::SYMBOL_POSITION)
                            ->info('before (Ar 1 500) or after (1 500 Ar).')
                            ->values(self::values(SymbolPosition::cases()))
                            ->defaultValue(SymbolPosition::Before->value)
                        ->end()
                        ->enumNode(AriaryOptions::DECIMAL)
                            ->info('Decimal separator: comma (Ar 1 500,50) or dot. It must differ from the grouping.')
                            ->values(self::values(DecimalSeparator::cases()))
                            ->defaultValue(DecimalSeparator::DEFAULT->value)
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static fn(array $ariary): bool => $ariary[AriaryOptions::GROUPING] === $ariary[AriaryOptions::DECIMAL])
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
        $services->set(AriaryOptions::class)
            ->factory([AriaryOptions::class, 'fromConfig'])
            ->args([$config[self::ARIARY]]);

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
     * @param list<\BackedEnum> $cases
     *
     * @return list<int|string>
     */
    private static function values(array $cases): array
    {
        return array_map(static fn(\BackedEnum $case): int|string => $case->value, $cases);
    }
}
