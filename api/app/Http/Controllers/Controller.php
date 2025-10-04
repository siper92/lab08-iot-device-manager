<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    public function getPageLimit(Request $request): int
    {
        return max(
            min(
                $request->integer(\Pagination::PAGE_LIMIT_KEY, $this->getDefaultPageLimit()),
                \Pagination::MAX_PER_PAGE
            ), 1
        );
    }

    public function getPage(Request $request): int
    {
        return max($request->integer('page', 1), 1);
    }

    public function getDefaultPageLimit(): int
    {
        return (int)config('app_custom.page_limit', \Pagination::DEFAULT_PER_PAGE);
    }
}
