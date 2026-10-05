<?php

namespace App\Services;

class SqlGeneratedColumnNormalizer
{
    public function normalize(string $sql, array $columns): ?string
    {
        if (! preg_match('/\A((?:\s|--[^\n]*\n|\#[^\n]*\n|\/\*.*?\*\/)*INSERT\s+INTO\s+`(?:``|[^`])+`\s*)(\([^)]*\)\s*)?VALUES\s*/is', $sql, $match)) {
            return null;
        }

        $names = array_column($columns, 'Field');
        if (! empty($match[2])) {
            preg_match_all('/`((?:``|[^`])+)`/', $match[2], $identifiers);
            $names = array_map(fn ($name) => str_replace('``', '`', $name), $identifiers[1]);
        }
        $generated = [];
        foreach ($columns as $column) {
            if (preg_match('/\b(?:VIRTUAL|STORED) GENERATED\b/i', $column['Extra'] ?? '')) {
                $index = array_search($column['Field'], $names, true);
                if ($index !== false) $generated[$index] = true;
            }
        }
        if ($generated === []) return null;

        $result = substr($sql, 0, strlen($match[0]));
        $start = strlen($match[0]);
        $depth = 0;
        $column = 0;
        $quote = null;
        $length = strlen($sql);
        for ($i = $start; $i < $length; $i++) {
            $char = $sql[$i];
            if ($quote !== null) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    if (($sql[$i + 1] ?? null) === $quote) $i++;
                    else $quote = null;
                }
                continue;
            }
            if (in_array($char, ["'", '"', '`'], true)) {
                $quote = $char;
            } elseif ($char === '(') {
                if ($depth++ === 0) {
                    $result .= substr($sql, $start, $i - $start + 1);
                    $start = $i + 1;
                    $column = 0;
                }
            } elseif (($char === ',' && $depth === 1) || ($char === ')' && $depth === 1)) {
                $result .= isset($generated[$column]) ? 'DEFAULT' : substr($sql, $start, $i - $start);
                $result .= $char;
                $start = $i + 1;
                $column++;
                if ($char === ')') $depth--;
            } elseif ($char === ')') {
                $depth--;
            }
        }

        return $result.substr($sql, $start);
    }
}
