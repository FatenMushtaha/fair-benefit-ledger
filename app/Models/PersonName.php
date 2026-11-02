<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\NameNormalizer;
use Database\Factories\PersonNameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonName extends Model
{
    /** @use HasFactory<PersonNameFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['person_id', 'name', 'normalized_name', 'blocking_key', 'script', 'source'];

    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            if ($model->blocking_key === null && $model->name !== null) {
                $model->blocking_key = app(NameNormalizer::class)->tokenSortedKey($model->name);
            }
            if ($model->normalized_name === null && $model->name !== null) {
                $model->normalized_name = app(NameNormalizer::class)->normalize($model->name);
            }
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
