<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Exception;

use InvalidArgumentException;

/**
 * Class InvalidLanguageCodeException.
 */
class InvalidLanguageCodeException extends InvalidArgumentException
{
    /**
     * InvalidLanguageCodeException constructor.
     *
     * @param string $languageCode
     * @param string $driver
     */
    public function __construct(string $languageCode, string $driver)
    {
        parent::__construct("$languageCode not recognized by $driver");
    }
}
