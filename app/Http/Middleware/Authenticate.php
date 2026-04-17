<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    public function handle($request, \Closure $next, ...$guards)
    {
        $authorizationHeader = $request->header('Authorization');
        $tokenHeader = $request->header('Token');
        $cookieToken = $request->cookie('sanctum');

        if (empty($authorizationHeader)) {
            $rawToken = $tokenHeader ?: $cookieToken;
            $xAuthToken = $request->header('X-Auth-Token');

            if (!empty($rawToken)) {
                $normalizedToken = preg_match('/^Bearer\s+/i', $rawToken)
                    ? $rawToken
                    : 'Bearer ' . $rawToken;

                $request->headers->set('Authorization', $normalizedToken);
            }

            if ($xAuthToken) {
                // Strip "Bearer " prefix if present, then re-set cleanly
                $token = str_starts_with($xAuthToken, 'Bearer ')
                    ? $xAuthToken
                    : 'Bearer ' . $xAuthToken;

                $request->headers->set('Authorization', $token);

                Log::info('Auth remapped from X-Auth-Token', [
                    'token_preview' => substr($token, 0, 20) . '...'
                ]);
            }
        }


        $this->authenticate($request, $guards);

        return $next($request);
    }

//    public function handle($request, \Closure $next, ...$guards)
//    {
//        if ($request->cookie('sanctum')) {
//            $request->headers->set('Authorization', 'Bearer ' . $request->cookie('sanctum'));
//        }
//
//        $this->authenticate($request, $guards);
//
//        return $next($request);
//    }
}
