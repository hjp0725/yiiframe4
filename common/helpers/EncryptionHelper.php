<?php 

namespace common\helpers;

use yii\web\UnprocessableEntityHttpException;

/**
 * 加密 & 签名助手
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class EncryptionHelper
{
    /* ====================== RSA ====================== */

    /**
     * RSA 私钥加密（兼容 PKCS#8/PKCS#1）
     *
     * @param string $data        明文
     * @param string $privateKey  PEM 文件绝对路径
     * @return string             Base64 密文
     * @throws \RuntimeException
     */
    public static function rsaEnCode(string $data, string $privateKey): string
    {
        $key = FileHelper::readKey($privateKey);
        $res = openssl_get_privatekey($key);
        if ($res === false) {
            throw new \RuntimeException('Invalid RSA private key.');
        }

        openssl_private_encrypt($data, $encrypted, $res);
        return base64_encode($encrypted);
    }

    /**
     * RSA 公钥解密
     *
     * @param string $data       Base64 密文
     * @param string $publicKey  PEM 文件绝对路径
     * @return string            明文
     * @throws \RuntimeException
     */
    public static function rsaDeCode(string $data, string $publicKey): string
    {
        $key = FileHelper::readKey($publicKey);
        $res = openssl_get_publickey($key);
        if ($res === false) {
            throw new \RuntimeException('Invalid RSA public key.');
        }

        openssl_public_decrypt(base64_decode($data, true), $decrypted, $res);
        return $decrypted;
    }

    /* ====================== 签名 ====================== */

    /**
     * 生成带签名的 URL 查询串
     *
     * @param array  $paramArr  业务参数
     * @param string $secret    appSecret
     * @param string $signName  签名键名
     * @return string           可直接拼接到 URL 的字符串
     */
    public static function createUrlParam(array $paramArr, string $secret, string $signName = 'sign'): string
    {
        // 生成不可预测 nonce
        if (!isset($paramArr['nonceStr'])) {
            $paramArr['nonceStr'] = bin2hex(random_bytes(8));
        }

        ksort($paramArr);
        $query = http_build_query($paramArr, '', '&', PHP_QUERY_RFC3986);
        $sign  = hash('sha256', $query . $secret);

        return $query . '&' . $signName . '=' . $sign;
    }

    /**
     * 校验 URL 查询串签名
     *
     * @param array  $paramArr  收到的全部参数
     * @param string $secret    appSecret
     * @param string $signName  签名键名
     * @return bool             通过返回 true
     * @throws UnprocessableEntityHttpException
     */
    public static function decodeUrlParam(array $paramArr, string $secret, string $signName = 'sign'): bool
    {
        $sign = $paramArr[$signName] ?? '';
        unset($paramArr[$signName]);

        ksort($paramArr);
        $calc = hash('sha256', http_build_query($paramArr, '', '&', PHP_QUERY_RFC3986) . $secret);

        if (!hash_equals($calc, $sign)) {
            $msg = YII_DEBUG ? "签名错误: expect {$calc}, given {$sign}" : '签名错误';
            throw new UnprocessableEntityHttpException($msg);
        }

        return true;
    }
}

/**
 * 文件助手（内部使用）
 */
class FileHelper
{
    public static function readKey(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Unable to read key file: {$path}");
        }
        return $content;
    }
}