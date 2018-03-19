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

namespace EzSystems\EzPlatformAutomatedTranslation\Client;

/**
 * Class Deepl
 */
class Deepl implements ClientInterface
{
    /**
     * {@inheritdoc}
     */
    public function getServiceName(): string
    {
        return "deepl";
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration): void
    {

    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $payload, string $from, string $to): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsLanguage(string $languageCode)
    {
        return false;
    }

}
