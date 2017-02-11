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

namespace bitExpert\Adrenaline\Responder;

use bitExpert\Adroit\Domain\Payload;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Responder to convert a given Twig template into an response object.
 *
 * @api
 */
class TwigResponder implements Responder
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;
    /**
     * @var string
     */
    protected $template;
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Creates a new {\bitExpert\Adrenaline\Responder\TwigResponder}.
     *
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Sets the template to render.
     *
     * @param string $template
     * @throws \InvalidArgumentException
     */
    public function setTemplate($template)
    {
        if (!is_string($template)) {
            throw new \InvalidArgumentException('Given template name needs to be of type string!');
        }

        $this->template = $template;
    }

    /**
     * Set additional HTTP headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Twig_Error_Syntax
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     */
    public function __invoke(Payload $payload, ResponseInterface $response) : ResponseInterface
    {
        if (null === $this->template) {
            throw new \RuntimeException('No template set to render!');
        }

        /** @var \bitExpert\Adrenaline\Domain\DomainPayload $payload */
        $values = $payload->getValues();
        $response->getBody()->rewind();
        $response->getBody()->write($this->twig->render($this->template, $values));

        $headers = array_merge($this->headers, ['Content-Type' => 'text/html']);
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        /** @var \bitExpert\Adrenaline\Domain\DomainPayload $payload */
        $status = $payload->getStatus() ?: StatusCodeInterface::STATUS_OK;

        return $response->withStatus($status);
    }
}
