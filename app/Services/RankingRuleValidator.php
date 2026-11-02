<?php

namespace App\Services;

class RankingRuleValidator
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    public function validate(array $config): array
    {
        $errors = [];

        $criteria = $config['criteria'] ?? null;
        if (! is_array($criteria)) {
            return ['criteria required'];
        }

        $count = count($criteria);
        if ($count < 4 || $count > 6) {
            $errors[] = 'criteria count must be 4-6';
        }

        $keys = [];
        $weightTotal = 0;
        foreach ($criteria as $index => $criterion) {
            if (! is_array($criterion)) {
                $errors[] = "criterion {$index} invalid";

                continue;
            }
            $key = $criterion['key'] ?? null;
            if (! is_string($key) || $key === '') {
                $errors[] = "criterion {$index} missing key";
            } elseif (in_array($key, $keys, true)) {
                $errors[] = "duplicate criterion key {$key}";
            } else {
                $keys[] = $key;
            }

            $weight = $criterion['weight'] ?? null;
            if (! is_int($weight) || $weight < 0) {
                $errors[] = "criterion {$index} invalid weight";
            } else {
                $weightTotal += $weight;
            }

            $mapping = $criterion['mapping'] ?? null;
            if (! is_array($mapping) || $mapping === []) {
                $errors[] = "criterion {$index} missing mapping";
            } else {
                foreach ($mapping as $points) {
                    if (! is_int($points) || $points < 0 || $points > 10000) {
                        $errors[] = "criterion {$index} invalid mapping points";
                        break;
                    }
                }
            }

            if (! isset($criterion['input_source']) || ! is_string($criterion['input_source'])) {
                $errors[] = "criterion {$index} missing input source";
            }
        }

        if ($weightTotal !== 10000) {
            $errors[] = 'weights must sum to 10000';
        }

        if (! isset($config['missing_data_policy']) || ! is_string($config['missing_data_policy'])) {
            $errors[] = 'missing data policy required';
        }

        if (! isset($config['tie_break']) || ! is_array($config['tie_break'])) {
            $errors[] = 'tie break required';
        }

        if (($config['automation_mode'] ?? null) !== 'manual_approval_required') {
            $errors[] = 'automation mode must be manual_approval_required';
        }

        return $errors;
    }
}
