<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Client;

/**
 * Interface ClientInterface.
 */
interface ClientInterface
{
    /**
     * @param array $configuration
     *
     * @return mixed
     */
    public function setConfiguration(array $configuration): void;

    /**
     * @param string $payload
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    public function translate(string $payload, ?string $from, string $to): string;

    /**
     * @param string $languageCode
     *
     * @return mixed
     */
    public function supportsLanguage(string $languageCode);

    /**
     * Use as key.
     *
     * @return string
     */
    public function getServiceAlias(): string;

    /**
     * Use for Human.
     *
     * @return string
     */
    public function getServiceFullName(): string;
}
