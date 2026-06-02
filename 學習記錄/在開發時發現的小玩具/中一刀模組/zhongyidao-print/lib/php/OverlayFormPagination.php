<?php

/**
 * 版型 A：依品名折行計算表身分頁（列數單位上限）。
 * 回傳格式：每頁為 int 陣列；值 > 100 表示該列為補空白（值 - 100 = 佔用列數）。
 */
final class OverlayFormPagination
{
    public const MAX_ROW_UNITS = 12;

    public const MAX_WORD_WIDTH_PER_LINE = 12;

    /**
     * @param  list<string>  $productNames
     * @return list<list<int>>
     */
    public static function paginate(array $productNames): array
    {
        $nameRowUnits = array_map(
            static fn (string $name) => (int) ceil(self::textWidth($name) / self::MAX_WORD_WIDTH_PER_LINE),
            $productNames
        );

        $result = [];
        $currentPage = [];
        $currentSum = 0;
        $max = self::MAX_ROW_UNITS;

        foreach ($nameRowUnits as $key => $units) {
            if ($currentSum + $units >= $max) {
                if ($currentSum !== $max) {
                    $currentPage[] = $max - $currentSum + 100;
                }
                $result[] = $currentPage;
                $currentPage = [];
                $currentSum = 0;
            }
            $currentPage[] = $units;
            $currentSum += $units;

            if ($key === count($nameRowUnits) - 1) {
                if ($currentSum !== $max) {
                    $currentPage[] = $max - $currentSum + 100;
                }
                $result[] = $currentPage;
            }
        }

        return $result === [] ? [[]] : $result;
    }

    /** 中文 1、英數符號 0.5 */
    public static function textWidth(string $text): float
    {
        $total = 0.0;
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($chars as $char) {
            $total += preg_match('/[\x{4e00}-\x{9fff}]/u', $char) ? 1.0 : 0.5;
        }

        return $total;
    }
}
