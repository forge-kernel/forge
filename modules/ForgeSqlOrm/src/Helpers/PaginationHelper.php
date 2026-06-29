<?php

declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Helpers;

use Modules\ForgeSqlOrm\ORM\Paginator;

class PaginationHelper
{
  public static function render(Paginator $paginator, array $options = []): string
  {
    $class = $options['class'] ?? 'pagination';
    $itemClass = $options['itemClass'] ?? 'page-item';
    $linkClass = $options['linkClass'] ?? 'page-link';
    $activeClass = $options['activeClass'] ?? 'active';
    $disabledClass = $options['disabledClass'] ?? 'disabled';

    $html = '<style>
      .pagination { display: flex; list-style: none; padding: 0; gap: 0.5rem; flex-wrap: wrap; margin: 1rem 0; }
      .page-item { margin: 0; }
      .page-link { display: block; padding: 0.5rem 1rem; text-decoration: none; border: 1px solid #ddd; border-radius: 0.25rem; color: #333; background: #fff; transition: all 0.2s; }
      .page-link:hover:not(.disabled-link) { background: #f5f5f5; }
      .page-item.active .page-link { background: #007bff; color: #fff; border-color: #007bff; font-weight: bold; }
      .page-item.disabled .page-link, .disabled-link { pointer-events: none; opacity: 0.5; color: #999; background: #f5f5f5; cursor: not-allowed; }
    </style>';

    $html .= "<nav aria-label=\"Page navigation\"><ul class=\"{$class}\">";

    $firstUrl = $paginator->firstPageUrl();
    $disabled = $firstUrl === null ? $disabledClass : '';
    $html .= "<li class=\"{$itemClass} {$disabled}\">";
    $html .= $firstUrl
      ? "<a href=\"" . htmlspecialchars($firstUrl) . "\" class=\"{$linkClass}\">First</a>"
      : "<span class=\"{$linkClass} disabled-link\">First</span>";
    $html .= "</li>";

    $prevUrl = $paginator->previousPageUrl();
    $disabled = $prevUrl === null ? $disabledClass : '';
    $html .= "<li class=\"{$itemClass} {$disabled}\">";
    $html .= $prevUrl
      ? "<a href=\"" . htmlspecialchars($prevUrl) . "\" class=\"{$linkClass}\">Previous</a>"
      : "<span class=\"{$linkClass} disabled-link\">Previous</span>";
    $html .= "</li>";

    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $showPages = $options['showPages'] ?? 5;

    $start = max(1, $currentPage - floor($showPages / 2));
    $end = min($lastPage, $start + $showPages - 1);
    $start = max(1, $end - $showPages + 1);

    if ($start > 1) {
      $html .= "<li class=\"{$itemClass}\"><a href=\"" . htmlspecialchars($paginator->url(1)) . "\" class=\"{$linkClass}\">1</a></li>";
      if ($start > 2) {
        $html .= "<li class=\"{$itemClass} {$disabledClass}\"><span class=\"{$linkClass} disabled-link\">...</span></li>";
      }
    }

    for ($i = $start; $i <= $end; $i++) {
      $active = $i === $currentPage ? $activeClass : '';
      $html .= "<li class=\"{$itemClass} {$active}\">";
      $html .= "<a href=\"" . htmlspecialchars($paginator->url($i)) . "\" class=\"{$linkClass}\">{$i}</a>";
      $html .= "</li>";
    }

    if ($end < $lastPage) {
      if ($end < $lastPage - 1) {
        $html .= "<li class=\"{$itemClass} {$disabledClass}\"><span class=\"{$linkClass} disabled-link\">...</span></li>";
      }
      $html .= "<li class=\"{$itemClass}\"><a href=\"" . htmlspecialchars($paginator->url($lastPage)) . "\" class=\"{$linkClass}\">{$lastPage}</a></li>";
    }

    $nextUrl = $paginator->nextPageUrl();
    $disabled = $nextUrl === null ? $disabledClass : '';
    $html .= "<li class=\"{$itemClass} {$disabled}\">";
    $html .= $nextUrl
      ? "<a href=\"" . htmlspecialchars($nextUrl) . "\" class=\"{$linkClass}\">Next</a>"
      : "<span class=\"{$linkClass} disabled-link\">Next</span>";
    $html .= "</li>";

    $lastUrl = $paginator->lastPageUrl();
    $disabled = $lastUrl === null ? $disabledClass : '';
    $html .= "<li class=\"{$itemClass} {$disabled}\">";
    $html .= $lastUrl
      ? "<a href=\"" . htmlspecialchars($lastUrl) . "\" class=\"{$linkClass}\">Last</a>"
      : "<span class=\"{$linkClass} disabled-link\">Last</span>";
    $html .= "</li>";

    $html .= "</ul></nav>";

    return $html;
  }

  public static function info(Paginator $paginator): string
  {
    $from = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;
    $to = min($paginator->currentPage() * $paginator->perPage(), $paginator->total());
    $total = $paginator->total();

    if ($total === 0) {
      return "No results found";
    }

    return "Showing {$from}-{$to} of {$total} results";
  }

  public static function json(Paginator $paginator): string
  {
    return json_encode($paginator->toArray(), JSON_PRETTY_PRINT);
  }
}
