<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Kernel\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SovereignStack\SStack\Core\Kernel\KernelInterface;

final class TerminateEvent extends KernelEvent
{
    public function __construct(
        KernelInterface $kernel,
        private readonly ServerRequestInterface $request,
        private readonly ResponseInterface $response,
    ) {
        parent::__construct($kernel);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
