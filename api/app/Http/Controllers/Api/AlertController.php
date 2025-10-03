<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Get alerts for a user.
     */
    public function getUserAlerts(int $userId, Request $request): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $query = Alert::where('user_id', $userId)
            ->with(['device:id,device_identifier,name', 'measurement'])
            ->orderBy('triggered_at', 'desc');

        // Filter by read status if provided
        if ($request->has('unread_only') && $request->unread_only) {
            $query->unread();
        }

        // Filter by alert type if provided
        if ($request->has('alert_type')) {
            $query->ofType($request->alert_type);
        }

        // Filter by severity if provided
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->paginate(50);

        return response()->json($alerts, 200);
    }

    /**
     * Mark an alert as read.
     */
    public function markAsRead(int $alertId): JsonResponse
    {
        $alert = Alert::find($alertId);

        if (!$alert) {
            return response()->json([
                'message' => 'Alert not found',
            ], 404);
        }

        $alert->markAsRead();

        return response()->json([
            'message' => 'Alert marked as read',
            'data' => $alert,
        ], 200);
    }

    /**
     * Mark all alerts as read for a user.
     */
    public function markAllAsRead(int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $count = Alert::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => "Marked {$count} alerts as read",
            'count' => $count,
        ], 200);
    }

    /**
     * Get alert statistics for a user.
     */
    public function getAlertStats(int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $stats = [
            'total' => Alert::where('user_id', $userId)->count(),
            'unread' => Alert::where('user_id', $userId)->unread()->count(),
            'by_severity' => Alert::where('user_id', $userId)
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'by_type' => Alert::where('user_id', $userId)
                ->selectRaw('alert_type, COUNT(*) as count')
                ->groupBy('alert_type')
                ->pluck('count', 'alert_type'),
        ];

        return response()->json([
            'data' => $stats,
        ], 200);
    }
}
