
# Stackify Monolog v1 Handler

Monolog handler for sending log messages and exceptions to Stackify.
Monolog 1.x is supported.

> For Monolog v2, use the [2.x branch](https://github.com/stackify/stackify-log-monolog/tree/2.x)

* **Errors and Logs Overview:** http://support.stackify.com/errors-and-logs-overview/
* **Sign Up for a Trial:** http://www.stackify.com/sign-up/

## Installation

Install the latest version with `composer require stackify/monolog "~1.0"`

### Installation with Linux Agent

This is the suggested installation option, offering the best logging performance.
 
**PHP:**
```php
use Monolog\Logger;
use Stackify\Log\Monolog\Handler as StackifyHandler;

$handler = new StackifyHandler('application_name', 'environment_name');
$logger = new Logger('logger');
$logger->pushHandler($handler);
```

**Symfony:**
```yml
services:
    stackify_handler:
        class: "Stackify\\Log\\Monolog\\Handler"
        arguments: ["application_name", "environment_name"]
monolog:
    handlers:
        stackify:
            type: service
            id: stackify_handler
```


#### Optional Settings

**Log Server Environment Variables**
- Server environment variables can be added to error log message metadata. **Note:** This will log all 
system environment variables; do not enable if sensitive information such as passwords or keys are stored this way.

 ```php
$handler = new StackifyHandler('application_name', 'environment_name', null, true); 
```

### Installation without Linux Agent

This option does not require a Stackify Agent to be installed because it sends data directly to Stackify services. It collects log entries in batches, calls curl using the ```exec``` function, and sends data to the background immediately [```exec('curl ... &')```]. This will affect the performance of your application minimally, but it requires permissions to call ```exec``` inside the PHP script and it may cause silent data loss in the event of any network issues. This transport method does not work on Windows. To configure ExecTransport you need to pass the environment name and API key (license key):

**PHP:**
```php
use Stackify\Log\Transport\ExecTransport;
use Stackify\Log\Monolog\Handler as StackifyHandler;

$transport = new ExecTransport('api_key');
$handler = new StackifyHandler('application_name', 'environment_name', $transport);
$logger = new Logger('logger');
$logger->pushHandler($handler);
```

**Symfony:**
```yml
services:
    stackify_transport:
        class: "Stackify\\Log\\Transport\ExecTransport"
        arguments: ["api_key"]
    stackify_handler:
        class: "Stackify\\Log\\Monolog\\Handler"
        arguments: ["application_name", "environment_name", "@stackify_transport"]
monolog:
    handlers:
        stackify:
            type: service
            id: stackify_handler
```

#### Optional Configuration

**Proxy**
- ExecTransport supports data delivery through proxy. Specify proxy using [libcurl format](http://curl.haxx.se/libcurl/c/CURLOPT_PROXY.html): <[protocol://][user:password@]proxyhost[:port]>
```php
$transport = new ExecTransport($apiKey, ['proxy' => 'https://55.88.22.11:3128']);
```

**Curl path**
- It can be useful to specify ```curl``` destination path for ExecTransport. This option is set to 'curl' by default.
```php
$transport = new ExecTransport($apiKey, ['curlPath' => '/usr/bin/curl']);
```

**Log Server Environment Variables**
- Server environment variables can be added to error log message metadata. **Note:** This will log all 
system environment variables; do not enable if sensitive information such as passwords or keys are stored this way.

 ```php
$handler = new StackifyHandler('application_name', 'environment_name', $transport, true); 
```

## Notes

To get more error details pass Exception objects to the logger if available:
```php
try {
    $db->connect();
catch (DbException $ex) {
    // you may use any key name
    $logger->addError('DB is not available', ['ex' => $ex]);
}
```

## Troubleshooting
If transport does not work, try looking into ```vendor\stackify\logger\src\Stackify\debug\log.log``` file (if it is available for writing). Errors are also written to global PHP [error_log](http://php.net/manual/en/errorfunc.configuration.php#ini.error-log).
Note that ExecTransport does not produce any errors at all, but you can switch it to debug mode:
```php
$transport = new ExecTransport($apiKey, ['debug' => true]);
```

## License

Copyright 2019 Stackify, LLC.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
