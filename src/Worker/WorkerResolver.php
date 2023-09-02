<?php

declare(strict_types=1);

namespace Baldinof\RoadRunnerBundle\Worker;

use Baldinof\RoadRunnerBundle\Exception\UnsupportedRoadRunnerModeException;
use Psr\Container\ContainerInterface;
use Spiral\RoadRunner\Environment\Mode;
use Spiral\RoadRunner\EnvironmentInterface;
use Spiral\RoadRunner\Http\HttpWorkerInterface;

final class WorkerResolver implements WorkerResolverInterface
{
    private EnvironmentInterface $environment;

    public function __construct(
        EnvironmentInterface $environment,
        private readonly ContainerInterface $container,
    ) {
        $this->environment = $environment;
    }

    public function resolve(string $mode): WorkerInterface
    {
        if ($this->environment->getMode() === Mode::MODE_HTTP) {
            return $this->container->get(HttpWorkerInterface::class);
        }
        if ($this->environment->getMode() === Mode::MODE_TEMPORAL) {
            return $this->container->get(TemporalWorker::class);
        }

        throw new UnsupportedRoadRunnerModeException($mode);
    }
}