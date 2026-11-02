<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PersonNameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonName extends Model
{
    /** @use HasFactory<PersonNameFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['person_id', 'name', 'normalized_name', 'script', 'source'];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
