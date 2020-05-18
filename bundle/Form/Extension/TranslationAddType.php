<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form\Extension;

use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use EzSystems\EzPlatformAdminUi\Form\Type\Content\Translation\TranslationAddType as BaseTranslationAddType;
use EzSystems\EzPlatformAutomatedTranslation\Client\ClientInterface;
use EzSystems\EzPlatformAutomatedTranslation\ClientProvider;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\TranslationAddDataTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TranslationAddType extends AbstractTypeExtension
{
    /** @var ClientProvider */
    private $clientProvider;

    /** @var LocaleConverterInterface */
    private $localeConverter;

    public function __construct(
        ClientProvider $clientProvider,
        LocaleConverterInterface $localeConverter
    ) {
        $this->clientProvider = $clientProvider;
        $this->localeConverter = $localeConverter;
    }

    public static function getExtendedTypes(): iterable
    {
        return [BaseTranslationAddType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $clients = $this->clientProvider->getClients();
        $clientsCount = count($clients);

        if ($clientsCount <= 0) {
            return;
        }
        if (1 === $clientsCount) {
            $client = array_pop($clients);
            $builder
                ->add(
                    'translatorAlias',
                    CheckboxType::class,
                    [
                        'label' => $client->getServiceFullName(),
                        'value' => $client->getServiceAlias(),
                        'data' => true,
                        'required' => false,
                    ]
                );
            $builder->addModelTransformer(new TranslationAddDataTransformer());

            return;
        }

        $builder
            ->add(
                'translatorAlias',
                ChoiceType::class,
                [
                    'label' => false,
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                    'choices' => ['' => 'no-service'] + $this->clientProvider->getClients(),
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

                        return '';
                    },
                ]
            );
        $builder->addModelTransformer(new TranslationAddDataTransformer());
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // let's pass to the template/form the possible language
        $map = [];

        $fillMap = function ($key, &$map) use ($form) {
            $languages = $form->get($key);
            foreach ($languages as $language) {
                /** @var Form $language */
                /** @var FormBuilderInterface $config */
                $config = $language->getConfig();
                $lang = $config->getOption('value');
                foreach ($this->clientProvider->getClients() as $client) {
                    $posix = $this->localeConverter->convertToPOSIX($lang);
                    if (null === $posix) {
                        continue;
                    }
                    if ($client->supportsLanguage($posix)) {
                        $map[$client->getServiceAlias()][] = $lang;
                    }
                }
            }
        };

        $fillMap('language', $map);
        $fillMap('base_language', $map);

        $view->vars['autotranslated_data'] = $map;
        parent::buildView($view, $form, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            ]
        );
    }
}
