<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

class DiffService
{
    /**
     * @return array<int, array{type: string, text: string}>
     */
    public function diff(string $old, string $new): array
    {
        $oldWords = $this->tokenize($old);
        $newWords = $this->tokenize($new);

        $lcsMatrix = $this->lcs($oldWords, $newWords);

        $result = [];
        $i = count($oldWords);
        $j = count($newWords);

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $oldWords[$i - 1] === $newWords[$j - 1]) {
                array_unshift($result, ['type' => 'unchanged', 'text' => $oldWords[$i - 1]]);
                $i--;
                $j--;
            } elseif ($j > 0 && ($i === 0 || $lcsMatrix[$i][$j - 1] >= $lcsMatrix[$i - 1][$j])) {
                array_unshift($result, ['type' => 'added', 'text' => $newWords[$j - 1]]);
                $j--;
            } else {
                array_unshift($result, ['type' => 'removed', 'text' => $oldWords[$i - 1]]);
                $i--;
            }
        }

        return $result;
    }

    public function diffHtml(string $old, string $new): string
    {
        $diff = $this->diff($old, $new);
        $html = '';

        foreach ($diff as $item) {
            match ($item['type']) {
                'added' => $html .= '<ins class="diff-added">'.htmlspecialchars($item['text']).'</ins> ',
                'removed' => $html .= '<del class="diff-removed">'.htmlspecialchars($item['text']).'</del> ',
                default => $html .= htmlspecialchars($item['text']).' ',
            };
        }

        return rtrim($html);
    }

    /**
     * @param  array<int, string>  $oldWords
     * @param  array<int, string>  $newWords
     * @return array<int, array<int, int>>
     */
    private function lcs(array $oldWords, array $newWords): array
    {
        $m = count($oldWords);
        $n = count($newWords);
        $matrix = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));

        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                if ($oldWords[$i - 1] === $newWords[$j - 1]) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
                } else {
                    $matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
                }
            }
        }

        return $matrix;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $cleaned = strip_tags($text);

        return preg_split('/\s+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
