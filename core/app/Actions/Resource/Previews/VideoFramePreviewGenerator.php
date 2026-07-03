<?php

namespace XBB\Actions\Resource\Previews;

use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Exception\ExecutableNotFoundException;
use FFMpeg\FFMpeg;
use FFMpeg\Media\Video;
use SergiX44\ImageZen\Image;

class VideoFramePreviewGenerator implements PreviewGenerator
{
    public function supports(Resource $resource): bool
    {
        return $resource->type === ResourceType::VIDEO && $this->createFfmpeg() !== null;
    }

    public function generate(Resource $resource, ResourceFile $file): ?Image
    {
        $ffmpeg = $this->createFfmpeg();

        if ($ffmpeg === null) {
            return null;
        }

        // ffmpeg operates on files, not streams: use the original path directly on
        // a local disk (no copy) and only fall back to a temp copy on remote disks.
        $localPath = $file->localPath();

        $duration = (float) $ffmpeg->getFFProbe()->format($localPath)->get('duration', 0);
        $seconds = min((float) config('previews.video.frame_seconds'), max($duration - 0.1, 0));

        $framePath = sys_get_temp_dir().'/xbb-frame-'.bin2hex(random_bytes(8)).'.png';

        try {
            /** @var Video $video */
            $video = $ffmpeg->open($localPath);
            $video->frame(TimeCode::fromSeconds($seconds))->save($framePath);

            return Image::make($framePath);
        } finally {
            @unlink($framePath);
        }
    }

    public function createFfmpeg(): ?FFMpeg
    {
        try {
            return FFMpeg::create([
                'ffmpeg.binaries' => config('previews.video.ffmpeg_path'),
                'ffprobe.binaries' => config('previews.video.ffprobe_path'),
                'timeout' => config('previews.video.timeout'),
            ]);
        } catch (ExecutableNotFoundException) {
            return null;
        }
    }
}
