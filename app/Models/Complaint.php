<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'ticket_code',
        'reporter_name',
        'kecamatan',
        'kelurahan',
        'raw_text',
        'clean_text',
        'predicted_category',
        'actual_category',
        'predicted_urgency',
        'actual_urgency',
        'assigned_opd',
        'assigned_unit',
        'sla_target',
        'confidence_svm',
        'latency_ms',
        'model_engine',
        'matched_tokens',
        'class_scores',
        'status',
        'source',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_tokens' => 'array',
            'class_scores' => 'array',
            'confidence_svm' => 'float',
            'latency_ms' => 'float',
            'reported_at' => 'datetime',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('ticket_code', 'like', "%{$search}%")
                        ->orWhere('raw_text', 'like', "%{$search}%")
                        ->orWhere('kelurahan', 'like', "%{$search}%")
                        ->orWhere('kecamatan', 'like', "%{$search}%");
                });
            })
            ->when($filters['kecamatan'] ?? null, fn (Builder $q, string $v) => $q->where('kecamatan', $v))
            ->when($filters['kategori'] ?? null, fn (Builder $q, string $v) => $q->where('predicted_category', $v))
            ->when($filters['urgensi'] ?? null, fn (Builder $q, string $v) => $q->where('predicted_urgency', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, string $v) => $q->where('status', $v))
            ->when($filters['source'] ?? null, fn (Builder $q, string $v) => $q->where('source', $v));
    }
}
