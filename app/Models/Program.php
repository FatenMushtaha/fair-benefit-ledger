<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['name', 'sector', 'status'];

    protected function casts(): array
    {
        return [
            'status' => ProgramStatus::class,
        ];
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }
}
