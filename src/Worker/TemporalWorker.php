<?php

declare(strict_types=1);

namespace Baldinof\RoadRunnerBundle\Worker;

use Baldinof\RoadRunnerBundle\Event\WorkerStartEvent;
use Baldinof\RoadRunnerBundle\Event\WorkerStopEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Temporal\Worker\WorkerFactoryInterface;

class TemporalWorker implements WorkerInterface
{
    private HttpDependencies $dependencies;
    private WorkerFactoryInterface $workerFactory;
    private iterable $workflows;
    private iterable $activities;

    public function __construct(
        KernelInterface $kernel,
        WorkerFactoryInterface $workerFactory,
        iterable $workflows,
        iterable $activities
    )
    {
        $this->workerFactory = $workerFactory;
        $container = $kernel->getContainer();

        /** @var HttpDependencies $dependencies */
        $dependencies = $container->get(HttpDependencies::class);
        $this->dependencies = $dependencies;

        $this->workflows = $workflows;
        $this->activities = $activities;
    }

    public function start(): void
    {
        $this->dependencies->getEventDispatcher()->dispatch(new WorkerStartEvent());
        if ($mainQueue = \getenv('MAIN_QUEUE')) {
            $this->runWorkers($mainQueue);
        }

        if ($personalQueue = \getenv('PERSONAL_QUEUE')) {
            $this->runWorkers($personalQueue);
        }

        $this->workerFactory->run();
        $this->dependencies->getEventDispatcher()->dispatch(new WorkerStopEvent());
    }

    private function runWorkers(
        string $taskQueue = WorkerFactoryInterface::DEFAULT_TASK_QUEUE,
    ): void {
        $worker = $this->workerFactory->newWorker($taskQueue);

        foreach ($this->workflows as $workflow) {
            $worker->registerWorkflowTypes(get_class($workflow));
        }

        foreach ($this->activities as $activity) {
            $worker->registerActivityImplementations($activity);
        }
    }
}