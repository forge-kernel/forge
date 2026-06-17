<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub;

use App\Modules\ForgeEvents\Services\QueueWorkerService;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware(['web', 'auth', 'hub-permissions'])]
final class QueueWorkerController
{
    use ControllerHelper;

    public function __construct(
        private readonly QueueWorkerService $queueWorkerService
    ) {
    }

    #[Route("/hub/queue-workers")]
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

        return $this->view(view: "pages/hub/queue-workers", data: $data);
    }

    #[Route("/hub/queue-workers", "POST")]
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

    #[Route("/hub/queue-workers/{id:[^/]+}", "PUT")]
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

    #[Route("/hub/queue-workers/{id:[^/]+}", "DELETE")]
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

    #[Route("/hub/queue-workers/{id:[^/]+}")]
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

    #[Route("/hub/queue-workers/{id:[^/]+}/start", "POST")]
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

    #[Route("/hub/queue-workers/{id:[^/]+}/stop", "POST")]
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

    #[Route("/hub/queue-workers/{id:[^/]+}/output")]
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

    #[Route("/hub/queue-workers/{id:[^/]+}/output", "DELETE")]
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
