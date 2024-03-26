<?php

declare(strict_types=1);

namespace E1on\OminesDatatablesElasticsearchAdapterBundle\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function provide(): SessionInterface
    {
        $request = $this->requestStack->getMainRequest();

        /** @var Request $request */
        return $request->getSession();
    }
}
