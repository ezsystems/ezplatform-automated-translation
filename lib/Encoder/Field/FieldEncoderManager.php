<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\Value;
use InvalidArgumentException;

final class FieldEncoderManager
{
    /** @var FieldEncoderInterface[]|iterable */
    private $fieldEncoders;

    /**
     * @param iterable|FieldEncoderInterface[] $fieldEncoders
     */
    public function __construct(iterable $fieldEncoders = [])
    {
        $this->fieldEncoders = $fieldEncoders;
    }

    public function encode(Field $field): string
    {
        foreach ($this->fieldEncoders as $fieldEncoder) {
            if ($fieldEncoder->canEncode($field)) {
                return $fieldEncoder->encode($field);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to encode field %s. Make sure field encoder service for it is properly registered.',
                get_class($field)
            )
        );
    }

    /**
     * @param mixed $previousFieldValue
     *
     * @throws \InvalidArgumentException
     * @throws \EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedFieldException
     */
    public function decode(string $type, string $value, $previousFieldValue): Value
    {
        foreach ($this->fieldEncoders as $fieldEncoder) {
            if ($fieldEncoder->canDecode($type)) {
                return $fieldEncoder->decode($value, $previousFieldValue);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to decode field %s. Make sure field encoder service for it is properly registered.',
                $type
            )
        );
    }
}
