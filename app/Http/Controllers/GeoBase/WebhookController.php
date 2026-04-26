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
        // GeoBase carries the event type in X-GeoBase-Event and the payload
        // is the raw JSON body (no envelope). Old format with a {event,data}
        // body envelope is supported as a fallback.
        $eventType = $request->header('X-GeoBase-Event') ?? $request->input('event');
        $data = $request->header('X-GeoBase-Event')
            ? $request->all()
            : $request->input('data', []);

        activity('geobase-webhook')
            ->withProperties($data)
            ->log($eventType);

        match ($eventType) {
            'enrollment.status_changed' => EnrollmentStatusChanged::dispatch(
                enrollmentId: $data['enrollment_id'],
                oldStatus: $data['old_status'],
                newStatus: $data['new_status'],
                sppProgramId: $data['spp_program_id'],
                timestamp: $data['timestamp'],
            ),
            'snapshot.generated' => SnapshotGenerated::dispatch(
                snapshotId: $data['snapshot_id'],
                period: $data['period'],
                snapshotHash: $data['sha256'],
                sppMirNivelId: $data['spp_mir_nivel_id'],
                sppProgramId: $data['spp_program_id'],
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
