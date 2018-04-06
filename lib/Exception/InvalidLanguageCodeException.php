<?php
namespace EzSystems\EzPlatformAutomatedTranslation\Exception;

use InvalidArgumentException;

class InvalidLanguageCodeException extends InvalidArgumentException
{
    public function __construct($languageCode, $driver)
    {
        parent::__construct("$languageCode not recognized by $driver");
    }
}