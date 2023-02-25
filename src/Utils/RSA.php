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
}
