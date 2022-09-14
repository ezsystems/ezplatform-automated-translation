<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use eZ\Publish\Core\FieldType\Value;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\RichText\RichTextEncoder;
use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedFieldException;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value as RichTextValue;

final class RichTextFieldEncoder implements FieldEncoderInterface
{
    /** @var \EzSystems\EzPlatformAutomatedTranslation\Encoder\RichText\RichTextEncoder */
    private $richTextEncoder;

    public function __construct(
        RichTextEncoder $richTextEncoder
    ) {
        $this->richTextEncoder = $richTextEncoder;
    }

    public function canEncode($field): bool
    {
        return $field->value instanceof RichTextValue;
    }

    public function canDecode(string $type): bool
    {
        return RichTextValue::class === $type;
    }

    public function encode($field): string
    {
        return $this->richTextEncoder->encode((string) $field->value);
    }

    public function decode(string $value, $previousFieldValue): Value
    {
        $decodedValue = $this->richTextEncoder->decode($value);

        if (strlen($decodedValue) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        return new RichTextValue($decodedValue);
    }
}
