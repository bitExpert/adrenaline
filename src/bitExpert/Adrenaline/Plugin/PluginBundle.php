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

interface PluginBundle extends Plugin
{
    /**
     * Returns a new instance of the collection including the given plugin
     * (immutable)
     *
     * @param Plugin $plugin
     * @return PluginCollection
     */
    public function withPlugin(Plugin $plugin);
}
