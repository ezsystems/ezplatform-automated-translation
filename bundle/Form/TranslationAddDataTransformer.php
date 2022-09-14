<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form;

use EzSystems\EzPlatformAdminUi\Form\Data\Content\Translation\TranslationAddData as BaseTranslationAddData;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\Data\TranslationAddData;
use Symfony\Component\Form\DataTransformerInterface;

class TranslationAddDataTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        /* @var BaseTranslationAddData $value */
        return new TranslationAddData($value->getLocation(), $value->getLanguage(), $value->getBaseLanguage());
    }

    public function reverseTransform($value)
    {
        return $value;
    }
}
