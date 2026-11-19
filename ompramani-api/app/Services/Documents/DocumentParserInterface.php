<?php

namespace App\Services\Documents;

interface DocumentParserInterface
{
    /**
     * Determine if this parser supports the given file and/or mime type.
     */
    public function supports(string $filePath, ?string $mimeType = null): bool;

    /**
     * Parse the document into sequential sections.
     *
     * @return array<int, array{
     *     type: string,
     *     title: ?string,
     *     content: ?string,
     *     heading_level: ?int,
     *     source_index: int,
     *     source_page_start?: ?int,
     *     source_page_end?: ?int
     * }>
     */
    public function parse(string $filePath): array;
}
