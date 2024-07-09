<?php

namespace PureDevLabs;

class Parser
{
    # region Public Methods
    public function ParseItagInfo($itag)
    {
        if (isset($this->itagDetailed[$itag]))
        {
            return $this->itagDetailed[$itag];
        }

        return 'Unknown';
    }
    public function ParseItagFileExt($itag)
    {
        if (isset($this->itagFileExt[$itag]))
        {
            return $this->itagFileExt[$itag];
        }

        return 'Unknown';
    }

    public function FormatedStreamsByItag()
    {
        return array(17, 18, 22);
    }

    public function AudioStreamsByItag()
    {
        return array(139, 140, 249, 250, 251, 256, 258, 327, 338);
    }

    public function VideoStreamsByItag()
    {
        return array(
            701, 700, 699, 698, 697, 696, 695, 694, 571, 402, 401,
            400, 399, 398, 397, 396, 395, 394, 337, 336, 335, 334,
            333, 332, 331, 330, 272, 315, 308, 303, 302, 313, 271,
            248, 247, 244, 243, 242, 278, 305, 304, 299, 298, 266,
            264, 137, 136, 135, 134, 133, 160
        );
    }

    #endregion

    // itag info does not change frequently, that is why we cache it here as a plain static array
    private $itagDetailed = array(
        // Dash Videos
        701 => 'mp4, video, AV1 HFR High, 2160p, 60 FPS, dash',
        700 => 'mp4, video, AV1 HFR High, 1440p, 60 FPS, dash',
        699 => 'mp4, video, AV1 HFR High, 1080p, 60 FPS, dash',
        698 => 'mp4, video, AV1 HFR High, 720p, 60 FPS, dash',
        697 => 'mp4, video, AV1 HFR High, 480p, 60 FPS, dash',
        696 => 'mp4, video, AV1 HFR High, 360p, 60 FPS, dash',
        695 => 'mp4, video, AV1 HFR High, 240p, 60 FPS, dash',
        694 => 'mp4, video, AV1 HFR High, 144p, 60 FPS, dash',
        571 => 'mp4, video, AV1 HFR, 4320p, 60 FPS, dash',
        402 => 'mp4, video, AV1 HFR, 4320p, 60 FPS, dash',
        401 => 'mp4, video, AV1 HFR, 2160p, 60 FPS, dash',
        400 => 'mp4, video, AV1 HFR, 1440p, 60 FPS, dash',
        399 => 'mp4, video, AV1 HFR, 1080p, 60 FPS, dash',
        398 => 'mp4, video, AV1 HFR, 720p, 60 FPS, dash',
        397 => 'mp4, video, AV1, 480p, 30 FPS, dash',
        396 => 'mp4, video, AV1, 360p, 30 FPS, dash',
        395 => 'mp4, video, AV1, 240p, 30 FPS, dash',
        394 => 'mp4, video, AV1, 144p, 30 FPS, dash',
        337 => 'webm, video, VP9.2 HDR HFR, 2160p, 60 FPS, dash',
        336 => 'webm, video, VP9.2 HDR HFR, 1440p, 60 FPS, dash',
        335 => 'webm, video, VP9.2 HDR HFR, 1080p, 60 FPS, dash',
        334 => 'webm, video, VP9.2 HDR HFR, 720p, 60 FPS, dash',
        333 => 'webm, video, VP9.2 HDR HFR, 480p, 60 FPS, dash',
        332 => 'webm, video, VP9.2 HDR HFR, 360p, 60 FPS, dash',
        331 => 'webm, video, VP9.2 HDR HFR, 240p, 60 FPS, dash',
        330 => 'webm, video, VP9.2 HDR HFR, 144p, 60 FPS, dash',
        272 => 'webm, video, VP9 HFR, 4320p, 60 FPS, dash',
        315 => 'webm, video, VP9 HFR, 2160p, 60 FPS, dash',
        308 => 'webm, video, VP9 HFR, 1440p, 60 FPS, dash',
        303 => 'webm, video, VP9 HFR, 1080p, 60 FPS, dash',
        302 => 'webm, video, VP9 HFR, 720p, 60 FPS, dash',
        313 => 'webm, video, VP9, 2160p, 30 FPS, dash',
        271 => 'webm, video, VP9, 1440p, 30 FPS, dash',
        248 => 'webm, video, VP9, 1080p, 30 FPS, dash',
        247 => 'webm, video, VP9, 720p, 30 FPS, dash',
        244 => 'webm, video, VP9, 480p, 30 FPS, dash',
        243 => 'webm, video, VP9, 360p, 30 FPS, dash',
        242 => 'webm, video, VP9, 240p, 30 FPS, dash',
        278 => 'webm, video, VP9, 144p, 30 FPS, dash',
        305 => 'mp4, video, H.264 HFR, 2160p, 30 FPS, dash',
        304 => 'mp4, video, H.264 HFR, 1440p, 60 FPS, dash',
        299 => 'mp4, video, H.264 HFR, 1080p, 60 FPS, dash',
        298 => 'mp4, video, H.264 HFR, 720p, 60 FPS, dash',
        266 => 'mp4, video, H.264, 2160p, 30 FPS, dash',
        264 => 'mp4, video, H.264, 1440p, 30 FPS, dash',
        137 => 'mp4, video, H.264, 1080p, 30 FPS, dash',
        137 => 'mp4, video, H.264, 1080p, 30 FPS, dash',
        136 => 'mp4, video, H.264, 720p, 30 FPS, dash',
        135 => 'mp4, video, H.264, 480p, 30 FPS, dash',
        134 => 'mp4, video, H.264, 360p, 30 FPS, dash',
        133 => 'mp4, video, H.264, 240p, 30 FPS, dash',
        160 => 'mp4, video, H.264, 144p, 30 FPS, dash',
        // Dash Audio
        139 => 'mp4, audio, AAC (HE v1), 48 Kbps, Stereo, dash',
        140 => 'mp4, audio, AAC (LC), 128 Kbps, Stereo, dash',
        141 => 'mp4, audio, AAC (LC), 256 Kbps, Stereo, dash',
        249 => 'WebM, audio, Opus, (VBR) ~50 Kbps, Stereo, dash',
        250 => 'WebM, audio, Opus, (VBR) ~70 Kbps, Stereo, dash',
        251 => 'WebM, audio, Opus, (VBR) <=160 Kbps, Stereo, dash',
        256 => 'MP4, audio, AAC (HE v1), 192 Kbps, Surround (5.1), dash',
        258 => 'MP4, audio, AAC (LC), 384 Kbps, Surround (5.1), dash',
        327 => 'MP4, audio, AAC (LC), 256 Kbps, Surround (5.1), dash',
        327 => 'WebM, audio, Opus, (VBR) ~480 Kbps, Quadraphonic (4), dash',
        // Legacy (non-DASH)
        17 => '3gp, video/audio, H.263, AAC (LC), 144p, 24 Kbps, mono, non-dash',
        18 => 'mp4, video/audio, H.264, AAC (LC), 360p, 96 Kbps, Stereo, non-dash',
        22 => 'mp4, video/audio, H.264, AAC (LC), 720p, 192 Kbps, Stereo, non-dash',
    );

