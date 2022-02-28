<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute;

use EzSystems\EzPlatformAutomatedTranslation\Encoder\RichText\RichTextEncoder;
use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedAttributeException;

class RichTextBlockAttributeEncoder implements BlockAttributeEncoderInterface
{
    private const TYPE = 'richtext';

    /** @var \EzSystems\EzPlatformAutomatedTranslation\Encoder\RichText\RichTextEncoder */
    private $richTextEncoder;

    public function __construct(
        RichTextEncoder $richTextEncoder
    ) {
        $this->richTextEncoder = $richTextEncoder;
    }

    public function canEncode(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function canDecode(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function encode($value): string
    {
        return $this->richTextEncoder->encode((string) $value);
    }

    public function decode(string $value): string
    {
        $decodedValue = $this->richTextEncoder->decode($value);

        if (strlen($decodedValue) === 0) {
            throw new EmptyTranslatedAttributeException();
        }

        return $decodedValue;
    }
}
