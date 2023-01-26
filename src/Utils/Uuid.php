<?php

namespace ZhenMu\Support\Utils;

use Illuminate\Support\Facades\DB;

class Uuid
{
    public static function uuid($hex = false)
    {
        if ($hex) {
            return \Ramsey\Uuid\Uuid::uuid4()->getHex()->toString(); // like: 6b2092378b014528b30b4a9b5fab3ba7
        }

        return \Ramsey\Uuid\Uuid::uuid4()->toString(); // mysql uuid, like: 6b209237-8b01-4528-b30b-4a9b5fab3ba7
    }
    
    public static function getCurrentSerialNumber(string $modelClass, $serialNumberField = 'serial_number'): int
    {
        return $modelClass::whereDate('created_at', now())->max(DB::raw("cast(`{$serialNumberField}` as UNSIGNED INTEGER)")) ?? 0;
    }
    
    public static function generateNextSerialNumberNo(int $serialNumber, int $padLength = 3): string
    {
        $nextSerialNumber = $serialNumber + 1;

        $no = str_pad($nextSerialNumber, $padLength, '0', STR_PAD_LEFT);

        return date('ymd') . $no;
    }
}
