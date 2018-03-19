<?php
/**
 * eZ Automated Translation Bundle.
 *
 * @package   EzSystems\eZAutomatedTranslationBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\TextLine\Value as TextLineValue;
use eZ\Publish\Core\FieldType\RichText\Value as RichTextValue;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Class Encoder
 *
 * Google Translate and Deepl (and probably others) are able to "ignore" markups when they translate
 *
 * This Encoder basically encodes a Field[] in XML of Translatable Fields (TextLineValue,TextBlockValue and
 * RichTextValue)
 *
 * Ex:
 *      <response>
 *             <title>The string value</title>
 *             <description>>The string value</description>
 *             <fieldIdentifier>value of the field converted into string</fieldIdentifier>
 *      </response>
 *
 * But as a RichTextValue is already a XML, the content Repository returns a valid XML already
 *         <?xml version="1.0" encoding="UTF-8"?><section><para>lorem ipsum</para></section>
 *
 * Then you end up with an XML Encoded like this (look for the <![CDATA[)
 *
 *      <response>
 *             <title>The string value</title>
 *             <description>
 *                  <![CDATA[<?xml version="1.0" encoding="UTF-8"?><section><para>lorem ipsum</para></section>]]>
 *              </description>
 *      </response>
 *
 * Which is bad because remote translation services are going to try to translate inside <![CDATA[ ]]>
 *
 * Then this Encoder fixes that, trusting the fact that RichTextValue is a valid XML
 *
 *      <response>
 *             <title>The string value</title>
 *             <description>
 *                  <fakecdata><section><para>lorem ipsum</para></section></fakecdata>
 *              </description>
 *      </response>
 *
 * Wrapping the valid XML in "<fakecdata>", the global XML is still valid, and the translation works
 *
 * The decode function reverses the wrapping.
 *
 */
class Encoder
{

    /**
     * Use to fake the <![CDATA[ something ]]> to <fakecdata> something </fakecdata>
     */
    private const CDATA_FAKER_TAG = 'fakecdata';

    private const XML_MARKUP = '<?xml version="1.0" encoding="UTF-8"?>';

    /**
     * @param Field[] $fields
     *
     * @return string
     */
    public function encode(array $fields): string
    {
        $results = [];
        foreach ($fields as $field) {
            // Note that TextBlock is a TextLine
            if ($field->value instanceof TextLineValue) {
                $value                               = (string) $field->value;
                $results[$field->fieldDefIdentifier] = $value;
                continue;
            }
            if ($field->value instanceof RichTextValue) {
                // we need to remove that to make it a good XML
                $results[$field->fieldDefIdentifier] = substr((string) $field->value, \strlen(self::XML_MARKUP));
            }
        }
        $encoder = new XmlEncoder();
        $payload = $encoder->encode($results, XmlEncoder::FORMAT);
        // here Encoder has  decorated with CDATA, we don't want the CDATA
        $payload = str_replace(
            ['<![CDATA[', ']]>'],
            ['<'.self::CDATA_FAKER_TAG.'>', '</'.self::CDATA_FAKER_TAG.'>'],
            $payload
        );

        return $payload;
    }

    /**
     * @param string $xml
     *
     * @return array
     */
    public function decode(string $xml): array
    {
        $encoder = new XmlEncoder();
        $data    = str_replace(
            ['<'.self::CDATA_FAKER_TAG.'>', '</'.self::CDATA_FAKER_TAG.'>'],
            ['<![CDATA['.self::XML_MARKUP, ']]>'],
            $xml
        );

        return $encoder->decode($data, XmlEncoder::FORMAT);

    }
}
