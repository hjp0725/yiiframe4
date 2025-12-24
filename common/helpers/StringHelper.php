<?php 
/**
 * Optimized for PHP 7.3+ , keep BC.
 */
namespace common\helpers;

use Yii;
use yii\helpers\BaseStringHelper;
use Ramsey\Uuid\Uuid;

/**
 * Class StringHelper
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class StringHelper extends BaseStringHelper
{
    /* ====================== UUID ====================== */

    /**
     * 生成 UUID
     *
     * @param string $type  time/md5/random/sha1/uniqid
     * @param string $name  加密名（md5/sha1 有效）
     * @return string
     * @throws \Exception
     */
    public static function uuid(string $type = 'time', string $name = 'php.net'): string
    {
        switch ($type) {
            case 'time':
                return Uuid::uuid1()->toString();
            case 'md5':
                return Uuid::uuid3(Uuid::NAMESPACE_DNS, $name)->toString();
            case 'random':
                return Uuid::uuid4()->toString();
            case 'sha1':
                return Uuid::uuid5(Uuid::NAMESPACE_DNS, $name)->toString();
            case 'uniqid':
                return md5(uniqid(md5(microtime(true) . self::random(8)), true));
        }
        throw new \InvalidArgumentException('Invalid uuid type: ' . $type);
    }

    /* ====================== 时间戳 ====================== */

    /**
     * 日期 → 时间戳
     * @param mixed $value
     * @return int|mixed
     */
    public static function dateToInt($value)
    {
        if ($value === '' || $value === null) {
            return $value;
        }
        return is_numeric($value) ? (int)$value : strtotime($value);
    }

    /**
     * 时间戳 → 日期
     * @param mixed $value
     * @param string $format
     * @return string
     */
    public static function intToDate($value, string $format = 'Y-m-d H:i:s'): string
    {
        if ($value === '' || $value === null) {
            return date($format);
        }
        return is_numeric($value) ? date($format, (int)$value) : $value;
    }

    /* ====================== 缩略图 ====================== */

    public static function getThumbUrl(string $url, int $width, int $height): string
    {
        $url = str_replace('attachment/images', 'attachment/thumb', $url);
        return self::createThumbUrl($url, $width, $height);
    }

    public static function createThumbUrl(string $url, int $width, int $height): string
    {
        $parts  = explode('/', $url);
        $name   = array_pop($parts);
        [$base, $ext] = explode('.', $name, 2) + ['', ''];
        $parts[] = "{$base}@{$width}x{$height}.{$ext}";
        return implode('/', $parts);
    }

    /* ====================== 压缩别名 ====================== */

    public static function getAliasUrl(string $url, string $alias = 'compress'): string
    {
        $parts  = explode('/', $url);
        $name   = array_pop($parts);
        [$base, $ext] = explode('.', $name, 2) + ['', ''];
        $parts[] = "{$base}@{$alias}.{$ext}";
        return implode('/', $parts);
    }

    /* ====================== 本地路径 ====================== */

    public static function getLocalFilePath(string $url, string $type = 'images'): string
    {
        $attachUrl = Yii::getAlias('@attachurl');
        if (RegularHelper::verify('url', $url)) {
            if (!RegularHelper::verify('url', $attachUrl)) {
                $host = Yii::$app->request->hostInfo . $attachUrl;
                $url  = str_replace($host, '', $url);
            } else {
                $url = str_replace($attachUrl, '', $url);
            }
        } else {
            $url = str_replace($attachUrl, '', $url);
        }
        return Yii::getAlias('@attachment') . $url;
    }

    /* ====================== 枚举解析 ====================== */

    public static function parseAttr($string): array
    {
        $string = (string) $string;          // null 转成 ''
        $array  = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
        if (strpos($string, ':') === false) {
            return $array;
        }
        $value = [];
        foreach ($array as $v) {
            [$k, $v] = explode(':', $v, 2) + ['', ''];
            $value[$k] = $v;
        }
        return $value;
    }

    /* ====================== 其它小工具 ====================== */

    public static function strExists(string $string, string $find): bool
    {
        return strpos($string, $find) !== false;
    }

    /**
     * 安全的 XML → SimpleXMLElement
     */
    public static function simplexmlLoadString(
        string $string,
        string $class_name = 'SimpleXMLElement',
        int $options = 0,
        string $ns = '',
        bool $is_prefix = false
    ) {
        if (preg_match('/(<!DOCTYPE|<!ENTITY)/i', $string)) {
            return false;
        }
        return simplexml_load_string($string, $class_name, $options, $ns, $is_prefix);
    }

    /**
     * 提取汉字
     */
    public static function strToChineseCharacters(string $string): array
    {
        preg_match_all('/[\x{4e00}-\x{9fa5}]+/u', $string, $chinese);
        return $chinese[0] ?? [];
    }

    /**
     * 首字母大写（支持连字符）
     */
    public static function strUcwords(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $str)));
    }

    /**
     * 驼峰 → 下划线
     */
    public static function toUnderScore(string $str): string
    {
        $len = strlen($str);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $ord = ord($str[$i]);
            if ($ord >= 65 && $ord <= 90) {          // A-Z
                if ($i !== 0) {
                    $out .= '-';
                }
                $out .= chr($ord + 32);
            } else {
                $out .= $str[$i];
            }
        }
        return $out;
    }

    /**
     * 取文件扩展名（含点）
     */
    public static function clipping(string $fileName, string $type = '.', int $length = 0): string
    {
        return substr(strtolower(strrchr($fileName, $type)), $length);
    }

    /**
     * 随机字符串
     */
    public static function random(int $length, bool $numeric = false): string
    {
        $seed = base_convert(md5(microtime(true) . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric
            ? (str_replace('0', '', $seed) . '012340567890')
            : ($seed . 'zZ' . strtoupper($seed));

        $hash = '';
        if (!$numeric) {
            $hash  = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }

        $max   = strlen($seed) - 1;
        $chars = str_split($seed);
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * 数字随机串
     */
    public static function randomNum($prefix = false, int $length = 8): string
    {
        $str = $prefix ?: '';
        return $str . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, $length);
    }

    /**
     * 去除注释
     */
    public static function removeAnnotation(string $content): string
    {
        return preg_replace("#(/\*.*?\*/)|(?://.*$)|(?:#.*$)#ms", '',
            str_replace(["\r\n", "\r"], "\n", $content));
    }

    /**
     * 生成随机 code
     */
    public static function code($merchant_id): string
    {
        return substr(md5(date('YmdHis') . rand(0, 999999) . $merchant_id), 16, 16);
    }

    /**
     * 字符串替换
     */
    public static function replace(string $search, string $replace, string $subject, int &$count = null): string
    {
        return str_replace($search, $replace, $subject, $count);
    }

    /**
     * 是否 Windows
     */
    public static function isWindowsOS(): bool
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    /* ====================== 文字排版 ====================== */

    /**
     * 自动换行（GD 版）
     */
    public static function autoWrap(
        int $font_size,
        int $angle,
        string $font_face,
        string $string,
        int $width,
        int $max_line = null
    ): string {
        $content  = '';
        $letters  = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        $lineCnt  = 0;

        foreach ($letters as $l) {
            $testStr = $content . $l;
            $box     = imagettfbbox($font_size, $angle, $font_face, $testStr);
            if ($box[2] > $width && $content !== '') {
                $lineCnt++;
                if ($max_line && $lineCnt >= $max_line) {
                    $content = mb_substr($content, 0, -1) . '...';
                    break;
                }
                $content .= "\n";
            }
            $content .= $l;
        }
        return $content;
    }

    /**
     * 文字省略
     */
    public static function textOmit(string $string, int $num = 26): string
    {
        if (mb_strlen($string) <= $num) {
            return $string;
        }
        return mb_substr($string, 0, $num - 3) . '...';
    }

    /**
     * 多行截取
     */
    public static function textNewLine(string $string, int $num = 26, int $cycle_index = 2): array
    {
        $letters = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        $data    = [];
        for ($i = 0; $i < $cycle_index; $i++) {
            $slice = implode('', array_slice($letters, $i * $num, $num));
            if ($i === $cycle_index - 1 && count($letters) > $cycle_index * $num) {
                $slice .= '...';
            }
            if ($slice !== '') {
                $data[] = $slice;
            }
        }
        return $data;
    }

    /* ====================== 版本号 ====================== */

    /**
     * 版本号 → 整数
     */
    public static function strToInt($string)
    {
        $versionArr = explode('.', $string);
        if (count($versionArr) > 3) {
            return false;
        }

        $version_id = 0;
        isset($versionArr[0]) && $version_id += BcHelper::mul((int)$versionArr[0], 100000000000, 12);
        isset($versionArr[1]) && $version_id += BcHelper::mul((int)$versionArr[1], 10000000, 8);
        isset($versionArr[2]) && $version_id += BcHelper::mul((int)$versionArr[2], 1000, 4);

        return $version_id;
    }

    /* ====================== 脱敏 ====================== */

    /**
     * 字符串脱敏
     */
    public static function hideStr(
        string $string,
        int $begin = 0,
        int $len = 4,
        int $type = 0,
        string $glue = '@'
    ): string {
        if ($string === '') {
            return '';
        }

        $mbLen = mb_strlen($string);
        switch ($type) {
            case 0:   // 左 → 右
                $left  = mb_substr($string, 0, $begin);
                $right = mb_substr($string, $begin + $len);
                $star  = str_repeat('*', $len);
                return $left . $star . $right;

            case 1:   // 右 ← 左
                $left  = mb_substr($string, 0, $mbLen - $begin - $len);
                $right = mb_substr($string, $mbLen - $begin);
                $star  = str_repeat('*', $len);
                return $left . $star . $right;

            case 2:   // 分割后右 ←
                [$a, $b] = explode($glue, $string) + ['', ''];
                return $a . $glue . self::hideStr($b, $begin, $len, 1);

            case 3:   // 分割后左 →
                [$a, $b] = explode($glue, $string) + ['', ''];
                return self::hideStr($a, $begin, $len, 0) . $glue . $b;

            case 4:   // 保留首尾
                $left  = mb_substr($string, 0, $begin);
                $right = mb_substr($string, -$len);
                $star  = str_repeat('*', $mbLen - $begin - $len);
                return $left . $star . $right;
        }
        return $string;
    }

    /* ====================== 截取 / 匹配 ====================== */

    /**
     * 匹配 {} 之间内容
     */
    public static function matchStr(string $str, string $start = '{', string $end = '}'): array
    {
        preg_match_all('/' . preg_quote($start, '/') . '(.*?)' . preg_quote($end, '/') . '/s', $str, $m);
        return $m[1] ?? [];
    }

    /**
     * 截取两个字符串之间
     */
    public static function cut(string $begin, string $end, string $str): string
    {
        $b = mb_strpos($str, $begin);
        if ($b === false) {
            return '';
        }
        $b += mb_strlen($begin);
        $e = mb_strpos($str, $end, $b);
        if ($e === false) {
            return '';
        }
        return mb_substr($str, $b, $e - $b);
    }

    /* ====================== 身份证解析 ====================== */

    /**
     * 身份证 → 性别（1 男 2 女 0 未知）
     */
    public static function get_sex(string $idcard): ?int
    {
        if ($idcard === '') {
            return null;
        }
        $sex = (int)substr($idcard, 16, 1);
        return $sex % 2 === 1 ? 1 : 2;
    }

    /**
     * 身份证 → 生日
     */
    public static function get_birthday(string $idcard): ?string
    {
        if ($idcard === '') {
            return null;
        }
        $ymd = substr($idcard, 6, 8);
        return substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2);
    }

    /**
     * 身份证 → 年龄
     */
    public static function get_age(string $idcard): ?int
    {
        if ($idcard === '') {
            return null;
        }
        $birth = substr($idcard, 6, 8);
        $age   = date('Y') - substr($birth, 0, 4);
        if (date('md') < substr($birth, 4, 4)) {
            $age--;
        }
        return $age;
    }

    /**
     * 身份证 → 地址
     */
    public static function get_address(string $idcard, int $type = 1): ?string
    {
        if ($idcard === '') {
            return null;
        }
        $addr = include __DIR__ . '/../data/address.php';   // 建议把 address.php 放 config/data 目录
        $key6 = substr($idcard, 0, 6);
        $key2 = substr($idcard, 0, 2);

        if ($type === 1) {
            return $addr[$key6] ?? $addr[$key2] ?? '未知地址';
        }
        return $addr[$key2] ?? '未知地址';
    }

    /**
     * 身份证合法性校验
     */
    public static function isIdCard(string $idcard): bool
    {
        $idcard = strtoupper($idcard);
        if (!preg_match('/^\d{17}[\dX]$/', $idcard)) {
            return false;
        }
        $wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $ai = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += $idcard[$i] * $wi[$i];
        }
        return $ai[$sum % 11] === $idcard[17];
    }

    /**
     * 身份证 → 生肖
     */
    public static function get_zodiac(string $idcard): ?string
    {
        if ($idcard === '') {
            return null;
        }
        $year   = (int)substr($idcard, 6, 4);
        $animals = ['猴', '鸡', '狗', '猪', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊'];
        return $animals[($year - 4) % 12];
    }

    /**
     * 身份证 → 星座
     */
    public static function get_starsign(string $idcard): ?string
    {
        if ($idcard === '') {
            return null;
        }
        $m = (int)substr($idcard, 10, 2);
        $d = (int)substr($idcard, 12, 2);

        $signs = [
            [1, 20, '摩羯座'], [2, 19, '水瓶座'], [3, 21, '双鱼座'],
            [4, 20, '白羊座'], [5, 21, '金牛座'], [6, 21, '双子座'],
            [7, 22, '巨蟹座'], [8, 23, '狮子座'], [9, 23, '处女座'],
            [10, 23, '天秤座'], [11, 22, '天蝎座'], [12, 22, '射手座'],
            [12, 31, '摩羯座'],
        ];
        foreach ($signs as $v) {
            if ($m < $v[0] || ($m === $v[0] && $d <= $v[1])) {
                return $v[2];
            }
        }
        return '摩羯座';
    }

    /* ====================== 微信昵称过滤 ====================== */

    /**
     * 过滤微信特殊表情
     */
    public static function wx_name_filter(string $text): string
    {
        // 4 字节表情统一清除
        return preg_replace(
            [
                '/[\x{1F600}-\x{1F64F}]/u',
                '/[\x{1F300}-\x{1F5FF}]/u',
                '/[\x{1F680}-\x{1F6FF}]/u',
                '/[\x{2600}-\x{26FF}]/u',
                '/[\x{2700}-\x{27BF}]/u',
            ],
            '',
            $text
        );
    }

    /**
     * 取子串（兼容老接口）
     */
    public static function get_string_between(string $string, string $start, string $end): string
    {
        $ini = strpos($string, $start);
        if ($ini === false) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini);
        if ($len === false) {
            return '';
        }
        return substr($string, $ini, $len - $ini);
    }
}