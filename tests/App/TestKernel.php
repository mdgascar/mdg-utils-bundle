<?php

declare(strict_types=1);

namespace Mdgascar\MdgUtilsBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Mdgascar\MdgUtilsBundle\MdgUtilsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * A small app with the bundle and, unless `$minimal`, Twig and Doctrine.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    private const string ENVIRONMENT = 'test';

    /**
     * @param array<string, mixed> $mdgUtilsConfig
     */
    public function __construct(
        private readonly array $mdgUtilsConfig = [],
        private readonly bool $minimal = false,
    ) {
        parent::__construct(self::ENVIRONMENT, true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        if (!$this->minimal) {
            yield new TwigBundle();
            yield new DoctrineBundle();
        }
        yield new MdgUtilsBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        // One cache per configuration, so each test kernel compiles its own container.
        return sys_get_temp_dir() . '/mdg-utils-bundle/' . md5(serialize([$this->mdgUtilsConfig, $this->minimal]));
    }

    public function getLogDir(): string
    {
        return $this->getCacheDir() . '/log';
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'validation' => ['enabled' => true],
            'translator' => ['enabled' => true, 'fallbacks' => ['en']],
        ]);
        if (!$this->minimal) {
            $container->extension('twig', ['strict_variables' => true]);
            $container->extension('doctrine', ['dbal' => ['url' => 'sqlite:///:memory:']]);
        }
        $container->extension('mdg_utils', $this->mdgUtilsConfig);
    }
}
