<?php
namespace edrard\MyLogMail;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Handlers
{
    public static $stdout = [
        new StreamHandler('php://stdout', Logger::INFO),
        new StreamHandler('php://stdout', Logger::INFO),
        new StreamHandler('php://stdout', Logger::INFO),
        new StreamHandler('php://stdout', Logger::INFO),
        new StreamHandler('php://stdout', Logger::INFO)
    ];


}