<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Security;

final class InputSanitizer
{
    public static function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitizeArray($value);
            } else {
                $data[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            }
        }
        return $data;
    }

    public static function sanitizeRequest(): void
    {
        $_GET = self::sanitizeArray($_GET);
        $_POST = self::sanitizeArray($_POST);
        $_REQUEST = self::sanitizeArray($_REQUEST);
    }
}
