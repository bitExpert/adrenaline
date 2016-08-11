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

interface Plugin
{
    /**
     * Attaches the plugin to the given instance of Adrenaline
     *
     * @param Adrenaline $adrenaline
     * @return void
     */
    public function applyTo(Adrenaline $adrenaline);
}
