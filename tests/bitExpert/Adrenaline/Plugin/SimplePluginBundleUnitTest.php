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

/**
 * Unit test for {@link \bitExpert\Adrenaline\Plugin\SimplePluginBundle}.
 */
class SimplePluginBundleUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function addingPluginIsImmutable()
    {
        $bundle = new SimplePluginBundle();
        $bundle2 = $bundle->withPlugin($this->getMock(Plugin::class));

        $this->assertNotSame($bundle, $bundle2);
    }

    /**
     * @test
     */
    public function addedPluginWillBeApplied()
    {
        $adrenaline = $this->getMock(Adrenaline::class);
        $bundle = new SimplePluginBundle();
        $plugin = $this->getMock(Plugin::class);

        $plugin->expects($this->once())
            ->method('applyTo')
            ->with($adrenaline);

        $bundle = $bundle->withPlugin($plugin);
        $bundle->applyTo($adrenaline);
    }

    /**
     * @test
     */
    public function addedPluginsWillBeAppliedInCorrectOrder()
    {
        $expectedOrder = [
            'plugin',
            'plugin2'
        ];

        $givenOrder = [];

        $adrenaline = $this->getMock(Adrenaline::class);
        $bundle = new SimplePluginBundle();

        $plugin = $this->getMock(Plugin::class);
        $plugin2 = $this->getMock(Plugin::class);

        $plugin->expects($this->once())
            ->method('applyTo')
            ->with($adrenaline)
            ->will($this->returnCallback(function () use (&$givenOrder) {
                $givenOrder[] = 'plugin';
            }));

        $plugin2->expects($this->once())
            ->method('applyTo')
            ->with($adrenaline)
            ->will($this->returnCallback(function () use (&$givenOrder) {
                $givenOrder[] = 'plugin2';
            }));

        $bundle = $bundle
            ->withPlugin($plugin)
            ->withPlugin($plugin2);

        $bundle->applyTo($adrenaline);
        $this->assertEquals($expectedOrder, $givenOrder);
    }
}
