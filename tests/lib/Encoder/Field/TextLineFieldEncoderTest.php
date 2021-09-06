<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Tests\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\TextLine;
use EzSystems\EzPlatformAutomatedTranslation\Encoder\Field\TextLineFieldEncoder;
use PHPUnit\Framework\TestCase;

class TextLineFieldEncoderTest extends TestCase
{
    public function testEncode()
    {
        $field = new Field([
            'fieldDefIdentifier' => 'field_1_textline',
            'value' => new TextLine\Value('Some text 1'),
        ]);

        $subject = new TextLineFieldEncoder();
        $result = $subject->encode($field);

        $this->assertEquals('Some text 1', $result);
    }

    public function testDecode()
    {
        $field = new Field([
            'fieldDefIdentifier' => 'field_1_textline',
            'value' => new TextLine\Value('Some text 1'),
        ]);

        $subject = new TextLineFieldEncoder();
        $result = $subject->decode('Some text 1', $field->value);

        $this->assertInstanceOf(TextLine\Value::class, $result);
        $this->assertEquals(new TextLine\Value('Some text 1'), $result);
    }
}
