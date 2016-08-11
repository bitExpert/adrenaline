<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline\Plugin;

use bitExpert\Adrenaline\Adrenaline;
use bitExpert\Slf4PsrLog\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Simple implementation of the {@link bitExpert\Adrenaline\Plugin\PluginCollection} interface
 *
 * @package bitExpert\Adrenaline\Plugin
 */
final class SimplePluginBundle implements PluginBundle
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Plugin[]
     */
    private $plugins;

    /**
     * SimplePluginCollection constructor.
     */
    public function __construct()
    {
        $this->logger = LoggerFactory::getLogger(get_class($this));
        $this->plugins = [];
    }

    /**
     * {@inheritdoc}
     */
    public function withPlugin(Plugin $plugin)
    {
        $instance = clone $this;
        $instance->plugins[] = $plugin;
        $this->logger->debug(sprintf(
            'Added plugin "%s".',
            get_class($plugin)
        ));
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(Adrenaline $adrenaline)
    {
        foreach ($this->plugins as $plugin) {
            $plugin->applyTo($adrenaline);

            $this->logger->debug(sprintf(
                'Applied plugin "%s".',
                get_class($plugin)
            ));
        }
    }
}
