<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PersonIdentifierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonIdentifier extends Model
{
    /** @use HasFactory<PersonIdentifierFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['person_id', 'type', 'normalized_value', 'authoritative_key', 'source'];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
