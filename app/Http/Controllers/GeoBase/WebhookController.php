<?php

namespace App\Http\Controllers\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Events\GeoBase\SnapshotGenerated;
use App\Events\GeoBase\SyncProcessed;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $eventType = $request->input('event');
        $data = $request->input('data', []);

        activity('geobase-webhook')
            ->withProperties($data)
            ->log($eventType);

        match ($eventType) {
            'enrollment.status_changed' => EnrollmentStatusChanged::dispatch(
                enrollmentId: $data['enrollment_id'],
                oldStatus: $data['old_status'],
                newStatus: $data['new_status'],
                programId: $data['program_id'],
                timestamp: $data['timestamp'],
            ),
            'snapshot.generated' => SnapshotGenerated::dispatch(
                snapshotId: $data['snapshot_id'],
                period: $data['period'],
                snapshotHash: $data['sha256'],
                componentId: $data['component_id'],
                programId: $data['program_id'],
                valorOficial: $data['valor_oficial'],
                timestamp: $data['timestamp'],
            ),
            'sync.processed' => SyncProcessed::dispatch(
                entryId: $data['entry_id'],
                operation: $data['operation'],
                resultType: $data['result_type'] ?? null,
                resultId: $data['result_id'] ?? null,
                timestamp: $data['timestamp'],
            ),
            default => null,
        };

        return response()->json(['received' => true]);
    }
}
