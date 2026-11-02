<?php

namespace App\Services;

use App\Enums\MatchConfidenceBand;
use App\Models\ImportRow;
use App\Models\MatchCandidate;
use App\Models\PersonIdentifier;
use App\Models\PersonName;

class CandidateGenerator
{
    public const NORMALIZER_VERSION = NameNormalizer::VERSION.'-'.IdentifierNormalizer::VERSION;

    public const MATCH_POLICY_VERSION = '1';

    public const MAX_CANDIDATES = 100;

    public function generate(ImportRow $row): void
    {
        $organizationId = (int) $row->organization_id;

        MatchCandidate::where('organization_id', $organizationId)
            ->where('import_row_id', $row->id)
            ->delete();

        $count = 0;
        $nameNormalizer = app(NameNormalizer::class);
        $identifierNormalizer = app(IdentifierNormalizer::class);

        $name = $this->payloadValue($row, 'name');
        $identifierType = $this->payloadValue($row, 'identifier_type');
        $identifierValue = $this->payloadValue($row, 'identifier_value');

        if ($identifierType !== null && $identifierValue !== null) {
            $normalized = $identifierNormalizer->normalize($identifierType, $identifierValue);
            $personIds = PersonIdentifier::query()
                ->where('organization_id', $organizationId)
                ->where('type', $identifierType)
                ->where('normalized_value', $normalized)
                ->pluck('person_id');

            foreach ($personIds as $personId) {
                if ($count >= self::MAX_CANDIDATES) {
                    break;
                }
                MatchCandidate::create([
                    'organization_id' => $organizationId,
                    'import_row_id' => $row->id,
                    'person_id' => $personId,
                    'method' => 'authoritative_identifier',
                    'confidence_band' => MatchConfidenceBand::High,
                    'reason_codes' => ['exact_authoritative_identifier'],
                    'blocking_key' => $normalized,
                    'match_policy_version' => self::MATCH_POLICY_VERSION,
                    'normalizer_version' => self::NORMALIZER_VERSION,
                ]);
                $count++;
            }
        }

        if ($name !== null) {
            $blockingKey = $nameNormalizer->tokenSortedKey($name);
            $personIds = PersonName::query()
                ->where('organization_id', $organizationId)
                ->where('blocking_key', $blockingKey)
                ->pluck('person_id')
                ->unique();

            foreach ($personIds as $personId) {
                if ($count >= self::MAX_CANDIDATES) {
                    break;
                }
                $exists = MatchCandidate::where('organization_id', $organizationId)
                    ->where('import_row_id', $row->id)
                    ->where('person_id', $personId)
                    ->exists();
                if ($exists) {
                    continue;
                }
                MatchCandidate::create([
                    'organization_id' => $organizationId,
                    'import_row_id' => $row->id,
                    'person_id' => $personId,
                    'method' => 'name_blocking',
                    'confidence_band' => MatchConfidenceBand::Medium,
                    'reason_codes' => ['name_blocking_match'],
                    'blocking_key' => $blockingKey,
                    'match_policy_version' => self::MATCH_POLICY_VERSION,
                    'normalizer_version' => self::NORMALIZER_VERSION,
                ]);
                $count++;
            }
        }
    }

    private function payloadValue(ImportRow $row, string $key): ?string
    {
        foreach ([$row->cleaned_payload, $row->mapped_payload, $row->raw_payload] as $payload) {
            if (is_array($payload) && isset($payload[$key]) && $payload[$key] !== null && $payload[$key] !== '') {
                return (string) $payload[$key];
            }
        }

        return null;
    }
}
