<?php

declare(strict_types=1);

use E1on\OminesDatatablesElasticsearchAdapter\ElasticaAdapter;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

$kernel = $GLOBALS['kernel'];

$container = $kernel->getContainer();

$session = $container->get(SessionInterface::class);

$adapter = new ElasticaAdapter($session);

dd($adapter);