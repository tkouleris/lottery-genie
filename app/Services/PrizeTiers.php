<?php

namespace App\Services;

/**
 * Reads the winners and prize of each tier of a draw row, locating the columns from the sheet headers
 * since their layout differs between games and years (e.g. Eurojackpot added "Greece" columns)
 */
class PrizeTiers
{
    /**
     * @param  array  $headerRows  the sheet rows above the first draw
     * @param  array  $row  the draw row
     * @return array<int, array{tier: string, winners: ?int, winners_gr: ?int, prize: ?float, jackpot: bool}>
     */
    public static function parse(array $headerRows, array $row): array
    {
        $subHeaderIndex = self::subHeaderIndex($headerRows);
        if (is_null($subHeaderIndex) || $subHeaderIndex === 0) {
            return [];
        }

        $tierLabels = $headerRows[$subHeaderIndex - 1];
        $tiers = [];
        $tier = null;

        foreach ($headerRows[$subHeaderIndex] as $column => $heading) {
            if (filled($tierLabels[$column] ?? null)) {
                $tier = self::tierLabel($tierLabels[$column]);
            }
            // Columns without a winners / prize title hold the draw number, date, numbers or ticket total
            if (is_null($tier) || blank($heading)) {
                continue;
            }
            $tiers[$tier] ??= ['tier' => $tier, 'winners' => null, 'winners_gr' => null, 'prize' => null, 'jackpot' => false];

            $heading = mb_strtoupper(trim($heading));
            $value = trim((string) ($row[$column] ?? ''));
            $greece = str_contains($heading, 'ΕΛΛΑΔΑ');

            if (str_starts_with($heading, 'ΕΠ')) {
                // Whole numbers, so any separator is a thousands one; "-" means no winners
                $tiers[$tier][$greece ? 'winners_gr' : 'winners'] = (int) preg_replace('/\D/', '', $value);
            } elseif (str_starts_with($heading, 'ΚΕΡΔΗ') && is_null($tiers[$tier]['prize'])) {
                $tiers[$tier]['prize'] = self::number($value);
                $tiers[$tier]['jackpot'] = str_contains(mb_strtoupper($value), 'ΤΖΑΚ');
            }
        }

        // Tiers only reported for Greece, e.g. Eurojackpot "1 + 1"
        foreach ($tiers as &$entry) {
            if (is_null($entry['winners']) && ! is_null($entry['winners_gr'])) {
                $entry['winners'] = $entry['winners_gr'];
            }
        }

        return array_values($tiers);
    }

    /**
     * The header row holding the "winners" / "prize" column titles
     */
    private static function subHeaderIndex(array $headerRows): ?int
    {
        foreach ($headerRows as $index => $headerRow) {
            foreach ($headerRow as $heading) {
                if (is_string($heading) && str_starts_with(mb_strtoupper(trim($heading)), 'ΚΕΡΔΗ')) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * "5+1 " => "5 + 1", "6αρια" => "6"
     */
    private static function tierLabel(string $label): string
    {
        $label = preg_replace('/\s*\+\s*/', ' + ', trim($label));

        return preg_match('/^(\d+)\D*$/u', $label, $match) ? $match[1] : $label;
    }

    /**
     * Parses "473.432,6" (Greek) or "695,112.50" (English) amounts; "-" and "ΤΖΑΚ-ΠΟΤ" give null
     */
    private static function number(string $value): ?float
    {
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $value)) {
            return (float) str_replace(['.', ','], ['', '.'], $value);
        }
        if (preg_match('/^\d{1,3}(,\d{3})*(\.\d+)?$/', $value)) {
            return (float) str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
