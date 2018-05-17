<?php
/**
 * eZ Platform Automated Translation Bundle.
 *
 * @package   EzSystems\EzPlatformAutomatedTranslationBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class EzPlatformAutomatedTranslationExtension.
 */
class EzPlatformAutomatedTranslationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        // always needed because of Bundle extension.
        $loader->load('services_override.yml');

        if (empty($config['system'])) {
            return;
        }

        $asseticBundles   = $container->getParameter('assetic.bundles');
        $asseticBundles[] = 'EzPlatformAutomatedTranslationBundle';
        $container->setParameter('assetic.bundles', $asseticBundles);

        $loader->load('ezadminui.yml');
        $loader->load('default_settings.yml');
        $loader->load('services.yml');

        $processor = new ConfigurationProcessor($container, $this->getAlias());
        $processor->mapSetting('configurations', $config);
        $processor->mapSetting('nontranslatablecharacters', $config);
        $processor->mapSetting('nontranslatabletags', $config);
        $processor->mapSetting('nonnalidattributetags', $config);
    }
}
