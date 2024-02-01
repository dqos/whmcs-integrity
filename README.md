# WHMCS Integrity Tool
This is a script made for a customer on request, allowing them to run this regularly for compliance reasons. 

*This script is coded quickly, no OOP is used and there is plenty room for improvements.*

### Introduction
WHMCS has an interesting history regarding security, especially so-called module developers are incompetent in programming and ethics. This script is a handy tool which can be used to "scan" your instance for malicious files, mostly caused by exploits.

It's easy to use, just run it. If there is NO output, then nothing is found. This is ideal if you want to automate using the script. If there is output then a problem was detected.

### Features
- Can do basic but important security checks on your WHMCS instance:
    - Check for default WHMCS folders.
    - Check for open ports (if not using Cloudflare).
    - Check for file permission for configuration.php.
- Can do an integrity check on your WHMCS instance by supplying access to a (fresh) WHMCS source. Comparison is perfromed with sha1 hashes.
- Can ignore files based on an ignore list.
- Automatically respects custom cron and admin directories during integrity checks.
- **New**: Adds a conditional file deletion feature, which deletes specified files at a configurable interval.
- **New**: Webhook notification feature to send alerts when security issues are detected.

### Requirements
We generally do not code software for EOL PHP, but still a lot of providers neglect upgrading and run on PHP 7 for some reason.
- PHP 7.x or higher (tested on 8.3.1)
- WHMCS 7.x or higher (tested on 8.8.0)

### Usage
1. Download `whmcs-integrity.php` to a place on your server where you can access your WHMCS instance easily.
2. Double-check the contents of the file and make sure there is no malicious code in it. if you give it bad data (and overall), the risk running this software is all yours.
3. To use the ignore feature, create a file named `ignore` in the same directory as the `whmcs-integrity.php` script. This file should contain a list of file paths that you want to exclude from the integrity check. Each path should be on a new line. For example:
    ```
    /path/to/ignored/file1.php
    /path/to/ignored/file2.php
    ```
    The script will automatically read this file and exclude these files from being checked for integrity.
4. Let's do an intial security check by running: `php whmcs-integrity.php check https://your-whmcs-instance-url.com /var/www/path/to/your/whmcs`.
5. In order to do an integrity check, you must have a WHMCS source folder with the exact same version as your instance. Download it from WHMCS website and unzip it.
6. Now run the following command to check integrity: `php whmcs-integrity.php integrity https://your-whmcs-instance-url.com /var/www/path/to/your/whmcs /path/towhmcs/source`.
7. The command in step 4 will return a list with issues found in your instance.
8. If you want to run both a check and an integrity scan for e.g. automation, you can use the `all` option instead of `check` or `integrity`.
9. **New** - The conditional file deletion feature can be configured by modifying the `$deleteList` array and the `$deletionIntervalDays` variable in the script. It deletes files listed in `$deleteList` if the last deletion was more than the specified number of days ago.
10. **New** - Enable the webhook notification by setting `$enableWebhook` to `true` and defining `$webhookUrl`. When security issues are detected, the script sends a JSON payload to this URL with issue details and a unique event ID.

#### Example Script Output
```
Sigh...you have your SSH port open to public
The downloads folder is public and a risk
The admin area is unprotected
Missing original file /var/www/location/file.php
Non-original file found /var/www/location/file.php
Potential malicious file found /var/www/location/file.php
Deleted file /var/www/location/file.php
```

#### Example Webhook JSON Payload
```json
{
  "message": "Security issues detected on your WHMCS instance",
  "description": "Example output of the script listing detected issues",
  "status": "trigger",
  "event_id": "1234"
}
```

### Disclaimer
Using this software is on your own risk, we are not held responsible for any issues.

### Contribute
Contributions are welcome in a form of a pull request (PR).

### License
```Apache License, Version 2.0 and the Commons Clause Restriction```
