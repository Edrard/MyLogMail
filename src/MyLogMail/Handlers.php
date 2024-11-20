<?php
namespace edrard\MyLogMail;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Handlers
{
    private static $stdout = [];

    public static function stdout(){
        if(static::$stdout != []){
            return static::$stdout;
        }
        static::$stdout = [
            new StreamHandler('php://stdout', Logger::INFO),
            new StreamHandler('php://stdout', Logger::CRITICAL),
            new StreamHandler('php://stdout', Logger::WARNING),
            new StreamHandler('php://stdout', Logger::ERROR),
            new StreamHandler('php://stdout', Logger::DEBUG)
        ];
        return static::$stdout;
    }


}