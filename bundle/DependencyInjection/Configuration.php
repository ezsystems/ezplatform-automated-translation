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

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class Configuration.
 */
class Configuration extends SiteAccessAware\Configuration
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('ez_platform_automated_translation');
        $systemNode  = $this->generateScopeBaseNode($rootNode);
        $systemNode->variableNode('configurations')->end();

        return $treeBuilder;
    }
}
