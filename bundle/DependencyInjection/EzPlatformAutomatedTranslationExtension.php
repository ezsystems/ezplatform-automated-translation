<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute\BlockAttributeEncoderInterface;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\FieldEncoderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EzPlatformAutomatedTranslationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        if (empty($config['system'])) {
            return;
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        // always needed because of Bundle extension.
        $loader->load('services_override.yml');

        $container->registerForAutoconfiguration(FieldEncoderInterface::class)
            ->addTag('ezplatform.automated_translation.field_encoder');

        $container->registerForAutoconfiguration(BlockAttributeEncoderInterface::class)
            ->addTag('ezplatform.automated_translation.block_attribute_encoder');

        if (!$this->hasConfiguredClients($config, $container)) {
            return;
        }

        $loader->load('ezadminui.yml');
        $loader->load('default_settings.yml');
        $loader->load('services.yml');

        $processor = new ConfigurationProcessor($container, $this->getAlias());
        $processor->mapSetting('configurations', $config);
        $processor->mapSetting('non_translatable_characters', $config);
        $processor->mapSetting('non_translatable_tags', $config);
        $processor->mapSetting('non_translatable_self_closed_tags', $config);
        $processor->mapSetting('non_valid_attribute_tags', $config);
    }

    private function hasConfiguredClients(array $config, ContainerBuilder $container): bool
    {
        return 0 !== count(array_filter($config['system'], static function ($value) use ($container) {
            return array_filter($value['configurations'], static function ($value) use ($container) {
                $value = is_array($value) ? reset($value) : $value;

                return !empty($container->resolveEnvPlaceholders($value, true));
            });
        }));
    }
}
