<?php

return [
    'import' => [
        'disk' => env('IMPORT_DISK', env('DOCX_IMPORT_DISK', 'local')),
        'directory' => env('IMPORT_DIR', env('DOCX_IMPORT_DIR', 'source_documents')),
        'max_file_size' => env('IMPORT_MAX_FILE_SIZE_KB', env('DOCX_IMPORT_MAX_SIZE_KB', 20480)), // 20 MB
    ],
];
