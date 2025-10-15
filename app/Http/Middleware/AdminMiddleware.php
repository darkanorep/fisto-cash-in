<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // if (auth()->check() && auth()->user()->roles->contains('name', Role::ADMIN)) {
        //         return $next($request);
        //     }
        //     return response()->json(['message' => 'Forbidden'], 403);

        if (auth()->check() && Gate::allows('admin')) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
