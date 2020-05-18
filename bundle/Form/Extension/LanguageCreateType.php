<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form\Extension;

use EzSystems\EzPlatformAdminUi\Form\Type\Language\LanguageCreateType as BaseLanguageCreateType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class LanguageCreateType extends AbstractTypeExtension
{
    /** @var array */
    private $localeList;

    public function __construct(array $localeList)
    {
        $this->localeList = array_keys($localeList);
    }

    public static function getExtendedTypes(): iterable
    {
        return [BaseLanguageCreateType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->remove('languageCode');
        $builder->add(
            'languageCode',
            ChoiceType::class,
            [
                'label' => /* @Desc("Language code") */
                    'ezplatform.language.create.language_code',
                'required' => false,
                'choices' => array_combine($this->localeList, $this->localeList),
            ]
        );
    }
}
