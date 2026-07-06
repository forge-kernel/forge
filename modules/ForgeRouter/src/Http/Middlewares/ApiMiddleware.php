<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;
use SimpleXMLElement;

#[Middleware(group: 'api', order: 2, allowDuplicate: true, enabled: true)]
class ApiMiddleware extends MiddlewareImpl
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        $response->setHeader('X-API-Version', '1.0.0')
            ->setHeader('X-Content-Type-Options', 'nosniff');

        $accept = $request->getHeader('Accept');
        if (str_contains($accept, 'application/xml')) {
            return $this->convertToXml($response);
        } elseif (str_contains($accept, 'text/csv')) {
            return $this->convertToCsv($response);
        } elseif (str_contains($accept, 'text/html')) {
            return $this->convertToHtml($response);
        } elseif (str_contains($accept, 'text/plain')) {
            return $this->convertToText($response);
        }

        return $response;
    }

    private function convertToXml(Response $response): Response
    {
        $data = json_decode($response->getContent(), true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return $response;
        }
        $xml = new SimpleXMLElement('<root/>');
        $this->arrayToXml($data, $xml);
        return new Response($xml->asXML(), $response->getStatusCode(), ['Content-Type' => 'application/xml']);
    }

    private function arrayToXml(array $data, SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $subnode = $xml->addChild('item');
                    $subnode->addAttribute('index', (string) $key);
                } else {
                    $subnode = $xml->addChild($key);
                }
                $this->arrayToXml($value, $subnode);
            } else {
                if (is_numeric($key)) {
                    $child = $xml->addChild('item', htmlspecialchars((string) $value));
                    $child->addAttribute('index', (string) $key);
                } else {
                    $xml->addChild($key, htmlspecialchars((string) $value));
                }
            }
        }
    }

    private function convertToCsv(Response $response): Response
    {
        $data = json_decode($response->getContent(), true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return $response;
        }
        if (!isset($data['data']) || !is_array($data['data'])) {
            return new Response('No data available', $response->getStatusCode(), ['Content-Type' => 'text/csv']);
        }
        $csv = $this->arrayToCsv($data['data']);
        return new Response($csv, $response->getStatusCode(), [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="export.csv"'
        ]);
    }

    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        $headers = [];

        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
            break;
        }

        fputcsv($output, $headers, ',', '"', '\\');

        foreach ($data as $row) {
            $rowData = [];
            foreach ($headers as $header) {
                if (isset($row[$header])) {
                    if (is_array($row[$header])) {
                        $rowData[] = json_encode($row[$header]);
                    } else {
                        $rowData[] = $row[$header];
                    }
                } else {
                    $rowData[] = '';
                }
            }
            fputcsv($output, $rowData, ',', '"', '\\');
        }

        rewind($output);
        return stream_get_contents($output);
    }

    private function convertToHtml(Response $response): Response
    {
        $data = json_decode($response->getContent(), true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return clone $response->setHeader('Content-Type', 'text/html');
        }
        if (empty($data['data']) || !is_array($data['data'])) {
            return new Response('No data available', $response->getStatusCode(), ['Content-Type' => 'text/html']);
        }
        $html = $this->arrayToHtml($data['data']);
        return new Response($html, $response->getStatusCode(), ['Content-Type' => 'text/html']);
    }

    private function arrayToHtml(array $data): string
    {
        $html = '<table border="1"><thead><tr>';
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $key) {
                $html .= "<th>$key</th>";
            }
            $html .= '</tr></thead><tbody>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    if (is_array($cell)) {
                        $html .= "<td>" . json_encode($cell) . "</td>";
                    } else {
                        $html .= "<td>$cell</td>";
                    }
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        return $html;
    }

    private function convertToText(Response $response): Response
    {
        $data = json_decode($response->getContent(), true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return clone $response->setHeader('Content-Type', 'text/plain');
        }
        if (!isset($data['data']) || !is_array($data['data'])) {
            return new Response('No data available', $response->getStatusCode(), ['Content-Type' => 'text/plain']);
        }
        $text = $this->arrayToText($data['data']);
        return new Response($text, $response->getStatusCode(), ['Content-Type' => 'text/plain']);
    }

    private function arrayToText(array $data): string
    {
        $text = '';
        foreach ($data as $row) {
            $rowData = [];
            foreach ($row as $cell) {
                if (is_array($cell)) {
                    $rowData[] = json_encode($cell);
                } else {
                    $rowData[] = $cell;
                }
            }
            $text .= implode("\t", $rowData) . "\n";
        }
        return $text;
    }
}
