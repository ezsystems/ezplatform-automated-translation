<?php
/**
 * eZ Automated Translation Bundle.
 *
 * @package   EzSystems\eZAutomatedTranslationBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form;

use EzSystems\EzPlatformAdminUi\Form\Data\Content\Translation\TranslationAddData as BaseTranslationAddData;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\Data\TranslationAddData;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class TranslationAddDataTransformer.
 */
class TranslationAddDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        /* @var BaseTranslationAddData $value */
        return new TranslationAddData($value->getLocation(), $value->getLanguage(), $value->getBaseLanguage());
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}
