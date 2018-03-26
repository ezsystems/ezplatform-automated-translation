<?php
/**
 * eZ Platform Automated Translation Bundle.
 *
 * @package   EzSystems\EzPlatformAutomatedTranslationBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()->in('bundle')->in('lib')->in('tests');

return PhpCsFixer\Config::create()
                        ->setRules(
                            [
                                '@Symfony'                    => true,
                                '@Symfony:risky'              => true,
                                'concat_space'                => ['spacing' => 'one'],
                                'binary_operator_spaces'      => [
                                    'align_equals'       => true,
                                    'align_double_arrow' => true,
                                ],
                                'array_syntax'                => ['syntax' => 'short'],
                                'ordered_imports'             => true,
                                'phpdoc_order'                => true,
                                'linebreak_after_opening_tag' => true,
                                'phpdoc_no_package'           => false,
                            ]
                        )
                        ->setFinder($finder);
