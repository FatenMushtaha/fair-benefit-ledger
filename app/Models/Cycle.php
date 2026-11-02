<?php

namespace App\Models;

use App\Enums\CycleStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CycleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cycle extends Model
{
    /** @use HasFactory<CycleFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'program_id',
        'rule_version_id',
        'name',
        'status',
        'start_date',
        'available_quantity',
        'automation_mode',
    ];

    protected function casts(): array
    {
        return [
            'status' => CycleStatus::class,
            'start_date' => 'date',
            'available_quantity' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(RuleVersion::class);
    }
}
