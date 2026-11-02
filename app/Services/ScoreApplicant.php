<?php

namespace App\Services;

class ScoreApplicant
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $inputs
     * @return array{criterion_points: array<string,int>, weighted_numerator: int, display_score: string, missing_flags: array<int,string>, reasons: array<int,string>}
     */
    public function score(array $config, array $inputs): array
    {
        $criteria = is_array($config['criteria'] ?? null) ? $config['criteria'] : [];
        $policy = (string) ($config['missing_data_policy'] ?? 'block');
        $points = [];
        $numerator = 0;
        $missing = [];
        $reasons = [];

        foreach ($criteria as $criterion) {
            $key = (string) ($criterion['key'] ?? '');
            $weight = (int) ($criterion['weight'] ?? 0);
            $source = (string) ($criterion['input_source'] ?? '');
            $mapping = is_array($criterion['mapping'] ?? null) ? $criterion['mapping'] : [];
            $value = $inputs[$source] ?? null;

            if ($value === null) {
                if ($policy === 'block') {
                    throw new \RuntimeException('Missing required scoring input: '.$source);
                }
                $missing[] = $source;
                $point = 0;
            } elseif (! array_key_exists((string) $value, $mapping)) {
                if ($policy === 'block') {
                    throw new \RuntimeException('Unsupported scoring value for '.$source);
                }
                $missing[] = $source;
                $point = 0;
            } else {
                $point = (int) $mapping[(string) $value];
            }

            $points[$key] = $point;
            $numerator += $point * $weight;
        }

        $display = number_format($numerator / 10000, 2, '.', '');

        return [
            'criterion_points' => $points,
            'weighted_numerator' => $numerator,
            'display_score' => $display,
            'missing_flags' => $missing,
            'reasons' => $reasons,
        ];
    }
}
