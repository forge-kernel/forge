<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Controllers\Hub;

use App\Modules\ForgeDeployment\Services\DeploymentHubService;
use App\Modules\ForgeDeployment\Services\DeploymentExecutionService;
use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use Forge\Core\Config\Config;
use Forge\Core\Config\Environment;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/hub/deployment')]
#[UseMiddleware(["web", "auth", "hub-permissions"])]
final class DeploymentController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly DeploymentHubService $deploymentHubService,
        private readonly DeploymentExecutionService $executionService,
        private readonly DeploymentConfigReader $configReader,
        private readonly Config $config,
    ) {
    }

    #[Endpoint]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $status = $this->deploymentHubService->getDeploymentStatus();
        $config = $this->deploymentHubService->getDeploymentConfig();
        $hasConfig = $this->deploymentHubService->hasConfig();
        $logs = $this->deploymentHubService->listDeploymentLogs();
        $phpInfo = $this->executionService->getPhpInfo();
        $isProduction =
            Environment::getInstance()->get("APP_ENV") === "production";

        $data = [
            "title" => "Deployment",
            "status" => $status,
            "config" => $config,
            "has_config" => $hasConfig,
            "config_path" => $this->deploymentHubService->getConfigPath(),
            "recent_logs" => array_slice($logs, 0, 10),
            "php_info" => $phpInfo,
            "is_production" => $isProduction,
        ];

        return $this->view(view: "hub/deployment", data: $data);
    }

    #[Endpoint("/status", "GET")]
    public function getStatus(Request $request): Response
    {
        $status = $this->deploymentHubService->getDeploymentStatus();

        return $this->jsonResponse([
            "success" => true,
            "status" => $status,
        ]);
    }

    #[Endpoint("/config", "GET")]
    public function getConfig(Request $request): Response
    {
        $config = $this->deploymentHubService->getDeploymentConfig();

        return $this->jsonResponse([
            "success" => true,
            "config" => $config,
            "has_config" => $this->deploymentHubService->hasConfig(),
            "config_path" => $this->deploymentHubService->getConfigPath(),
        ]);
    }

    #[Endpoint("/config", "POST")]
    public function saveConfig(Request $request): Response
    {
        $data = $request->json();

        if (empty($data)) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "No data received",
                ],
                400,
            );
        }

        $updateType = $data["update"] ?? null;

        if ($updateType === "post_deployment_commands") {
            $commands = $data["post_deployment_commands"] ?? [];
            if (!is_array($commands)) {
                return $this->jsonResponse(
                    [
                        "success" => false,
                        "message" => "Invalid commands data",
                    ],
                    400,
                );
            }

            $currentConfig = $this->deploymentHubService->getRawDeploymentConfig();
            if ($currentConfig === null) {
                $currentConfig = [
                    "server" => [],
                    "provision" => [],
                    "deployment" => [],
                ];
            }
            if (!isset($currentConfig["server"])) {
                $currentConfig["server"] = [];
            }
            if (!isset($currentConfig["provision"])) {
                $currentConfig["provision"] = [];
            }
            if (!isset($currentConfig["deployment"])) {
                $currentConfig["deployment"] = [];
            }
            $currentConfig["deployment"][
                "post_deployment_commands"
            ] = $commands;

            try {
                $result = $this->deploymentHubService->saveDeploymentConfig(
                    $currentConfig,
                );
                if (!$result) {
                    $configPath =
                        $this->deploymentHubService->getConfigPath() ??
                        BASE_PATH . "/forge-deployment.php";
                    return $this->jsonResponse(
                        [
                            "success" => false,
                            "message" =>
                                "Failed to save commands - check file permissions. Path: " .
                                $configPath,
                        ],
                        500,
                    );
                }
            } catch (\Exception $e) {
                return $this->jsonResponse(
                    [
                        "success" => false,
                        "message" =>
                            "Error saving commands: " . $e->getMessage(),
                    ],
                    500,
                );
            }

            return $this->jsonResponse([
                "success" => true,
                "message" => "Post-deployment commands saved successfully",
                "commands_count" => count($commands),
            ]);
        }

        if ($updateType === "env_vars") {
            $envVars = $data["env_vars"] ?? [];
            if (!is_array($envVars)) {
                return $this->jsonResponse(
                    [
                        "success" => false,
                        "message" => "Invalid environment variables data",
                    ],
                    400,
                );
            }

            $currentConfig = $this->deploymentHubService->getRawDeploymentConfig();
            if ($currentConfig === null) {
                $currentConfig = [
                    "server" => [],
                    "provision" => [],
                    "deployment" => [],
                ];
            }
            if (!isset($currentConfig["server"])) {
                $currentConfig["server"] = [];
            }
            if (!isset($currentConfig["provision"])) {
                $currentConfig["provision"] = [];
            }
            if (!isset($currentConfig["deployment"])) {
                $currentConfig["deployment"] = [];
            }
            $currentConfig["deployment"]["env_vars"] = $envVars;

            try {
                $result = $this->deploymentHubService->saveDeploymentConfig(
                    $currentConfig,
                );
                if (!$result) {
                    $configPath =
                        $this->deploymentHubService->getConfigPath() ??
                        BASE_PATH . "/forge-deployment.php";
                    return $this->jsonResponse(
                        [
                            "success" => false,
                            "message" =>
                                "Failed to save environment variables - check file permissions. Path: " .
                                $configPath,
                        ],
                        500,
                    );
                }
            } catch (\Exception $e) {
                return $this->jsonResponse(
                    [
                        "success" => false,
                        "message" =>
                            "Error saving environment variables: " .
                            $e->getMessage(),
                    ],
                    500,
                );
            }

            return $this->jsonResponse([
                "success" => true,
                "message" => "Environment variables saved successfully",
                "vars_count" => count($envVars),
            ]);
        }

        $config = $data["config"] ?? null;

        if ($config === null || !is_array($config)) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "Invalid configuration data",
                ],
                400,
            );
        }

        $existingConfig =
            $this->deploymentHubService->getRawDeploymentConfig() ?? [];

        $mergedConfig = [
            "server" => array_merge(
                $existingConfig["server"] ?? [],
                $config["server"] ?? [],
            ),
            "provision" => array_merge(
                $existingConfig["provision"] ?? [],
                $config["provision"] ?? [],
            ),
            "deployment" => array_merge(
                $existingConfig["deployment"] ?? [],
                $config["deployment"] ?? [],
            ),
        ];

        $errors = $this->deploymentHubService->validateConfig($mergedConfig);
        if (!empty($errors)) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "Configuration validation failed",
                    "errors" => $errors,
                ],
                400,
            );
        }

        $result = $this->deploymentHubService->saveDeploymentConfig(
            $mergedConfig,
        );
        if (!$result) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "Failed to save configuration file",
                ],
                500,
            );
        }

        return $this->jsonResponse([
            "success" => true,
            "message" => "Configuration saved successfully",
        ]);
    }

    #[Endpoint("/deploy", "POST")]
    public function deploy(Request $request): Response
    {
        $data = $request->json();
        $args = $data["args"] ?? [];

        $result = $this->executionService->executeDeployment(
            "modules:forge-deployment:deploy",
            $args,
        );

        return $this->jsonResponse(
            [
                "success" => $result["success"],
                "deployment_id" => $result["deployment_id"],
                "message" => $result["success"]
                    ? "Deployment started successfully"
                    : "Deployment failed",
                "output" => $result["output"] ?? "",
            ],
            $result["success"] ? 200 : 500,
        );
    }

    #[Endpoint("/deploy-app", "POST")]
    public function deployApp(Request $request): Response
    {
        $data = $request->json();
        $args = $data["args"] ?? [];

        $result = $this->executionService->executeDeployment(
            "modules:forge-deployment:deploy-app",
            $args,
        );

        return $this->jsonResponse(
            [
                "success" => $result["success"],
                "deployment_id" => $result["deployment_id"],
                "message" => $result["success"]
                    ? "Application deployment started successfully"
                    : "Application deployment failed",
                "output" => $result["output"] ?? "",
            ],
            $result["success"] ? 200 : 500,
        );
    }

    #[Endpoint("/update", "POST")]
    public function update(Request $request): Response
    {
        $data = $request->json();
        $args = $data["args"] ?? [];

        $result = $this->executionService->executeDeployment(
            "modules:forge-deployment:update",
            $args,
        );

        return $this->jsonResponse(
            [
                "success" => $result["success"],
                "deployment_id" => $result["deployment_id"],
                "message" => $result["success"]
                    ? "Update deployment started successfully"
                    : "Update deployment failed",
                "output" => $result["output"] ?? "",
            ],
            $result["success"] ? 200 : 500,
        );
    }

    #[Endpoint("/rollback", "POST")]
    public function rollback(Request $request): Response
    {
        $data = $request->json();
        $args = $data["args"] ?? [];

        $result = $this->executionService->executeDeployment(
            "modules:forge-deployment:rollback",
            $args,
        );

        return $this->jsonResponse(
            [
                "success" => $result["success"],
                "deployment_id" => $result["deployment_id"],
                "message" => $result["success"]
                    ? "Rollback started successfully"
                    : "Rollback failed",
                "output" => $result["output"] ?? "",
            ],
            $result["success"] ? 200 : 500,
        );
    }

    #[Endpoint("/deploy-env", "POST")]
    public function deployEnv(Request $request): Response
    {
        $data = $request->json();
        $args = $data["args"] ?? [];

        $result = $this->executionService->executeDeployment(
            "modules:forge-deployment:deploy-env",
            $args,
        );

        return $this->jsonResponse(
            [
                "success" => $result["success"],
                "deployment_id" => $result["deployment_id"],
                "message" => $result["success"]
                    ? "Environment file deployment started successfully"
                    : "Environment file deployment failed",
                "output" => $result["output"] ?? "",
            ],
            $result["success"] ? 200 : 500,
        );
    }

    #[Endpoint("/delete-server", "POST")]
    public function deleteServer(Request $request): Response
    {
        $data = $request->json();
        $args = $data["args"] ?? ["--skip-confirmation"];

        $result = $this->executionService->executeDeployment(
            "modules:forge-deployment:delete-server",
            $args,
        );

        return $this->jsonResponse(
            [
                "success" => $result["success"],
                "deployment_id" => $result["deployment_id"],
                "message" => $result["success"]
                    ? "Server deletion started successfully"
                    : "Server deletion failed",
                "output" => $result["output"] ?? "",
            ],
            $result["success"] ? 200 : 500,
        );
    }

    #[Endpoint("/logs/{deploymentId}", "GET")]
    public function getLogs(Request $request, string $deploymentId): Response
    {
        $logs = $this->executionService->getDeploymentLog($deploymentId);

        if ($logs === null) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "Deployment logs not found",
                ],
                404,
            );
        }

        $cleanLogs = preg_replace('/\x1b\[[0-9;]*m/', "", $logs);

        return $this->jsonResponse([
            "success" => true,
            "logs" => $cleanLogs,
            "deployment_id" => $deploymentId,
        ]);
    }

    #[Endpoint("/secrets", "GET")]
    public function getSecrets(Request $request): Response
    {
        $secrets = [
            "digitalocean_api_token" => $this->maskSecret(
                $this->config->get(
                    "forge_deployment.digitalocean.api_token",
                    "",
                ),
            ),
            "cloudflare_api_token" => $this->maskSecret(
                $this->config->get("forge_deployment.cloudflare.api_token", ""),
            ),
        ];

        return $this->jsonResponse([
            "success" => true,
            "secrets" => $secrets,
        ]);
    }

    #[Endpoint("/secrets", "POST")]
    public function updateSecrets(Request $request): Response
    {
        $data = $request->json();
        $secrets = $data["secrets"] ?? [];

        if (isset($secrets["digitalocean_api_token"])) {
            $token = $secrets["digitalocean_api_token"];
            if ($token !== "••••••••" && !empty($token)) {
                $this->config->set(
                    "forge_deployment.digitalocean.api_token",
                    $token,
                );
                putenv("FORGE_DEPLOYMENT_DIGITALOCEAN_API_TOKEN=" . $token);
            }
        }

        if (isset($secrets["cloudflare_api_token"])) {
            $token = $secrets["cloudflare_api_token"];
            if ($token !== "••••••••" && !empty($token)) {
                $this->config->set(
                    "forge_deployment.cloudflare.api_token",
                    $token,
                );
                putenv("FORGE_DEPLOYMENT_CLOUDFLARE_API_TOKEN=" . $token);
            }
        }

        return $this->jsonResponse([
            "success" => true,
            "message" => "Secrets updated successfully",
        ]);
    }

    private function maskSecret(?string $value): string
    {
        if ($value === null || $value === "") {
            return "";
        }

        return "••••••••";
    }

    private function generateConfigFile(array $config): string
    {
        $template = $this->configReader->generateConfigTemplate();

        $server = $config["server"] ?? [];
        $provision = $config["provision"] ?? [];
        $deployment = $config["deployment"] ?? [];

        $serverConfig = $this->formatArray($server, "server");
        $provisionConfig = $this->formatArray($provision, "provision");
        $deploymentConfig = $this->formatArray($deployment, "deployment");

        return <<<PHP
        <?php

        declare(strict_types=1);

        return [
        {$serverConfig}
        {$provisionConfig}
        {$deploymentConfig}
        ];
        PHP;
    }

    private function formatArray(array $data, string $key): string
    {
        if (empty($data)) {
            return "    '{$key}' => [],";
        }

        $lines = ["    '{$key}' => ["];

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $subArray = $this->formatSubArray($v, 2);
                $lines[] = "        '{$k}' => {$subArray},";
            } elseif (is_bool($v)) {
                $lines[] = "        '{$k}' => " . ($v ? "true" : "false") . ",";
            } elseif (is_numeric($v)) {
                $lines[] = "        '{$k}' => {$v},";
            } elseif ($v === null) {
                $lines[] = "        '{$k}' => null,";
            } else {
                $escaped = addslashes((string) $v);
                $lines[] = "        '{$k}' => '{$escaped}',";
            }
        }

        $lines[] = "    ],";

        return implode("\n", $lines);
    }

    private function formatSubArray(array $data, int $indent): string
    {
        if (empty($data)) {
            return "[]";
        }

        $spaces = str_repeat(" ", $indent * 4);
        $lines = ["["];

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $subArray = $this->formatSubArray($v, $indent + 1);
                $lines[] = "{$spaces}    '{$k}' => {$subArray},";
            } elseif (is_bool($v)) {
                $lines[] =
                    "{$spaces}    '{$k}' => " . ($v ? "true" : "false") . ",";
            } elseif (is_numeric($v)) {
                $lines[] = "{$spaces}    '{$k}' => {$v},";
            } elseif ($v === null) {
                $lines[] = "{$spaces}    '{$k}' => null,";
            } else {
                $escaped = addslashes((string) $v);
                $lines[] = "{$spaces}    '{$k}' => '{$escaped}',";
            }
        }

        $lines[] = "{$spaces}]";

        return implode("\n", $lines);
    }

    #[Endpoint("/php-binaries", "GET")]
    public function getPhpBinaries(Request $request): Response
    {
        $binaries = $this->executionService->getAvailablePhpBinaries();

        return $this->jsonResponse([
            "success" => true,
            "binaries" => $binaries,
        ]);
    }

    #[Endpoint("/php-executable", "POST")]
    public function setPhpExecutable(Request $request): Response
    {
        $data = $request->json();
        $path = $data["path"] ?? null;

        if ($path === null || !is_string($path)) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "PHP executable path is required",
                ],
                400,
            );
        }

        if (!file_exists($path) || !is_executable($path)) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "PHP executable not found or not executable",
                ],
                400,
            );
        }

        $result = $this->deploymentHubService->setPhpExecutable($path);
        if (!$result) {
            return $this->jsonResponse(
                [
                    "success" => false,
                    "message" => "Failed to save PHP executable preference",
                ],
                500,
            );
        }

        return $this->jsonResponse([
            "success" => true,
            "message" => "PHP executable preference saved successfully",
            "php_info" => $this->executionService->getPhpInfo(),
        ]);
    }
}
