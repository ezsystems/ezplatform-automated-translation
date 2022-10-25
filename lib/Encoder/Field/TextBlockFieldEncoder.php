<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\TextBlock\Value as TextBlockValue;
use eZ\Publish\Core\FieldType\Value;
use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedFieldException;

final class TextBlockFieldEncoder implements FieldEncoderInterface
{
    public function canEncode(Field $field): bool
    {
        return $field->value instanceof TextBlockValue;
    }

    public function canDecode(string $type): bool
    {
        return TextBlockValue::class === $type;
    }

    public function encode(Field $field): string
    {
        return (string) $field->value;
    }

    public function decode(string $value, $previousFieldValue): Value
    {
        $value = trim($value);

        if (strlen($value) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        // If string contains '&'
        // "Aide & procédures"
        // it will be warp with CDATA
        // '<![CDATA[Aide & procédures]]>'
        // CDATA will be transform to <fakecdata>
        // <fakecdata>Aides & démarches</fakecdata>
        // After translation
        // <fakecdata>Aids &amp; Procedures</fakecdata>
        // Then <fakecdata> will be replace by '<![CDATA[' . self::XML_MARKUP. @see Encoder::decode()
        // <![CDATA[<?xml version="1.0" encoding="UTF-8"? >Aids &amp; Procedures]]>
        // So we need to remove XML_MARKUP and replace '&amp;' by '&'
        // TODO This is not the good solution...
        $value = str_replace([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '&amp;',
        ], [
            '', '&'
        ], $value);

        return new TextBlockValue($value);
    }
}
