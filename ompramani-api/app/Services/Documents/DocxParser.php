<?php

namespace App\Services\Documents;

use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

class DocxParser implements DocumentParserInterface
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * Determine if this parser supports the given file.
     */
    public function supports(string $filePath, ?string $mimeType = null): bool
    {
        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return true;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return $extension === 'docx';
    }

    /**
     * Parse a DOCX file into sequential structure of headings and paragraphs.
     *
     * @return array<int, array{type: string, title: ?string, content: ?string, heading_level: ?int, source_index: int}>
     *
     * @throws InvalidArgumentException
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException('Tập tin không tồn tại hoặc không thể đọc được.');
        }

        // Verify ZIP magic signature (PK\x03\x04)
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 4);
        fclose($handle);

        if ($header !== "PK\x03\x04") {
            throw new InvalidArgumentException('Tập tin không phải là định dạng DOCX hợp lệ hoặc đã bị hỏng.');
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($filePath);

        if ($openResult !== true) {
            throw new InvalidArgumentException('Không thể mở tập tin DOCX.');
        }

        try {
            $documentXmlContent = $zip->getFromName('word/document.xml');

            if ($documentXmlContent === false) {
                throw new InvalidArgumentException('Tập tin DOCX không chứa cấu trúc word/document.xml hợp lệ.');
            }

            $styleMap = $this->parseStyles($zip);
            $parsedItems = $this->parseDocumentXml($documentXmlContent, $styleMap);

            if (empty($parsedItems)) {
                throw new InvalidArgumentException('Tài liệu DOCX không có nội dung văn bản nào.');
            }

            return $parsedItems;
        } finally {
            $zip->close();
        }
    }

    /**
     * Parse word/styles.xml to map style IDs to heading levels.
     *
     * @return array<string, int>
     */
    private function parseStyles(ZipArchive $zip): array
    {
        $stylesContent = $zip->getFromName('word/styles.xml');

        if ($stylesContent === false) {
            return [];
        }

        $styleMap = [];

        try {
            $xml = new SimpleXMLElement($stylesContent);
            $xml->registerXPathNamespace('w', self::W_NS);

            $styles = $xml->xpath('//w:style[@w:type="paragraph"]');

            if ($styles !== false) {
                foreach ($styles as $style) {
                    $styleId = (string) ($style->attributes(self::W_NS)['styleId'] ?? '');
                    if ($styleId === '') {
                        continue;
                    }

                    $level = $this->detectHeadingLevelFromStyle($style);

                    if ($level !== null) {
                        $styleMap[$styleId] = $level;
                    }
                }
            }
        } catch (\Throwable) {
            // If styles.xml fails to parse, fallback to inline style detection in document.xml
        }

        return $styleMap;
    }

    /**
     * Determine heading level from a style XML element.
     */
    private function detectHeadingLevelFromStyle(SimpleXMLElement $style): ?int
    {
        $style->registerXPathNamespace('w', self::W_NS);

        // Check outline level (0 = H1, 1 = H2, etc.)
        $outlineLvl = $style->xpath('.//w:outlineLvl');
        if (! empty($outlineLvl)) {
            $val = (int) ($outlineLvl[0]->attributes(self::W_NS)['val'] ?? -1);
            if ($val >= 0 && $val <= 8) {
                return $val + 1;
            }
        }

        // Check style name (e.g., "heading 1", "Tiêu đề 1")
        $nameEl = $style->xpath('.//w:name');
        if (! empty($nameEl)) {
            $name = (string) ($nameEl[0]->attributes(self::W_NS)['val'] ?? '');
            $matchedLevel = $this->matchHeadingName($name);
            if ($matchedLevel !== null) {
                return $matchedLevel;
            }
        }

        $styleId = (string) ($style->attributes(self::W_NS)['styleId'] ?? '');

        return $this->matchHeadingName($styleId);
    }

    /**
     * Check if a string represents a heading and return level 1..9.
     */
    private function matchHeadingName(string $name): ?int
    {
        $normalized = mb_strtolower(trim($name), 'UTF-8');

        if (preg_match('/^(?:heading|tieu\s*de|tiêu\s*đề)\s*([1-9])$/u', $normalized, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^(?:heading|tieu_de|tiêu_đề)_?([1-9])$/u', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Parse word/document.xml and extract text and heading hierarchy.
     *
     * @param  array<string, int>  $styleMap
     * @return array<int, array{type: string, title: ?string, content: ?string, heading_level: ?int, source_index: int}>
     */
    private function parseDocumentXml(string $xmlContent, array $styleMap): array
    {
        $xml = new SimpleXMLElement($xmlContent);
        $xml->registerXPathNamespace('w', self::W_NS);

        $paragraphs = $xml->xpath('//w:body/w:p');

        if ($paragraphs === false) {
            return [];
        }

        $items = [];
        $sourceIndex = 1;

        foreach ($paragraphs as $p) {
            $p->registerXPathNamespace('w', self::W_NS);

            // Extract all text and linebreaks in order
            $text = $this->extractParagraphText($p);

            if ($text === '') {
                continue;
            }

            // Determine if paragraph has heading style
            $headingLevel = $this->detectParagraphHeadingLevel($p, $styleMap);

            if ($headingLevel !== null) {
                $items[] = [
                    'type' => 'heading',
                    'title' => $text,
                    'content' => null,
                    'heading_level' => $headingLevel,
                    'source_index' => $sourceIndex++,
                ];
            } else {
                $items[] = [
                    'type' => 'paragraph',
                    'title' => null,
                    'content' => $text,
                    'heading_level' => null,
                    'source_index' => $sourceIndex++,
                ];
            }
        }

        return $items;
    }

    /**
     * Detect heading level from paragraph properties.
     *
     * @param  array<string, int>  $styleMap
     */
    private function detectParagraphHeadingLevel(SimpleXMLElement $p, array $styleMap): ?int
    {
        $p->registerXPathNamespace('w', self::W_NS);

        // Check outline level directly on paragraph
        $outline = $p->xpath('./w:pPr/w:outlineLvl');
        if (! empty($outline)) {
            $val = (int) ($outline[0]->attributes(self::W_NS)['val'] ?? -1);
            if ($val >= 0 && $val <= 8) {
                return $val + 1;
            }
        }

        // Check paragraph style
        $pStyle = $p->xpath('./w:pPr/w:pStyle');
        if (! empty($pStyle)) {
            $val = (string) ($pStyle[0]->attributes(self::W_NS)['val'] ?? '');

            if (isset($styleMap[$val])) {
                return $styleMap[$val];
            }

            $matched = $this->matchHeadingName($val);
            if ($matched !== null) {
                return $matched;
            }
        }

        return null;
    }

    /**
     * Extract full text from paragraph including runs, breaks, tabs.
     */
    private function extractParagraphText(SimpleXMLElement $p): string
    {
        $p->registerXPathNamespace('w', self::W_NS);
        $runs = $p->xpath('.//w:r | .//w:br');

        if (empty($runs)) {
            // Direct text fallback
            return trim((string) $p);
        }

        $text = '';

        foreach ($runs as $node) {
            $nodeName = $node->getName();

            if ($nodeName === 'br') {
                $text .= "\n";

                continue;
            }

            if ($nodeName === 'r') {
                $node->registerXPathNamespace('w', self::W_NS);
                $children = $node->xpath('./w:t | ./w:br | ./w:cr | ./w:tab');

                foreach ($children as $child) {
                    $cName = $child->getName();
                    if ($cName === 't') {
                        $text .= (string) $child;
                    } elseif ($cName === 'br' || $cName === 'cr') {
                        $text .= "\n";
                    } elseif ($cName === 'tab') {
                        $text .= "\t";
                    }
                }
            }
        }

        // Clean up redundant blank lines or multiple spaces while preserving meaningful paragraphs
        $text = preg_replace("/[ \t]+/u", ' ', $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text ?? '');
    }
}
