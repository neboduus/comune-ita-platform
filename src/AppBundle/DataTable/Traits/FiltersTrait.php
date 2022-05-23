<?php

namespace AppBundle\DataTable\Traits;

use Symfony\Component\HttpFoundation\Request;

trait FiltersTrait
{
  private static function getFiltersFromRequest(Request $request): array
  {
    $filters = $request->query->all();

    if (!isset($filters['filters'])) {
      parse_str($request->request->get('filters', ''), $filters);
    }

    return $filters;
  }
}
