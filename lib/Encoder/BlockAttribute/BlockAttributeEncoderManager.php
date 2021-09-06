<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute;

use InvalidArgumentException;

final class BlockAttributeEncoderManager
{
    /** @var BlockAttributeEncoderInterface[]|iterable */
    private $blockAttributeEncoders;

    /**
     * @param iterable|BlockAttributeEncoderInterface[] $blockAttributeEncoders
     */
    public function __construct(iterable $blockAttributeEncoders = [])
    {
        $this->blockAttributeEncoders = $blockAttributeEncoders;
    }

    /**
     * @param mixed $value
     */
    public function encode(string $type, $value): string
    {
        foreach ($this->blockAttributeEncoders as $blockAttributeEncoder) {
            if ($blockAttributeEncoder->canEncode($type)) {
                return $blockAttributeEncoder->encode($value);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to encode block attribute %s. Make sure block attribute encoder service for it is properly registered.',
                $type
            )
        );
    }

    /**
     * @throws \EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedAttributeException
     */
    public function decode(string $type, string $value): string
    {
        foreach ($this->blockAttributeEncoders as $blockAttributeEncoder) {
            if ($blockAttributeEncoder->canDecode($type)) {
                return $blockAttributeEncoder->decode($value);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to decode block attribute %s. Make sure block attribute encoder service for it is properly registered.',
                $type
            )
        );
    }
}
