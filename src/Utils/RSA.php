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
        for($i = 0; $i < $len; ) {
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
        for($i = 0; $i < $len; ) {
            $fKey = $fKey . substr($privateKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PRIVATE KEY-----";
        return $fKey;
    }

    public static function encrypt($data, $privateKey)
    {
        // $privateKey = RSA::normalPrivateKey($privateKey); // 格式化私钥为标准的 private key

        $res = openssl_private_encrypt($data, $encrypted, $privateKey);

        if(!$res) {
            return false;
        }

        return Str::stringToHex($encrypted);
    }

    public function decrypt($data, $publicKey)
    {
        // $publicKey = RSA::normalPublicKey($publicKey); // 格式化公钥为标准的 public key

        // $openssl_pub = openssl_pkey_get_public($publicKey); // 不知道作用，未使用

        // 验签
        $resArr = openssl_public_decrypt(Str::hexToString($data), $decrypted, $publicKey);

        if(!$resArr){
            return false;
        }

        return $decrypted;
    }
}
