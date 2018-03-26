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
use EzSystems\EzPlatformAutomatedTranslation\Client\ClientInterface;
use EzSystems\EzPlatformAutomatedTranslation\ClientProvider;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\Data\TranslationAddData;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\TranslationAddDataTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TranslationAddType.
 */
class TranslationAddType extends AbstractTypeExtension
{
    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * TranslationAddType constructor.
     *
     * @param ClientProvider $clientProvider
     */
    public function __construct(ClientProvider $clientProvider)
    {
        $this->clientProvider = $clientProvider;
    }

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
                ChoiceType::class,
                [
                    'label'        => false,
                    'expanded'     => false,
                    'multiple'     => false,
                    'choices'      => ['no-service' => 'no-service'] + $this->clientProvider->getClients(),
                    'choice_label' => function ($client) {
                        if ($client instanceof ClientInterface) {
                            return ucfirst($client->getServiceFullName());
                        }

                        return $client;
                    },
                    'choice_value' => function ($client) {
                        if ($client instanceof ClientInterface) {
                            return $client->getServiceAlias();
                        }

                        return $client;
                    },
                ]
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
