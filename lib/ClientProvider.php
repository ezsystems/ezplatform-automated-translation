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

namespace EzSystems\EzPlatformAutomatedTranslation;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformAutomatedTranslation\Client\ClientInterface;

/**
 * Class ClientProvider.
 */
class ClientProvider
{
    /**
     * @var ClientInterface[]
     */
    private $clients;

    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * ClientProvider constructor.
     *
     * @param ConfigResolverInterface $configResolver
     * @param iterable                $clients
     */
    public function __construct(iterable $clients, ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
        foreach ($clients as $client) {
            $this->addClient($client);
        }
    }

    /**
     * @param ClientInterface $client
     *
     * @throws \ReflectionException
     *
     * @return ClientProvider
     */
    private function addClient(ClientInterface $client): self
    {
        $configurations = $this->configResolver->getParameter('configurations', 'ez_platform_automated_translation');
        $reflection     = new \ReflectionClass($client);
        $key            = strtolower($reflection->getShortName());
        if (isset($configurations[$key])) {
            $client->setConfiguration($configurations[$key]);
            $this->clients[$key] = $client;
        }

        return $this;
    }

    /**
     * @param $key
     *
     * @return ClientInterface
     */
    public function get($key): ClientInterface
    {
        if (!isset($this->clients[$key])) {
            throw new \LogicException("The Remote Service {$key} does not exist or has not been configured.");
        }

        return $this->clients[$key];
    }

    /**
     * @return ClientInterface[]
     */
    public function getClients(): array
    {
        return $this->clients;
    }
}
