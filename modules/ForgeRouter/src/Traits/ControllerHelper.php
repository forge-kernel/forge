<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Traits;

use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\ApiResponse;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Router;
use Forge\Core\Contracts\ViewInterface;
use ReflectionClass;

/**
 * The ControllerHelper trait is your toolbox for controllers,
 * it provides several usefuls methods to make common tasks easier.
 */
trait ControllerHelper
{
    /**
     * This method helps send data back to the client in JSON format.
     * JSON is a common format for sending data over the web.
     * You just give it the data and a status code (like 200 for success),
     * and it takes care of the rest.
     *
     * @param array $data
     * @param int $statusCode
     *
     * @return Response
     */
    protected function jsonResponse(
        array $data,
        int $statusCode = 200
    ): Response {
        $jsonData = json_encode($data);
        return (new Response($jsonData, $statusCode))->setHeader(
            "Content-Type",
            "application/json"
        );
    }

    /**
     * This method helps render a view, which is basically a template for
     * how the data should be displayed. You tell it which view to use
     * and what data to pass to it, and it renders the view with the data.
     *
     * @param string $view The view file path (relative to views directory).
     * @param array<string, mixed> $data Data to pass to the view.
     * @return Response Returns the rendered view content.
     */
    protected function view(string $view, array $data = [], ?string $layout = null): Response
    {
        if ($layout === null) {
            try {
                $route = Router::getInstance()->getCurrentRoute();
                $layout = $route["layout"] ?? null;
            } catch (\Throwable) {
                $layout = null;
            }
        }

        $module = $this->detectModule();

        $viewName = $module ? "{$module}:{$view}" : $view;
        $content = Container::getInstance()
            ->get(ViewInterface::class)
            ->render($viewName, $data, $layout);
        return new Response($content);
    }

    /**
     * This is a helper method that figures out which module the current controller
     * belongs to. Modules are like separate sections of the application.
     */
    private function detectModule(): ?string
    {
        $namespaceParts = explode(
            "\\",
            (new ReflectionClass($this))->getNamespaceName()
        );
        return ($namespaceParts[1] ?? null) === "Modules"
            ? $namespaceParts[2]
            : null;
    }

    /**
     * his method helps send a response in a format that's commonly used for APIs.
     * You give it the data, a status code, and any headers, and it sends the response.
     */
    protected function apiResponse(
        mixed $data,
        int $statusCode = 200,
        array $headers = []
    ): ApiResponse {
        return new ApiResponse($data, $statusCode, $headers);
    }

    /**
     * This method helps send an error response in a format that's commonly used for APIs.
     * You give it an error message, a status code, any additional errors, and an error code,
     * and it sends the error response.
     */
    protected function apiError(
        string $message,
        int $statusCode = 400,
        array $errors = [],
        string $code = "ERROR_CODE"
    ): ApiResponse {
        return new ApiResponse(
            null,
            $statusCode,
            [],
            [
                "error" => [
                    "code" => $code,
                    "message" => $message,
                    "errors" => $errors,
                ],
            ]
        );
    }

    /**
     * This method helps send data back to the client in CSV format. CSV is a common format
     * for sending tabular data. You give it the data and a filename, and it sends the data as a CSV file.
     */
    protected function csvResponse(
        array $data,
        string $filename = "export.csv"
    ): Response {
        $csv = $this->arraryToCsv($data);
        return (new Response($csv))
            ->setHeader("Content-Type", "text/csv")
            ->setHeader(
                "Content-Dispostion",
                "attachment; filename=\"$filename\""
            );
    }

    /**
     * This is a helper method that converts an array of data into CSV format.
     */
    private function arraryToCsv(array $data): string
    {
        $output = fopen("php://temp", "r+");
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        rewind($output);
        return stream_get_contents($output);
    }
}
