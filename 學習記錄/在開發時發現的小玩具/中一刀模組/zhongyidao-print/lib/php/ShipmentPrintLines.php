<?php

/**
 * 版型 B：出貨單明細展開（品名過長續列、運費列）。
 * 無框架依賴，可複製至任意 PHP 專案。
 */
final class ShipmentPrintLines
{
    public const ROWS_PER_PAGE = 8;

    public const MAX_PRODUCT_CHARS_PER_ROW = 32;

    /**
     * @param  array<int, object|array<string, mixed>>  $items
     * @param  callable(object|array): string  $productLabel
     * @return list<array<string, mixed>>
     */
    public static function build(array $items, float $shippingFee, callable $productLabel): array
    {
        $lines = [];
        $seq = 0;

        foreach ($items as $item) {
            $parts = self::splitProductLabel($productLabel($item));
            foreach ($parts as $index => $part) {
                $isContinuation = $index > 0;
                if (! $isContinuation) {
                    $seq++;
                }
                $lines[] = [
                    'type' => 'item',
                    'item' => $item,
                    'seq' => $isContinuation ? null : $seq,
                    'name_part' => $part,
                    'is_continuation' => $isContinuation,
                ];
            }
        }

        if ($shippingFee > 0) {
            $seq++;
            $lines[] = [
                'type' => 'shipping',
                'item' => null,
                'seq' => $seq,
                'name_part' => '運費',
                'is_continuation' => false,
                'shipping_amount' => $shippingFee,
            ];
        }

        return $lines;
    }

    /**
     * @return list<array<int, array<string, mixed>>>
     */
    public static function chunkPages(array $lines, ?int $rowsPerPage = null): array
    {
        $rowsPerPage = $rowsPerPage ?? self::ROWS_PER_PAGE;
        if ($lines === []) {
            return [[]];
        }

        return array_map(
            static fn (array $chunk) => array_values($chunk),
            array_chunk($lines, $rowsPerPage)
        );
    }

    /**
     * @return list<string>
     */
    public static function splitProductLabel(string $label): array
    {
        $label = trim($label);
        if ($label === '') {
            return [''];
        }

        $max = self::MAX_PRODUCT_CHARS_PER_ROW;
        if (mb_strlen($label) <= $max) {
            return [$label];
        }

        $parts = [];
        $offset = 0;
        $length = mb_strlen($label);
        while ($offset < $length) {
            $parts[] = mb_substr($label, $offset, $max);
            $offset += $max;
        }

        return $parts;
    }
}
