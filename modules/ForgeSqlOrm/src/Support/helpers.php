<?php

declare(strict_types=1);

use App\Modules\ForgeSqlOrm\Helpers\PaginationHelper;

if (!function_exists('pagination')) {
    function pagination(\App\Modules\ForgeSqlOrm\ORM\Paginator $paginator, array $options = []): string
    {
        return PaginationHelper::render($paginator, $options);
    }
}

if (!function_exists('pagination_info')) {
    function pagination_info(\App\Modules\ForgeSqlOrm\ORM\Paginator $paginator): string
    {
        return PaginationHelper::info($paginator);
    }
}

if (!function_exists('paginate')) {
    function paginate(\App\Modules\ForgeSqlOrm\ORM\Paginator $paginator, array $options = []): string
    {
        return pagination($paginator, $options);
    }
}
