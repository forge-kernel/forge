<?php

namespace App\Modules\ForgeDebugBar\Collectors;

use Forge\Core\DI\Container;
use Forge\Core\Session\SessionInterface;

class SessionCollector
{
    public static function collect(...$args): array
    {
        return self::instance()->collectSessionData();
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    public function collectSessionData(): array
    {
        $container = Container::getInstance();
        if (!$container->has(SessionInterface::class)) {
            return ['status' => 'Session not available'];
        }

        /** @var SessionInterface $session */
        $session = $container->get(SessionInterface::class);

        if (!$session->isStarted()) {
            return ['status' => 'Session not started'];
        }

        $sessionData = [];
        if (isset($_SESSION) && is_array($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                $sessionData[$key] = $this->formatValue($value);
            }
        }

        return [
            'session_id' => $session->getId(),
            'data' => $sessionData,
            'count' => count($sessionData)
        ];
    }

    private function formatValue($value): string
    {
        if (is_object($value)) {
            return 'Object (' . get_class($value) . ')';
        } elseif (is_array($value)) {
            return 'Array (' . count($value) . ' items)';
        } elseif (is_resource($value)) {
            return 'Resource (' . get_resource_type($value) . ')';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return (string) $value;
        }
    }
}
