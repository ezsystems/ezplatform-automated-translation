<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use EzSystems\EzPlatformAutomatedTranslationBundle\DependencyInjection\EzPlatformAutomatedTranslationExtension;

class EzPlatformAutomatedTranslationExtensionTest extends TestCase
{
    public function clientConfigurationDataProvider()
    {
        return [
            //set 1
            [['system' => ['default' => ['configurations' => []]]], false],
            //set 2
            [['system' => ['default' => ['configurations' => [
                'client1' => ['key1' => 'value1'],
            ]]]], true],
            //set 3
            [['system' => ['default' => ['configurations' => [
                'client1' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ]]]], true],
            //set 3
            [['system' => ['default' => ['configurations' => [
                'client1' => [
                    'key1' => 'value1',
                    'key2' => 'valueX',
                ],
                'client2' => [
                    'key3' => 'value2',
                    'key2' => 'valueY',
                ],
            ]]]], true],
            //set 4
            [['system' => ['default' => ['configurations' => [
                'client1' => [
                    'key1' => 'value1',
                    'key2' => 'valueX',
                ],
                'client2' => [
                    'key3' => 'value2',
                    'key2' => 'valueY',
                ],
                'client3' => [
                    'key1' => 'ENV_TEST1',
                    'key2' => 'valueX',
                ],
            ]]]], true],
        ];
    }

    /**
     * @dataProvider clientConfigurationDataProvider
     */
    public function testHasConfiguredClients(array $input, bool $expected)
    {
        $containerMock = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['resolveEnvPlaceholders'])
            ->getMock();

        $containerMock
            ->expects($this->any())
            ->method('resolveEnvPlaceholders')
            ->withConsecutive(['value1'], ['value2'], ['ENV_TEST1'])
            ->willReturnOnConsecutiveCalls(['value1'], ['value2'], ['test1']);

        $subject = new EzPlatformAutomatedTranslationExtension();

        // call for private method hasConfiguredClients on $subject object
        $hasConfiguredClientsResult = call_user_func_array(\Closure::bind(
            function ($method, $params) {
                return call_user_func_array([$this, $method], $params);
            },
            $subject,
            EzPlatformAutomatedTranslationExtension::class
        ), ['hasConfiguredClients', [$input, $containerMock]]);

        $this->assertEquals($expected, $hasConfiguredClientsResult);
    }
}
