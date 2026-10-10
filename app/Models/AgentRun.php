<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One run of an AI agent: what triggered it, what its tools did and what it answered.
 * Tools report their side effects with AgentRun::record() while a run is active.
 */
class AgentRun extends Model
{
    protected $fillable = [
        'agent',
        'trigger',
        'user_id',
        'status',
        'actions',
        'response',
        'error',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'actions' => 'array',
        ];
    }

    private static ?self $active = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function begin(string $agent, string $trigger = 'schedule', ?User $user = null): self
    {
        return static::$active = static::create([
            'agent' => $agent,
            'trigger' => $trigger,
            'user_id' => $user?->id,
            'status' => 'running',
            'actions' => [],
        ]);
    }

    /** Called by agent tools: adds one line to the active run, if there is one. */
    public static function record(string $tool, string $result): void
    {
        if (! static::$active) {
            return;
        }

        static::$active->actions = [...(static::$active->actions ?? []), [
            'tool' => $tool,
            'result' => $result,
            'at' => now()->toIso8601String(),
        ]];
        static::$active->save();
    }

    public function finish(string $response, int $durationMs): void
    {
        $this->update(['status' => 'completed', 'response' => $response, 'duration_ms' => $durationMs]);
        static::$active = null;
    }

    public function fail(string $error, int $durationMs): void
    {
        $this->update(['status' => 'failed', 'error' => $error, 'duration_ms' => $durationMs]);
        static::$active = null;
    }
}
