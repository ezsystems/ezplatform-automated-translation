<?php
/**
 * eZ Automated Translation Bundle.
 *
 * @package   EzSystems\eZAutomatedTranslationBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\API\Repository\Repository;

/**
 * Trait RepositoryAware
 */
trait RepositoryAware
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @param Repository $repository
     *
     * @required
     */
    public function setRepository(Repository $repository): void
    {
        $this->repository = $repository;
    }

}
