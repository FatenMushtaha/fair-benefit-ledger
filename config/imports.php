<?php

return [
    'max_bytes' => (int) env('IMPORT_MAX_BYTES', 10 * 1024 * 1024),
    'max_rows' => (int) env('IMPORT_MAX_ROWS', 5000),
    'chunk_size' => (int) env('IMPORT_CHUNK_SIZE', 250),
];
