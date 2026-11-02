<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\FinalApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalApproval extends Model
{
    /** @use HasFactory<FinalApprovalFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'decision_id',
        'final_approver_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approver_id');
    }
}
