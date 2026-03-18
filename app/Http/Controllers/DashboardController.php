<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:access dashboard', only: ['summary']),
        ];
    }

    public function __construct(private DashboardRepository $repository) {}

    public function summary()
    {
        return $this->repository->summary();
    }
}
