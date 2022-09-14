<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Event;

use eZ\Publish\API\Repository\Values\Content\Field;

final class FieldEncodeEvent
{
    /** @var \eZ\Publish\API\Repository\Values\Content\Field */
    private $field;

    /** @var string */
    private $value;

    public function __construct(
        Field $field,
        string $value
    ) {
        $this->field = $field;
        $this->value = $value;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    public function setField(Field $field): void
    {
        $this->field = $field;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
