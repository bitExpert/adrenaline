<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types = 1);

namespace bitExpert\Adrenaline\Accept;

use Negotiation\Accept;
use Negotiation\Negotiator;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request;

/**
 * Unit test for {@link \bitExpert\Adroit\Accept\ContentNegotiationManager}.
 */
class ContentNegotiationManagerUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var \Negotiation\Negotiator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $negotiator;
    /**
     * @var ContentNegotiationManager
     */
    protected $manager;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = new Request();
        $this->negotiator = $this->createMock(Negotiator::class);
        $this->manager = new ContentNegotiationManager($this->negotiator);
    }

    /**
     * @test
     */
    public function willReturnAcceptHeaderValueWhenMatchWasFound()
    {
        $this->request = $this->request->withHeader('Accept', 'text/html');
        $this->negotiator->expects($this->once())
            ->method('getBest')
            ->will($this->returnValue(new Accept('text/html')));

        $bestMatch = $this->manager->getBestMatch($this->request);

        $this->assertSame('text/html', $bestMatch);
    }

    /**
     * @test
     */
    public function willReturnNullWhenMatchWasNotFound()
    {
        $this->request = $this->request->withHeader('Accept', 'text/html');
        $this->negotiator->expects($this->once())
            ->method('getBest')
            ->will($this->returnValue(null));

        $bestMatch = $this->manager->getBestMatch($this->request);

        $this->assertNull($bestMatch);
    }

    /**
     * @test
     */
    public function willReturnNullIfAcceptHeaderIsNotPresent()
    {
        $bestMatch = $this->manager->getBestMatch($this->request);

        $this->assertNull($bestMatch);
    }
}
