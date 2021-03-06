Push Tester
===========
Console application to send push notifications to GCM and APNS.

[![License](http://img.shields.io/:license-mit-blue.svg)](http://doge.mit-license.org)

Installing
----------
To install application as executable:

### Linux and Mac OS X

```bash
$ sudo curl -LsS https://github.com/gigigoapps/push-tester/releases/download/latest/PushTester.phar -o /usr/local/bin/PushTester
$ sudo chmod a+x /usr/local/bin/PushTester
```

### Windows

```bash
c:\> php -r "file_put_contents('PushTester', file_get_contents('https://github.com/gigigoapps/push-tester/releases/download/latest/PushTester.phar'));"
```

Move the downloaded `PushTester` file to your projects directory and execute
it as follows:

```bash
c:\> php PushTester
```

If you prefer to create a global `PushTester` command, execute the following:

```bash
c:\> (echo @ECHO OFF & echo php "%~dp0PushTester" %*) > PushTester.bat
```

Then, move both files (`PushTester` and `PushTester.bat`) to any location included
in your execution path. Now you can run the `PushTester` command anywhere on your
system.


Using
-----
There is `send-push` command to send single push notification.

**Send test push**

For GCM:
```
php PushTester.phar send-push apns 3610ddea5e97b690b1a17247c11c4483264a72cbc8376e52f0fecedba64b7a03 -p /some/dir/path/certificate.pem
```

For APNS:
```
php PushTester.phar send-push gcm APA91bG7VgdMs1Bvi1uNSjHvH6sXUx_gvzHm-zwPXOcUgcECYv198256tmQz1aJ6l2QiI3z9bbBkRURmqvn8gs-PUPNvfQlm8QOZ5JYHBZFXyK2d0ZV1nn9-O8PLcdJWEYeIrQK6I7aZ -g "J79asdfklas-fj98DSFd8of04fm3lk4f89ksdjf"
```

Build new release
-----------------

You need [box2 app](https://github.com/box-project/box2) to generate new releases:

Simply install globally box2 and execute this command from this source root.
```
box build
```