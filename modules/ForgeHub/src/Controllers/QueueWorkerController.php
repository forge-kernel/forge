<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Controllers;

use Modules\ForgeEvents\Services\QueueWorkerService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: "/hub/queue-workers")]
#[UseMiddleware(['web', 'auth', 'hub-permissions'])]
final class QueueWorkerController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly QueueWorkerService $queueWorkerService
    ) {
    }

    #[Endpoint]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $workers = $this->queueWorkerService->getWorkers();

        foreach ($workers as &$worker) {
            if (!isset($worker['output_file'])) {
                $worker['output_file'] = $this->queueWorkerService->getOutputFilePath($worker['id'] ?? '');
            }
            $worker['has_output'] = file_exists($worker['output_file']);
            $worker['output_size'] = $this->queueWorkerService->getOutputFileSize($worker['id'] ?? '');

            if ($worker['has_output']) {
                $worker['last_output_preview'] = $this->queueWorkerService->getLastOutput($worker['id'] ?? '', 10);
            }
        }

        $phpInfo = $this->queueWorkerService->getPhpInfo();

        $data = [
            'title' => 'Queue Workers',
            'workers' => $workers,
            'phpInfo' => $phpInfo,
        ];

        return $this->view(view: "queue-workers", data: $data);
    }

    #[Endpoint(method: "POST")]
    public function create(Request $request): Response
    {
        $data = $request->json();
        $name = trim($data['name'] ?? '');
        $queues = $data['queues'] ?? [];
        $processes = isset($data['processes']) ? (int) $data['processes'] : 1;

        if (empty($name)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Name is required',
            ], 400);
        }

        if (empty($queues) || !is_array($queues)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'At least one queue is required',
            ], 400);
        }

        $validQueues = [];
        foreach ($queues as $queue) {
            $queue = trim($queue);
            if (!empty($queue) && preg_match('/^[a-zA-Z0-9_-]+$/', $queue)) {
                $validQueues[] = $queue;
            }
        }

        if (empty($validQueues)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid queue names',
            ], 400);
        }

        if ($processes < 1 || $processes > 10) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Processes must be between 1 and 10',
            ], 400);
        }

        $worker = $this->queueWorkerService->createWorker($name, $validQueues, $processes);

        return $this->jsonResponse([
            'success' => true,
            'worker' => $worker,
        ]);
    }

    #[Endpoint("/{id:[^/]+}", "PUT")]
    public function update(Request $request, string $id): Response
    {
        $data = $request->json();
        $name = isset($data['name']) ? trim($data['name']) : null;
        $queues = $data['queues'] ?? null;
        $processes = isset($data['processes']) ? (int) $data['processes'] : null;

        $updateData = [];

        if ($name !== null) {
            if (empty($name)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Name is required',
                ], 400);
            }
            $updateData['name'] = $name;
        }

        if ($queues !== null) {
            if (empty($queues) || !is_array($queues)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'At least one queue is required',
                ], 400);
            }

            $validQueues = [];
            foreach ($queues as $queue) {
                $queue = trim($queue);
                if (!empty($queue) && preg_match('/^[a-zA-Z0-9_-]+$/', $queue)) {
                    $validQueues[] = $queue;
                }
            }

            if (empty($validQueues)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid queue names',
                ], 400);
            }

            $updateData['queues'] = $validQueues;
        }

        if ($processes !== null) {
            if ($processes < 1 || $processes > 10) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Processes must be between 1 and 10',
                ], 400);
            }
            $updateData['processes'] = $processes;
        }

        $worker = $this->queueWorkerService->updateWorker($id, $updateData);

        if (!$worker) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker not found',
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'worker' => $worker,
        ]);
    }

    #[Endpoint("/{id:[^/]+}", "DELETE")]
    public function delete(Request $request, string $id): Response
    {
        $success = $this->queueWorkerService->removeWorker($id);

        if (!$success) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker not found',
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    #[Endpoint("/{id:[^/]+}")]
    public function show(Request $request, string $id): Response
    {
        $worker = $this->queueWorkerService->getWorker($id);

        if (!$worker) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker not found',
            ], 404);
        }

        $worker['has_output'] = file_exists($worker['output_file'] ?? '');
        $worker['output_size'] = $this->queueWorkerService->getOutputFileSize($id);
        $worker['last_output'] = $this->queueWorkerService->getLastOutput($id, 200);

        return $this->jsonResponse([
            'success' => true,
            'worker' => $worker,
        ]);
    }

    #[Endpoint("/{id:[^/]+}/start", "POST")]
    public function start(Request $request, string $id): Response
    {
        $worker = $this->queueWorkerService->getWorker($id);

        if (!$worker) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker not found',
            ], 404);
        }

        if ($this->queueWorkerService->isWorkerRunning($id)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker is already running',
            ], 400);
        }

        $success = $this->queueWorkerService->startWorker($id);

        if (!$success) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to start worker',
            ], 500);
        }

        $worker = $this->queueWorkerService->getWorker($id);

        return $this->jsonResponse([
            'success' => true,
            'worker' => $worker,
            'message' => 'Worker started successfully. It may take a few seconds to appear as running.',
        ]);
    }

    #[Endpoint("/{id:[^/]+}/stop", "POST")]
    public function stop(Request $request, string $id): Response
    {
        $worker = $this->queueWorkerService->getWorker($id);

        if (!$worker) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker not found',
            ], 404);
        }

        if (!$this->queueWorkerService->isWorkerRunning($id)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker is not running',
            ], 400);
        }

        $success = $this->queueWorkerService->stopWorker($id);

        if (!$success) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to stop worker',
            ], 500);
        }

        $worker = $this->queueWorkerService->getWorker($id);

        return $this->jsonResponse([
            'success' => true,
            'worker' => $worker,
        ]);
    }

    #[Endpoint("/{id:[^/]+}/output")]
    public function getOutput(Request $request, string $id): Response
    {
        $worker = $this->queueWorkerService->getWorker($id);

        if (!$worker) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker not found',
            ], 404);
        }

        $output = $this->queueWorkerService->getLastOutput($id, 200);
        $hasOutput = !empty($output);
        $outputSize = $this->queueWorkerService->getOutputFileSize($id);

        return $this->jsonResponse([
            'success' => true,
            'output' => $output,
            'has_output' => $hasOutput,
            'output_size' => $outputSize,
        ]);
    }

    #[Endpoint("/{id:[^/]+}/output", "DELETE")]
    public function clearOutput(Request $request, string $id): Response
    {
        $worker = $this->queueWorkerService->getWorker($id);

        if (!$worker) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Worker not found',
            ], 404);
        }

        $success = $this->queueWorkerService->clearOutput($id);

        return $this->jsonResponse([
            'success' => $success,
        ]);
    }
}
