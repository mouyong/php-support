<?php

namespace ZhenMu\Support\Utils;

class RSA
{
    public static function generate($config = [])
    {
        $config = array_merge(array(
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ), $config);

        $privateKeyObj = openssl_pkey_new($config);

        $publicKey = openssl_pkey_get_details($privateKeyObj)['key'];
        openssl_pkey_export($privateKeyObj, $privateKey);

        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];
    }

    public static function singleLinePublicKey($publicKey)
    {
        $string = str_replace([
            "-----BEGIN PUBLIC KEY-----\n",
            "-----END PUBLIC KEY-----",
        ], '', $publicKey);

        $stringArray = explode("\n", $string);
        $result = implode('', $stringArray);

        return $result;
    }

    public static function singleLinePrivateKey($privateKey)
    {
        $string = str_replace([
            "-----BEGIN PRIVATE KEY-----\n",
            "-----END PRIVATE KEY-----",
        ], '', $privateKey);

        $stringArray = explode("\n", $string);
        $result = implode('', $stringArray);

        return $result;
    }

    public static function normalPublicKey($publicKey)
    {
        $fKey = "-----BEGIN PUBLIC KEY-----\n";
        $len = strlen($publicKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($publicKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PUBLIC KEY-----";
        return $fKey;
    }

    public static function normalPrivateKey($privateKey)
    {
        $fKey = "-----BEGIN PRIVATE KEY-----\n";
        $len = strlen($privateKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($privateKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PRIVATE KEY-----";
        return $fKey;
    }

    public static function chunkEncrypt($data, $publicKey, $keySize = 2048)
    {
        if (!$data) {
            return null;
        }

        if (!is_string($data)) {
            $data = json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (str_contains($publicKey, "PUBLIC") === false) {
            $publicKey = RSA::normalPublicKey($publicKey); // 格式化公钥为标准的 public key
        }

        $plaintext = $data;
        $chunkSize = $keySize / 8 - 11;

        $output = "";
        while ($plaintext) {
            $chunk = substr($plaintext, 0, $chunkSize);
            $plaintext = substr($plaintext, $chunkSize);
            openssl_public_encrypt($chunk, $encrypted, $publicKey);
            $output .= $encrypted;
        }

        return Str::stringToHex($output);
    }

    public static function chunkDecrypt($data, $privateKey, $keySize = 2048)
    {
        if (!$data) {
            return null;
        }

        if (str_contains($privateKey, "PRIVATE") === false) {
            $privateKey = RSA::normalPrivateKey($privateKey); // 格式化私钥为标准的 private key
        }

        $output = Str::hexToString($data);
        
        $plaintext = "";
        while ($output) {
            $chunk = substr($output, 0, $keySize / 8);
            $output = substr($output, $keySize / 8);
            openssl_private_decrypt($chunk, $decrypted, $privateKey);
            $plaintext .= $decrypted;
        }

        return $plaintext;
    }

    public static function encrypt($data, $privateKey)
    {
        if (!$data) {
            return null;
        }

        if (!is_string($data)) {
            $data = json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (str_contains($privateKey, "PRIVATE") === false) {
            $privateKey = RSA::normalPrivateKey($privateKey); // 格式化私钥为标准的 private key
        }

        $res = openssl_private_encrypt($data, $encrypted, $privateKey);

        if (!$res) {
            return false;
        }

        return Str::stringToHex($encrypted);
    }

    public static function decrypt($data, $publicKey)
    {
        if (!$data) {
            return null;
        }

        if (!is_string($data)) {
            return false;
        }

        if (str_contains($publicKey, "PUBLIC") === false) {
            $publicKey = RSA::normalPublicKey($publicKey); // 格式化公钥为标准的 public key
        }

        // $openssl_pub = openssl_pkey_get_public($publicKey); // 不知道作用，未使用

        // 验签
        $resArr = openssl_public_decrypt(Str::hexToString($data), $decrypted, $publicKey);

        if (!$resArr) {
            return false;
        }

        return $decrypted;
    }
}
