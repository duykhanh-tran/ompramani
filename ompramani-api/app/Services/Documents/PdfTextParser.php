<?php

namespace App\Services\Documents;

use App\Services\Documents\Exceptions\PdfNoTextLayerException;
use InvalidArgumentException;
use Smalot\PdfParser\Parser;
use Throwable;

class PdfTextParser implements DocumentParserInterface
{
    protected Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser;
    }

    /**
     * Determine if this parser supports the given file and/or mime type.
     */
    public function supports(string $filePath, ?string $mimeType = null): bool
    {
        if ($mimeType === 'application/pdf') {
            return true;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return $extension === 'pdf';
    }

    /**
     * Parse a PDF file into sequential sections with page-aware metadata.
     *
     * @return array<int, array{
     *     type: string,
     *     title: ?string,
     *     content: ?string,
     *     heading_level: ?int,
     *     source_index: int,
     *     source_page_start: int,
     *     source_page_end: int
     * }>
     *
     * @throws InvalidArgumentException
     * @throws PdfNoTextLayerException
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException('Tập tin không tồn tại hoặc không thể đọc được.');
        }

        // Validate PDF signature (%PDF-)
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            throw new InvalidArgumentException('Tập tin không phải là định dạng PDF hợp lệ hoặc đã bị hỏng.');
        }

        try {
            $pdf = $this->parser->parseFile($filePath);
            $pages = $pdf->getPages();
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Không thể mở hoặc phân tích tập tin PDF: '.$e->getMessage(), 0, $e);
        }

        if (empty($pages)) {
            throw new InvalidArgumentException('Tài liệu PDF không có trang nào.');
        }

        // Step 1: Extract and clean raw text per page
        $pagesText = [];
        $totalCharacters = 0;

        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1;
            $rawText = $page->getText();
            $cleaned = $this->cleanupText($rawText);
            $charLen = mb_strlen(trim($cleaned), 'UTF-8');

            $totalCharacters += $charLen;
            $pagesText[$pageNumber] = $cleaned;
        }

        // Step 2: Scanned / no-text layer detection
        // If average text per page < 10 characters or overall characters < 15, assume scanned PDF
        if ($totalCharacters < 15) {
            throw new PdfNoTextLayerException;
        }

        // Step 3: Remove repetitive headers/footers
        $pagesText = $this->removeRepetitiveHeadersAndFooters($pagesText);

        // Step 4: Extract sections (headings & paragraphs) per page
        $items = [];
        $sourceIndex = 1;

        foreach ($pagesText as $pageNumber => $pageContent) {
            if (trim($pageContent) === '') {
                continue;
            }

            $blocks = $this->splitIntoBlocks($pageContent);

            foreach ($blocks as $block) {
                $blockText = trim($block);
                if ($blockText === '') {
                    continue;
                }

                $headingLevel = $this->detectHeadingLevel($blockText);

                if ($headingLevel !== null) {
                    $items[] = [
                        'type' => 'heading',
                        'title' => $blockText,
                        'content' => null,
                        'heading_level' => $headingLevel,
                        'source_index' => $sourceIndex++,
                        'source_page_start' => $pageNumber,
                        'source_page_end' => $pageNumber,
                    ];
                } else {
                    $items[] = [
                        'type' => 'paragraph',
                        'title' => null,
                        'content' => $blockText,
                        'heading_level' => null,
                        'source_index' => $sourceIndex++,
                        'source_page_start' => $pageNumber,
                        'source_page_end' => $pageNumber,
                    ];
                }
            }
        }

        if (empty($items)) {
            throw new PdfNoTextLayerException;
        }

        return $items;
    }

    /**
     * Extract quick metadata like pages count and characters count.
     *
     * @return array{pages_count: int, characters_count: int}
     */
    public function extractMetadata(string $filePath): array
    {
        $pdf = $this->parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $pagesCount = count($pages);
        $charsCount = 0;

        foreach ($pages as $page) {
            $charsCount += mb_strlen(trim($page->getText()), 'UTF-8');
        }

        return [
            'pages_count' => $pagesCount,
            'characters_count' => $charsCount,
        ];
    }

    /**
     * Clean up text safely without altering scripture wording.
     */
    protected function cleanupText(string $text): string
    {
        // 1. Remove NUL bytes and control characters (except tab, newline)
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

        // 2. Normalize CRLF and CR to LF
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 3. Clean spaces
        $lines = explode("\n", $text);
        $trimmedLines = array_map(function ($line) {
            return trim(preg_replace('/[ \t]+/u', ' ', $line) ?? '');
        }, $lines);

        $text = implode("\n", $trimmedLines);

        // 4. Reduce excessive blank lines (max 2 consecutive newlines)
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Remove header/footer lines that repeat across pages (> 50% of pages).
     *
     * @param  array<int, string>  $pagesText
     * @return array<int, string>
     */
    protected function removeRepetitiveHeadersAndFooters(array $pagesText): array
    {
        $totalPages = count($pagesText);
        if ($totalPages < 3) {
            return $pagesText;
        }

        $firstLines = [];
        $lastLines = [];

        foreach ($pagesText as $pageNumber => $text) {
            $lines = array_values(array_filter(explode("\n", $text), fn ($l) => trim($l) !== ''));
            if (! empty($lines)) {
                $firstLines[$pageNumber] = $lines[0];
                $lastLines[$pageNumber] = end($lines);
            }
        }

        // Count occurrences
        $firstLineCounts = array_count_values($firstLines);
        $lastLineCounts = array_count_values($lastLines);

        $repetitiveHeaders = array_keys(array_filter($firstLineCounts, fn ($count) => $count >= ceil($totalPages * 0.5)));
        $repetitiveFooters = array_keys(array_filter($lastLineCounts, fn ($count) => $count >= ceil($totalPages * 0.5)));

        $cleanedPages = [];
        foreach ($pagesText as $pageNumber => $text) {
            $lines = explode("\n", $text);

            // Strip header if repetitive or matches simple page number
            if (! empty($lines) && (in_array(trim($lines[0]), $repetitiveHeaders, true) || preg_match('/^(?:Trang\s+)?\d+$/ui', trim($lines[0])))) {
                array_shift($lines);
            }

            // Strip footer if repetitive or matches simple page number
            $lastIndex = count($lines) - 1;
            if ($lastIndex >= 0 && (in_array(trim($lines[$lastIndex]), $repetitiveFooters, true) || preg_match('/^(?:Trang\s+)?\d+$/ui', trim($lines[$lastIndex])))) {
                array_pop($lines);
            }

            $cleanedPages[$pageNumber] = implode("\n", $lines);
        }

        return $cleanedPages;
    }

    /**
     * Split page text into blocks (paragraphs or potential headings).
     *
     * @return array<int, string>
     */
    protected function splitIntoBlocks(string $text): array
    {
        $rawBlocks = preg_split("/\n\s*\n/u", $text);

        if ($rawBlocks === false || empty($rawBlocks)) {
            $rawBlocks = [$text];
        }

        $blocks = [];
        foreach ($rawBlocks as $block) {
            $lines = array_values(array_filter(explode("\n", trim($block)), fn ($l) => trim($l) !== ''));
            if (empty($lines)) {
                continue;
            }

            $currentParagraph = [];
            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                if ($this->detectHeadingLevel($trimmedLine) !== null) {
                    if (! empty($currentParagraph)) {
                        $blocks[] = implode("\n", $currentParagraph);
                        $currentParagraph = [];
                    }
                    $blocks[] = $trimmedLine;
                } else {
                    $currentParagraph[] = $trimmedLine;
                }
            }

            if (! empty($currentParagraph)) {
                $blocks[] = implode("\n", $currentParagraph);
            }
        }

        return $blocks;
    }

    /**
     * Detect heading level based on safe heuristic Buddhist scripture patterns.
     */
    protected function detectHeadingLevel(string $text): ?int
    {
        $text = trim($text);

        // Heading lines are standalone and usually under 120 characters
        if (mb_strlen($text, 'UTF-8') > 120) {
            return null;
        }

        // Multi-line block is not a heading
        if (str_contains($text, "\n")) {
            return null;
        }

        // Pattern 1: Level 1 patterns (Quyển, Kinh title, Lời mở đầu, Chính văn, Hồi hướng)
        if (preg_match('/^(?:QUYỂN|QUYEN)\s+(?:THƯỢNG|TRUNG|HẠ|THUONG|TRUNG|HA|[0-9IVXLCDM]+)(?:\s*[:\-–].*)?$/ui', $text)) {
            return 1;
        }

        if (preg_match('/^(?:LỜI\s+MỞ\s+ĐẦU|LOI\s+MO\s+DAU)(?:\s*[:\-–].*)?$/ui', $text)) {
            return 1;
        }

        if (preg_match('/^(?:CHÍNH\s+VĂN|CHINH\s+VAN)(?:\s*[:\-–].*)?$/ui', $text)) {
            return 1;
        }

        if (preg_match('/^(?:HỒI\s+HƯỚNG|HOI\s+HUONG)(?:\s*[:\-–].*)?$/ui', $text)) {
            return 1;
        }

        if (preg_match('/^(?:PHẨM|PHAM)\s+(?:THỨ\s+[A-ZÀ-Ỹa-zà-ỹ0-9IVXLCDM]+|[0-9IVXLCDM]+|[A-ZÀ-Ỹa-zà-ỹ\s]+)(?:\s*[:\-–].*)?$/ui', $text)) {
            return 1;
        }

        if (preg_match('/^(?:CHƯƠNG|CHUONG)\s+[0-9IVXLCDM]+(?:\s*[:\-–].*)?$/ui', $text)) {
            return 1;
        }

        if (preg_match('/^(?:KINH|Kinh)\s+[A-Za-zÀ-ỹ\s]+$/u', $text) && mb_strlen($text, 'UTF-8') <= 80) {
            return 1;
        }

        // Pattern 2: Level 2 patterns (Tiết, Mục)
        if (preg_match('/^(?:MỤC|MUC|TIẾT|TIET)\s+[0-9IVXLCDM]+(?:\s*[:\-–].*)?$/ui', $text)) {
            return 2;
        }

        return null;
    }
}
