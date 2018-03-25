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

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Controller;

use EzSystems\EzPlatformAdminUiBundle\Controller\TranslationController as BaseTranslationController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TranslationController.
 */
class TranslationController extends BaseTranslationController
{
    /**
     * {@inheritdoc}
     */
    public function addAction(Request $request): Response
    {
        $response = parent::addAction($request);
        if (!$response instanceof RedirectResponse) {
            return $response;
        }

        $targetUrl    = $response->getTargetUrl();
        $pattern      = str_replace(
            '/',
            '\/',
            urldecode(
                $this->generateUrl(
                    'ezplatform.content.translate',
                    [
                        'contentId'        => '([0-9]*)',
                        'fromLanguageCode' => '([a-zA-Z-]*)',
                        'toLanguageCode'   => '([a-zA-Z-]*)',
                    ]
                )
            )
        );
        $serviceAlias = $request->request->get('add-translation')['translatorAlias'];
        if (1 !== preg_match("#{$pattern}#", $targetUrl)) {
            return $response;
        }
        $response->setTargetUrl(sprintf('%s?translatorAlias=%s', $targetUrl, $serviceAlias));

        return $response;
    }
}
