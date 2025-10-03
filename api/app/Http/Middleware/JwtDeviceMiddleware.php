<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtDeviceMiddleware
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

        if (!$payload || $payload->type !== 'device') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $deviceId = $request->route('deviceId');
        if ($deviceId && $payload->sub != $deviceId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->attributes->set('device_id', $payload->sub);

        return $next($request);
    }
}
