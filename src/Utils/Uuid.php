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
    
    public static function getCurrentSerialNumber(string $modelClass, $serialNumberField = 'serial_number'): int
    {
        return $modelClass::whereDate('created_at', now())->max($serialNumberField) ?? 0;
    }
    
    public static function generateNextSerialNumberNo(int $serialNumber): string
    {
        $nextSerialNumber = $serialNumber + 1;

        $no = str_pad($nextSerialNumber, 3, '0', STR_PAD_LEFT);

        return date('ymd') . $no;
    }
}
