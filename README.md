# WHMCS Integrity Tool
This is a script made for a customer on request, allowing them to run this regularly for compliance reasons. *This script is coded quickly, no OOP is used and there is plenty room for improvements.*

### Introduction
WHMCS has an interesting history regarding security, especially so-called module developers are incompetent in coding and ethics. This script is a handy tool which can be used to "scan" your instance for malicious files, mostly caused by exploits.
It's easy to use, just run it. If there is NO output, then nothing is found. This is ideal if you want to automate using the script. If there is output then something has been found.

### Features
- Can do basic but important security checks on your WHMCS instance:
    - Check for default WHMCS folders.
    - Check for open ports (if not using Cloudflare).
    - Check for file permission for configuration.php.
- Can do an integrity check on your WHMCS instance by supplying access to a fresh WHMCS source. It does a comparison using sha1 hashes.
- Can ignore files based on an ignore list.
- Automatically respects custom cron and admin directories during integrity checks.

### Requirements
We generally do not code software for EOL PHP, but still a lot of providers neglect upgrading and run on PHP 7 for some reason.
- PHP 7.x or higher (tested on 8.3.1)
- WHMCS 7.x or higher (tested on 8.8.0)

### Usage
1. Download `whmcs-integrity.php` to a place on your server where you have easy access to your WHMCS instance.
2. Double-check the contents of the file and make sure there is no malicious code in it. The risk running this software is all yours.
3. Let's do a security check first by running: `php whmcs-integrity.php check https://your-whmcs-instance /var/www/path/to/your/whmcs`.
4. In order to do an integrity check, you must have a WHMCS source folder with the exact same version as your instance. Download it from WHMCS website and unzip it.
5. Now run the following command to check integrity: `php whmcs-integrity.php integrity https://your-whmcs-instance /var/www/path/to/your/whmcs /root/whmcs_v880_full/whmcs`.
6. The command in step 5 will return a list with issues found in your instance.
7. If you want to run both a check and an integrity scan for e.g. automation, you can use the `all` option instead of `check` or `integrity`.

#### Example Output
```
Sigh...you have your SSH port open to public
The downloads folder is public and a risk
The admin area is unprotected
Missing original file /var/www/location/file.php
Non-original file found /var/www/location/file.php
Potential malicious file found /var/www/location/file.php
```

### Disclaimer
Using this software is on your own risk, we are not held responsible for any issues.

### Contribute
Contributions are welcome in a form of a pull request (PR).

### License
```Apache License, Version 2.0 and the Commons Clause Restriction```
