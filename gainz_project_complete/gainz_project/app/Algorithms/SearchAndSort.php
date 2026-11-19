<?php
declare(strict_types=1);

namespace App\Algorithms;

class SearchAndSort
{
    public static function mergeSort(array $items, string $key, string $direction = 'asc', string $type = 'string'): array
    {
        $count = count($items);
        if ($count <= 1) {
            return array_values($items);
        }

        $mid = intdiv($count, 2);
        $left = self::mergeSort(array_slice($items, 0, $mid), $key, $direction, $type);
        $right = self::mergeSort(array_slice($items, $mid), $key, $direction, $type);

        return self::merge($left, $right, $key, $direction, $type);
    }

    private static function merge(array $left, array $right, string $key, string $direction, string $type): array
    {
        $result = [];
        $i = 0;
        $j = 0;
        $leftCount = count($left);
        $rightCount = count($right);

        while ($i < $leftCount && $j < $rightCount) {
            $cmp = self::compareValues($left[$i][$key] ?? '', $right[$j][$key] ?? '', $type);
            if (strtolower($direction) === 'desc') {
                $cmp = -$cmp;
            }

            if ($cmp <= 0) {
                $result[] = $left[$i];
                $i++;
            } else {
                $result[] = $right[$j];
                $j++;
            }
        }

        while ($i < $leftCount) {
            $result[] = $left[$i];
            $i++;
        }

        while ($j < $rightCount) {
            $result[] = $right[$j];
            $j++;
        }

        return $result;
    }

    private static function compareValues(mixed $a, mixed $b, string $type): int
    {
        return match ($type) {
            'number' => ((float)$a <=> (float)$b),
            'date' => ((strtotime((string)$a) ?: 0) <=> (strtotime((string)$b) ?: 0)),
            default => strcasecmp(trim((string)$a), trim((string)$b)),
        };
    }

    public static function binarySearch(array $sortedItems, string $key, string|float|int $target, string $type = 'string'): array
    {
        $items = array_values($sortedItems);
        $low = 0;
        $high = count($items) - 1;
        $comparisons = 0;
        $foundIndex = null;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $comparisons++;

            $candidate = $items[$mid][$key] ?? '';
            if ($type === 'string') {
                $cmp = strcasecmp(trim((string)$candidate), trim((string)$target));
            } elseif ($type === 'number') {
                $cmp = ((float)$candidate <=> (float)$target);
            } else {
                $cmp = ((strtotime((string)$candidate) ?: 0) <=> (strtotime((string)$target) ?: 0));
            }

            if ($cmp === 0) {
                $foundIndex = $mid;
                break;
            } elseif ($cmp < 0) {
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        if ($foundIndex === null) {
            return ['matches' => [], 'comparisons' => $comparisons, 'found_index' => null];
        }

        $leftIdx = $foundIndex;
        while ($leftIdx >= 0) {
            $val = $items[$leftIdx][$key] ?? '';
            $match = ($type === 'string')
                ? (strcasecmp(trim((string)$val), trim((string)$target)) === 0)
                : ($type === 'number' ? ((float)$val === (float)$target) : ((strtotime((string)$val) ?: 0) === (strtotime((string)$target) ?: 0)));
            if (!$match) {
                break;
            }
            $leftIdx--;
        }

        $rightIdx = $foundIndex + 1;
        $total = count($items);
        while ($rightIdx < $total) {
            $val = $items[$rightIdx][$key] ?? '';
            $match = ($type === 'string')
                ? (strcasecmp(trim((string)$val), trim((string)$target)) === 0)
                : ($type === 'number' ? ((float)$val === (float)$target) : ((strtotime((string)$val) ?: 0) === (strtotime((string)$target) ?: 0)));
            if (!$match) {
                break;
            }
            $rightIdx++;
        }

        $matches = [];
        for ($k = $leftIdx + 1; $k < $rightIdx; $k++) {
            $matches[] = $items[$k];
        }

        return ['matches' => $matches, 'comparisons' => $comparisons, 'found_index' => $foundIndex];
    }
}
