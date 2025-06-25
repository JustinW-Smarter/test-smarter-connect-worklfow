<?php
class IntestingHelper
{
    public static function InTesting()
    {
        session_start();
        $inTesting = in_array($_SERVER["REMOTE_ADDR"], [
            // '217.100.38.106', // Kantoor Smarter
            // '89.205.139.223',
            // '83.86.78.158', // Smarter Connect
            // '2001:1c03:5f1b:3500:7055:d2c:e6d9:ec92',
            // '217.104.26.233', // Jaime Thuis
            // '95.99.101.228', // Kevin Thuis
        ]);
        return true;
    }
}
