<?php
if (!function_exists("forgetailwind")) {
    function forgetailwind(): string
    {
        $isHmrEnabled = env("APP_HMR", false);
        $env = env("APP_ENV");
        $host = request_host();

        if (!$isHmrEnabled) {
            return "";
        }

        if (in_array($env, ["production", "staging"], true)) {
            return "";
        }

        if (
            !str_starts_with($host, "localhost") &&
            !str_starts_with($host, "127.0.0.1")
        ) {
            return "";
        }


        return '<script defer src="/assets/modules/forge-tailwind/js/forge-tailwind-hmr.js"></script>';
    }
}