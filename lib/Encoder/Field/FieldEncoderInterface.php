<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\Value;

interface FieldEncoderInterface
{
    public function canEncode(Field $field): bool;

    public function canDecode(string $type): bool;

    public function encode(Field $field): string;

    /**
     * @param mixed $previousValue
     */
    public function decode(string $value, $previousValue): Value;
}
