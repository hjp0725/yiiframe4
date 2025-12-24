<?php 

namespace common\helpers;

/**
 * FFmpeg 助手
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class FfmpegHelper
{
    /** @var string 可执行文件绝对路径（注意末尾空格） */
    private static $ffmpegPath = '/usr/bin/ffmpeg ';

    /* ====================== 转码 ====================== */

    /**
     * 视频转码
     *
     * @param string $source  源文件
     * @param string $target  目标文件
     * @throws \RuntimeException
     */
    public static function transcoding(string $source, string $target): void
    {
        $cmd = [trim(self::$ffmpegPath), '-i', $source, '-y', $target];
        self::run($cmd);
    }

    /* ====================== 截图 ====================== */

    /**
     * 截取视频指定帧
     *
     * @param string $filePath  视频绝对路径
     * @param string $imagePath 输出图片绝对路径
     * @param string $second    时间戳 00:00:01
     * @throws \RuntimeException
     */
    public static function imageResize(string $filePath, string $imagePath, string $second): void
    {
        $cmd = [
            trim(self::$ffmpegPath),
            '-ss', $second,
            '-i', $filePath,
            '-r', '1',
            '-vframes', '1',
            '-an',
            '-f', 'mjpeg',
            '-y', $imagePath,
        ];
        self::run($cmd);
    }

    /* ====================== 信息 ====================== */

    /**
     * 获取视频信息
     *
     * 返回字段：
     * duration/seconds/start/bitrate/vcodec/vformat/resolution/width/height/
     * acodec/asamplerate/play_time/size
     *
     * @param string $file 绝对路径
     * @return array
     */
    public static function getVideoInfo(string $file): array
    {
        $cmd = [trim(self::$ffmpegPath), '-i', $file];
        $raw = self::run($cmd, true);

        $info = [];

        // Duration: 00:02:28.63, start: 0.000000, bitrate: 1606 kb/s
        if (preg_match('/Duration:\s*([\d:.]+),\s*start:\s*([\d.]+),\s*bitrate:\s*(\d+)\s*kb\/s/', $raw, $m)) {
            $info['duration']  = $m[1];
            $info['start']     = (float) $m[2];
            $info['bitrate']   = (int) $m[3];

            // 秒数
            [$h, $i, $s] = explode(':', $m[1]);
            $info['seconds'] = $h * 3600 + $i * 60 + (float) $s;
            $info['play_time'] = $info['seconds'] + $info['start'];
        }

        // Video: h264 (Main), yuv420p, 1280x720, 475 kb/s, 29.97 fps
        if (preg_match('/Video:\s*([^,]+),\s*([^,]+),\s*(\d+x\d+)/', $raw, $m)) {
            [$info['vcodec'], $info['vformat'], $info['resolution']] = [$m[1], $m[2], $m[3]];
            [$info['width'], $info['height']] = array_map('intval', explode('x', $m[3]));
        }

        // Audio: aac (LC), 44100 Hz
        if (preg_match('/Audio:\s*([^,]+),\s*(\d+)\s*Hz/', $raw, $m)) {
            $info['acodec']      = $m[1];
            $info['asamplerate'] = (int) $m[2];
        }

        $info['size'] = filesize($file);

        return $info;
    }

    /* ====================== 底层 ====================== */

    /**
     * 执行命令
     *
     * @param array $cmd  命令数组
     * @param bool  $stderr 是否返回 stderr
     * @return string
     * @throws \RuntimeException
     */
    private static function run(array $cmd, bool $stderr = false): string
    {
        $des = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $des, $pipes);
        if (!is_resource($proc)) {
            throw new \RuntimeException('无法启动 FFmpeg 进程');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($proc);
        if ($code !== 0) {
            throw new \RuntimeException("FFmpeg 异常 (exit {$code}): {$stderr}");
        }

        return $stderr ? $stderr : $stdout;
    }
}