<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Routing;

final class RadixTree
{
    private ?RadixNode $root = null;
    private int $routeCount = 0;

    public function __construct()
    {
        $this->root = new RadixNode('');
    }

    public function add(string $path, array $routeData): void
    {
        $segments = $this->parseSegments($path);
        $node = $this->root;

        foreach ($segments as $segment) {
            $segmentKey = $this->getSegmentKey($segment);

            if (!isset($node->children[$segmentKey])) {
                $node->children[$segmentKey] = new RadixNode($segmentKey);
            }

            $child = $node->children[$segmentKey];

            if ($this->isParamSegment($segment)) {
                $child->paramName = $this->extractParamName($segment);
                $child->paramConstraint = $this->extractConstraint($segment);
            }

            $node = $child;
        }

        $node->routeData = $routeData;
        $node->hasRoute = true;
        $this->routeCount++;
    }

    public function find(string $path): ?array
    {
        $segments = $this->parseSegments($path);
        $segmentCount = count($segments);
        $node = $this->root;
        $paramValues = [];
        $lastMatchIndex = -1;
        $matchedRoute = null;
        $matchedParams = [];

        foreach ($segments as $index => $segment) {
            $segmentKey = $segment === '' ? '' : $segment;
            $found = false;
            $atParam = false;

            if (isset($node->children[$segmentKey])) {
                $node = $node->children[$segmentKey];
                $found = true;
            } elseif (!empty($segmentKey) && isset($node->children[':param'])) {
                $paramChild = $node->children[':param'];
                if ($this->matchConstraint($segment, $paramChild->paramConstraint)) {
                    $paramValues[$paramChild->paramName] = $segment;
                    $node = $paramChild;
                    $found = true;
                    $atParam = true;
                }
            }

            if (!$found) {
                if ($matchedRoute !== null) {
                    break;
                }
                return null;
            }

            if ($node->hasRoute) {
                $lastMatchIndex = $index;
                $matchedRoute = $node;
                $matchedParams = $paramValues;
            }

            if ($atParam && $node->hasRoute && $index < $segmentCount - 1) {
                $longerMatch = $this->findLongerRoute($node, $segments, $index + 1, $segmentCount, $paramValues);
                if ($longerMatch !== null) {
                    $matchedRoute = $longerMatch['node'];
                    $matchedParams = $longerMatch['params'];
                    $lastMatchIndex = $longerMatch['lastIndex'];
                    $paramValues = $longerMatch['params'];
                }
            }
        }

        if ($matchedRoute === null) {
            $matchedRoute = $this->findRouteWithTrailingSlash($node, $segments);
            if ($matchedRoute === null) {
                return null;
            }
            $matchedParams = $paramValues;
        }

        $result = $matchedRoute->routeData;
        if (!empty($matchedParams)) {
            $result['params'] = array_merge($result['params'] ?? [], $matchedParams);
        }

        return $result;
    }

    private function findLongerRoute(RadixNode $node, array $segments, int $startIndex, int $segmentCount, array $baseParams): ?array
    {
        $paramValues = $baseParams;
        $currentNode = $node;
        $lastIndex = $startIndex - 1;

        for ($i = $startIndex; $i < $segmentCount; $i++) {
            $segment = $segments[$i];
            $segmentKey = $segment === '' ? '' : $segment;
            $found = false;

            if (isset($currentNode->children[$segmentKey])) {
                $currentNode = $currentNode->children[$segmentKey];
                $found = true;
                if (!$currentNode->hasRoute && isset($currentNode->children[':param']) && $i < $segmentCount - 1) {
                    continue;
                }
            } elseif (!empty($segmentKey) && isset($currentNode->children[':param'])) {
                $paramChild = $currentNode->children[':param'];
                if ($this->matchConstraint($segment, $paramChild->paramConstraint)) {
                    $paramValues[$paramChild->paramName] = $segment;
                    $currentNode = $paramChild;
                    $found = true;
                }
            }

            if (!$found) {
                return null;
            }

            if ($currentNode->hasRoute) {
                $lastIndex = $i;
            }
        }

        if (!$currentNode->hasRoute) {
            return null;
        }

        return [
            'node' => $currentNode,
            'params' => $paramValues,
            'lastIndex' => $lastIndex,
        ];
    }

    private function findRouteWithTrailingSlash(RadixNode $node, array $segments): ?RadixNode
    {
        if (empty($segments) || end($segments) !== '') {
            return null;
        }

        $trailingSegments = array_slice($segments, 0, -1);
        $tempNode = $this->root;

        foreach ($trailingSegments as $segment) {
            $segmentKey = $segment === '' ? '' : $segment;

            if (isset($tempNode->children[$segmentKey])) {
                $tempNode = $tempNode->children[$segmentKey];
            } elseif (!empty($segmentKey) && isset($tempNode->children[':param'])) {
                $paramChild = $tempNode->children[':param'];
                if (!$this->matchConstraint($segment, $paramChild->paramConstraint)) {
                    return null;
                }
                $tempNode = $paramChild;
            } else {
                return null;
            }
        }

        return $tempNode->hasRoute ? $tempNode : null;
    }

    private function parseSegments(string $path): array
    {
        $path = trim($path, '/');
        if ($path === '') {
            return [''];
        }
        return explode('/', $path);
    }

    private function getSegmentKey(string $segment): string
    {
        if ($segment === '' || $segment === null) {
            return '';
        }
        return $this->isParamSegment($segment) ? ':param' : $segment;
    }

    private function isParamSegment(string $segment): bool
    {
        return str_starts_with($segment, '{') && str_ends_with($segment, '}');
    }

    private function extractParamName(string $segment): string
    {
        if (preg_match('/^\{([a-zA-Z0-9_]+)(?::(.+))?\}$/', $segment, $matches)) {
            return $matches[1];
        }
        return ltrim($segment, '{}');
    }

    private function extractConstraint(string $segment): ?string
    {
        if (preg_match('/^\{[a-zA-Z0-9_]+:(.+)\}$/', $segment, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function matchConstraint(string $value, ?string $constraint): bool
    {
        if ($constraint === null) {
            return true;
        }
        if ($constraint === '.+') {
            return $value !== '';
        }
        if ($constraint === '[^/]+' || $constraint === 'no-slash') {
            return strpos($value, '/') === false;
        }
        return (bool) preg_match("/^{$constraint}$/", $value);
    }

    public function count(): int
    {
        return $this->routeCount;
    }
}

final class RadixNode
{
    public string $key;
    public ?string $paramName = null;
    public ?string $paramConstraint = null;
    public bool $hasRoute = false;
    /** @var array<string, RadixNode> */
    public array $children = [];
    public ?array $routeData = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }
}