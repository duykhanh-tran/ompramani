<?php

namespace App\Services\Documents\Exceptions;

use InvalidArgumentException;

class PdfNoTextLayerException extends InvalidArgumentException
{
    public const ERROR_CODE = 'PDF_NO_TEXT_LAYER';

    public function __construct(
        string $message = 'Tài liệu này không chứa lớp văn bản hoặc có thể là PDF scan. OCR chưa được hỗ trợ trong phiên bản hiện tại.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