    private $itagFileExt = array(
        701 => 'mp4',
        700 => 'mp4',
        699 => 'mp4',
        698 => 'mp4',
        697 => 'mp4',
        696 => 'mp4',
        695 => 'mp4',
        694 => 'mp4',
        571 => 'mp4',
        402 => 'mp4',
        401 => 'mp4',
        400 => 'mp4',
        399 => 'mp4',
        398 => 'mp4',
        397 => 'mp4',
        396 => 'mp4',
        395 => 'mp4',
        394 => 'mp4',
        337 => 'webm',
        336 => 'webm',
        335 => 'webm',
        334 => 'webm',
        333 => 'webm',
        332 => 'webm',
        331 => 'webm',
        330 => 'webm',
        272 => 'webm',
        315 => 'webm',
        308 => 'webm',
        303 => 'webm',
        302 => 'webm',
        313 => 'webm',
        271 => 'webm',
        248 => 'webm',
        247 => 'webm',
        244 => 'webm',
        243 => 'webm',
        242 => 'webm',
        278 => 'webm',
        305 => 'mp4',
        304 => 'mp4',
        299 => 'mp4',
        298 => 'mp4',
        266 => 'mp4',
        264 => 'mp4',
        137 => 'mp4',
        136 => 'mp4',
        135 => 'mp4',
        134 => 'mp4',
        133 => 'mp4',
        160 => 'mp4',
        17 => '3gp',
        18 => 'mp4',
        22 => 'mp4',
        139 => 'mp4',
        140 => 'mp4',
        249 => 'webm',
        250 => 'webm',
        251 => 'webm',
        256 => 'mp4',
        258 => 'mp4',
        327 => 'mp4',
        338 => 'webm'
    );
}
