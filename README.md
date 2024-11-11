# MyLogMail
Log Initialization for MyLog package with Mailer

config.php
```php
return [
    'file' => [
        'dst' => 'logs',
        'full' => 1, # keep info
        'debug' => 1, # More priority then full, adding debug to logs
        'per_run' => 0 # Create new log per script run
    ],
    'mail' => [
        'user' => '',
        'pass' => '',
        'smtp' => '',
        'port' => '25',
        'from' => '',
        'to' => '',
        'separate' => '1',
        'subject' => 'My Server'
    ]
];
```
To use, just run
```php
$config = require 'config.php';
new LogInitiation($config);
```
