<?php

return [
    // Longest side of the generated preview, in pixels
    'max_dimension' => (int) env('PREVIEWS_MAX_DIMENSION', 600),

    // WEBP encoding quality (0-100)
    'quality' => (int) env('PREVIEWS_QUALITY', 80),

    // Raster images larger than this (bytes) get a downscaled preview
    'raster_size_threshold' => (int) env('PREVIEWS_RASTER_SIZE_THRESHOLD', 512 * 1024),

    // Rasterization density (DPI) for vector sources (SVG, PDF)
    'density' => (int) env('PREVIEWS_DENSITY', 150),

    'video' => [
        'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
        'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),
        'timeout' => (int) env('FFMPEG_TIMEOUT', 120),
        // Timestamp (seconds) of the extracted frame, clamped to the video duration
        'frame_seconds' => (float) env('PREVIEWS_FRAME_SECONDS', 1.0),
    ],
];
