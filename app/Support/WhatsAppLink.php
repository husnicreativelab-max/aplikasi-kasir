<?php

namespace App\Support;

class WhatsAppLink
{
    public static function normalizeNumber(?string $phone): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }
        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '62'.$number;
        }

        return strlen($number) >= 8 && strlen($number) <= 15 ? $number : null;
    }

    public static function url(?string $phone, string $message): ?string
    {
        $number = self::normalizeNumber($phone);
        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
