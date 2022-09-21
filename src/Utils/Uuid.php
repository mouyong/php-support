<?php

namespace ZhenMu\Support\Utils;

class Uuid
{
    public static function uuid($hex = true)
    {
        if ($hex) {
            return \Ramsey\Uuid\Uuid::uuid4()->getHex()->toString();
        }

        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
    
    public static function generateNextSerialNumber(int $serialNumber): string
    {
        $nextSerialNumber = $serialNumber + 1;

        $no = str_pad($nextSerialNumber, 3, '0', STR_PAD_LEFT);

        return date('ymd') . $no;
    }
}
