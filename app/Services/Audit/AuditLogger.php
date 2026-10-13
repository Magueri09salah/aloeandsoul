<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Records who did what in the admin. Sensitive values are redacted.
 */
class AuditLogger
{
    private const REDACT = [
        'password', 'password_confirmation', 'current_password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes', 'token',
        'customer_phone', 'customer_email', 'address', 'phone', 'delivery_notes',
    ];

    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, array $properties = [], ?int $userId = null): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $userId ?? $this->request->user()?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $this->redact($properties),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 250, ''),
        ]);
    }

    /**
     * Field-level diff of a model's pending changes, for update audits.
     *
     * @param  list<string>  $ignore
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public static function diff(Model $model, array $ignore = ['updated_at']): array
    {
        $changes = [];
        foreach ($model->getDirty() as $key => $value) {
            if (in_array($key, $ignore, true)) {
                continue;
            }
            $changes[$key] = ['from' => $model->getOriginal($key), 'to' => $model->getAttribute($key)];
        }

        return $changes;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, self::REDACT, true)) {
                $data[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            } elseif ($value instanceof \BackedEnum) {
                $data[$key] = $value->value;
            }
        }

        return $data;
    }
}
