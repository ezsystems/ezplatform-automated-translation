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

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form\Data;

use EzSystems\EzPlatformAdminUi\Form\Data\Content\Translation\TranslationAddData as BaseTranslationAddData;

/**
 * Class TranslationAddData.
 */
class TranslationAddData extends BaseTranslationAddData
{
    /**
     * @var string
     */
    protected $translatorAlias;

    /**
     * @return mixed
     */
    public function getTranslatorAlias()
    {
        return $this->translatorAlias;
    }

    /**
     * @param mixed $translatorAlias
     */
    public function setTranslatorAlias($translatorAlias): void
    {
        $this->translatorAlias = $translatorAlias;
    }
}
