PHP Example
===========

This is a simple example that accesses the
[xmlstats](https://erikberg.com/api) API using PHP. It uses
[cURL](https://curl.haxx.se) to issue and receive HTTP requests and
responses. It uses [memcache](http://memcached.org) to store the HTTP
responses.

Requirements
------------
PHP 5.4+ with cURL and memcache modules

Getting Started
---------------
Clone the repository. Install memcache if it is not already installed. Instructions
will vary depending on your operating system.

### Configure
Specify your API access token and e-mail address in `xmlstats.ini`. Additionally, specify
your memcache host and port if it is not running on your local server at the default port.

### Run
Versions of PHP 5.4 and later provide a built-in server to develop and test PHP scripts quickly. Start
a server running on your localhost at port 8000.
```
php -S localhost:8000
```

Start a memcache server according to your operating system.

Point your web browser to http://localhost:8000.

