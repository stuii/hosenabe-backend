<?php

namespace HoseAbe\Resources;

class RandomString
{
    private const CHARS = 'CqIas6$HZgY:ajOx{uvnw(@xSl*r1-tZ!1i_im>poX)GsVudmhIp]v9zzB8rbC}<W=7egMkd,U0K2Rq2y9e#+8QfNQEtcDPn3yGXkR.6[PYESc3V4HwBhL7MODJA5J4bKUfT0o5NTFWjlFL';

    public static function generate(int $length = 80): string
    {
        $randomString = '';
        for($i = 0; $i < $length; $i++){
            $randomString .= self::CHARS[rand(0,strlen(self::CHARS)-1)];
        }
        return $randomString;
    }

    public static function isValid(string $string): bool
    {
        foreach(str_split($string) as $char){
            if(!str_contains(self::CHARS, $char)){ return false;}
        }
        return true;
    }
}