<?php

declare(strict_types=1);

use Modules\ForgeSqlOrm\Helpers\PaginationHelper;

if (!function_exists('pagination')) {
    function pagination(\Modules\ForgeSqlOrm\ORM\Paginator $paginator, array $options = []): string
    {
        return PaginationHelper::render($paginator, $options);
    }
}

if (!function_exists('pagination_info')) {
    function pagination_info(\Modules\ForgeSqlOrm\ORM\Paginator $paginator): string
    {
        return PaginationHelper::info($paginator);
    }
}

if (!function_exists('paginate')) {
    function paginate(\Modules\ForgeSqlOrm\ORM\Paginator $paginator, array $options = []): string
    {
        return pagination($paginator, $options);
    }
}
