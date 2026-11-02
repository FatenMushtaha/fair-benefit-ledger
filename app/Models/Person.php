<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['display_name', 'normalized_name', 'date_of_birth', 'phone'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class);
    }

    public function names(): HasMany
    {
        return $this->hasMany(PersonName::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }
}
