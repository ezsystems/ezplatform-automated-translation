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

use eZ\Publish\API\Repository\ContentTypeService;
use EzSystems\EzPlatformAdminUi\RepositoryForms\Data\ContentTranslationData;
use EzSystems\EzPlatformAutomatedTranslation\Translator;
use EzSystems\RepositoryForms\Data\Content\FieldData;
use EzSystems\RepositoryForms\Form\Type\Content\ContentEditType as BaseContentEditType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContentEditType.
 */
class ContentEditType extends AbstractTypeExtension
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContentTypeService
     */
    private $contentTypeService;

    /**
     * ContentEditType constructor.
     *
     * @param Translator         $translator
     * @param RequestStack       $requestStack
     * @param ContentTypeService $contentTypeService
     */
    public function __construct(
        Translator $translator,
        RequestStack $requestStack,
        ContentTypeService $contentTypeService
    ) {
        $this->translator         = $translator;
        $this->requestStack       = $requestStack;
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return BaseContentEditType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!$data instanceof ContentTranslationData) {
                    return;
                }
                $request = $this->requestStack->getMasterRequest();
                if (null === $request) {
                    return;
                }
                $fromLanguageCode             = $request->attributes->get('fromLanguageCode') ?? '';
                $toLanguageCode               = $request->attributes->get('toLanguageCode') ?? '';
                $serviceAlias                 = $request->query->get('translatorAlias');
                $content                      = $this->translator->translateContent(
                    $fromLanguageCode,
                    $toLanguageCode,
                    $serviceAlias,
                    $data->content
                );
                $contentType                  = $this->contentTypeService->loadContentType(
                    $content->contentInfo->contentTypeId
                );
                $newData                      = new ContentTranslationData(['content' => $content]);
                foreach ($content->getFieldsByLanguage() as $field) {
                    $fieldDef   = $contentType->getFieldDefinition($field->fieldDefIdentifier);
                    $fieldValue = $content->getFieldValue($fieldDef->identifier, $toLanguageCode);
                    $newData->addFieldData(
                        new FieldData(
                            [
                                'fieldDefinition' => $fieldDef,
                                'field'           => $field,
                                'value'           => $fieldValue,
                            ]
                        )
                    );
                }
                $event->setData($newData);
            }
        );
    }
}
