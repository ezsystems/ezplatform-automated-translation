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

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form\Extension;

use EzSystems\EzPlatformAdminUi\Form\Type\Content\Translation\TranslationAddType as BaseTranslationAddType;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\Data\TranslationAddData;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\TranslationAddDataTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TranslationAddType.
 */
class TranslationAddType extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return BaseTranslationAddType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'translatorAlias',
                TextType::class,
                ['label' => 'plop']
            );
        $builder->addModelTransformer(new TranslationAddDataTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TranslationAddData::class,
            ]
        );
    }
}
