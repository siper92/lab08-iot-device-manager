<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtUserMiddleware
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token || !$this->jwtService->validateToken($token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $this->jwtService->getPayload($token);

        if (!$payload || $payload->type !== 'user') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $request->route('userId');
        if ($userId && $payload->sub != $userId) {
            return response()->json(['error' => 'forbidden...'], 403);
        }

        $request->attributes->set('user_id', $payload->sub);
        if (isset($payload->devices)) {
            $request->attributes->set('user_devices', $payload->devices);
        }

        return $next($request);
    }
}
