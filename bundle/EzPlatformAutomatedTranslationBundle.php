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

namespace EzSystems\EzPlatformAutomatedTranslationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class EzPlatformAutomatedTranslationBundle.
 */
class EzPlatformAutomatedTranslationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return 'EzPlatformAdminUiBundle';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
}
