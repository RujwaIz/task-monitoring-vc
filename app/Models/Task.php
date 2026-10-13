<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'title', 'owner', 'due_date', 'priority', 'status'])]
class Task extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Query version of isOverdue(): not complete and due before today. */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', '!=', TaskStatus::Complete)->whereDate('due_date', '<', today());
    }

    /** A task is overdue when it is not complete and its due date is before today. */
    public function isOverdue(): bool
    {
        return $this->status !== TaskStatus::Complete && $this->due_date->isBefore(today());
    }
}
