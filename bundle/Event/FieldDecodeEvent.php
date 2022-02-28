<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Event;

use eZ\Publish\Core\FieldType\Value;

final class FieldDecodeEvent
{
    /** @var string */
    private $type;

    /** @var Value */
    private $value;

    /** @var mixed */
    private $previousValue;

    public function __construct(
        string $type,
        Value $value,
        $previousValue
    ) {
        $this->type = $type;
        $this->value = $value;
        $this->previousValue = $previousValue;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): Value
    {
        return $this->value;
    }

    public function setValue(Value $value): void
    {
        $this->value = $value;
    }

    public function getPreviousValue()
    {
        return $this->previousValue;
    }

    public function setPreviousValue($previousValue): void
    {
        $this->previousValue = $previousValue;
    }
}
