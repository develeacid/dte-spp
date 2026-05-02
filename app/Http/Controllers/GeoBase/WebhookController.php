<?php

namespace App\Http\Controllers\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Events\GeoBase\SnapshotGenerated;
use App\Events\GeoBase\SyncProcessed;
use App\Http\Controllers\Controller;
use App\Models\GeoBase\WebhookDelivery;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    private const PAYLOAD_TRUNCATE_BYTES = 16384;
    private const ERROR_MESSAGE_MAX = 497;

    public function handle(Request $request): JsonResponse
    {
        $deliveryId = $request->header('X-GeoBase-Delivery');
        $eventType = $request->header('X-GeoBase-Event') ?? $request->input('event');

        if (! $deliveryId || ! $eventType) {
            return response()->json([
                'error' => 'Missing X-GeoBase-Delivery or X-GeoBase-Event header',
            ], 400);
        }

        $existing = WebhookDelivery::where('delivery_id', $deliveryId)->first();
        if ($existing) {
            return response()->json([
                'received' => true,
                'idempotent' => true,
                'first_seen_at' => $existing->created_at->toIso8601String(),
            ]);
        }

        $payload = $request->header('X-GeoBase-Event')
            ? $request->all()
            : $request->input('data', []);

        try {
            $delivery = WebhookDelivery::create([
                'delivery_id' => $deliveryId,
                'event_type' => $eventType,
                'signature_valid' => true,
                'payload' => $this->maybeTruncate($request->getContent(), $payload),
            ]);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                $existing = WebhookDelivery::where('delivery_id', $deliveryId)->first();

                return response()->json([
                    'received' => true,
                    'idempotent' => true,
                    'first_seen_at' => $existing?->created_at?->toIso8601String(),
                ]);
            }
            throw $e;
        }

        $rules = $this->rulesFor($eventType);
        if ($rules !== null) {
            $validator = Validator::make($payload, $rules);
            if ($validator->fails()) {
                $delivery->update([
                    'status_code' => 422,
                    'error_message' => substr(
                        json_encode($validator->errors()->toArray()),
                        0,
                        self::ERROR_MESSAGE_MAX
                    ),
                    'processed_at' => now(),
                ]);

                activity('geobase-webhook')
                    ->withProperties(array_merge($payload, ['_invalid' => true]))
                    ->log("$eventType (invalid)");

                return response()->json([
                    'received' => false,
                    'errors' => $validator->errors()->toArray(),
                ], 422);
            }
        }

        activity('geobase-webhook')
            ->withProperties($payload)
            ->log($eventType);

        match ($eventType) {
            'enrollment.status_changed' => EnrollmentStatusChanged::dispatch(
                enrollmentId: $payload['enrollment_id'],
                oldStatus: $payload['old_status'],
                newStatus: $payload['new_status'],
                sppProgramId: $payload['spp_program_id'],
                timestamp: $payload['timestamp'],
            ),
            'snapshot.generated' => SnapshotGenerated::dispatch(
                snapshotId: $payload['snapshot_id'],
                period: $payload['period'],
                snapshotHash: $payload['sha256'],
                sppMirNivelId: $payload['spp_mir_nivel_id'],
                sppProgramId: $payload['spp_program_id'],
                valorOficial: $payload['valor_oficial'],
                timestamp: $payload['timestamp'],
            ),
            'sync.processed' => SyncProcessed::dispatch(
                entryId: $payload['entry_id'],
                operation: $payload['operation'],
                resultType: $payload['result_type'] ?? null,
                resultId: $payload['result_id'] ?? null,
                timestamp: $payload['timestamp'],
            ),
            default => null,
        };

        $delivery->update([
            'status_code' => 200,
            'processed_at' => now(),
        ]);

        return response()->json(['received' => true]);
    }

    private function maybeTruncate(string $rawBody, array $parsed): array
    {
        if (strlen($rawBody) > self::PAYLOAD_TRUNCATE_BYTES) {
            return [
                '_truncated' => true,
                'preview' => substr($rawBody, 0, 16000),
            ];
        }

        return $parsed;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return $e->getCode() === '23505'
            || str_contains($e->getMessage(), 'duplicate key')
            || str_contains($e->getMessage(), 'UNIQUE constraint');
    }

    private function rulesFor(string $eventType): ?array
    {
        return match ($eventType) {
            'enrollment.status_changed' => [
                'enrollment_id' => 'required|integer',
                'old_status' => 'required|string',
                'new_status' => 'required|string',
                'spp_program_id' => 'required|integer',
                'timestamp' => 'required|string',
            ],
            'snapshot.generated' => [
                'snapshot_id' => 'required|integer',
                'period' => 'required|string',
                'sha256' => 'required|string',
                'spp_mir_nivel_id' => 'required|integer',
                'spp_program_id' => 'required|integer',
                'valor_oficial' => 'required|integer',
                'timestamp' => 'required|string',
            ],
            'sync.processed' => [
                'entry_id' => 'required|integer',
                'operation' => 'required|string',
                'result_type' => 'nullable|string',
                'result_id' => 'nullable|integer',
                'timestamp' => 'required|string',
            ],
            default => null,
        };
    }
}
