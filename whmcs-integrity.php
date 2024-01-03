<?php
/**
 * WHMCS Integrity Tool
 *
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License")
 * and the Commons Clause Restriction; you may not use this file except in
 * compliance with the License.
 *
 * @category   whmcs
 * @package    whmcs-integrity
 * @author     dqos
 * @copyright  2023 dqos
 * @license    https://github.com/dqos/whmcs-integrity/blob/main/LICENSE.md
 * @link       https://github.com/dqos/whmcs-integrity
 */

// Ensure this script only runs in CLI;
if (php_sapi_name() != 'cli') {
    exit;
}

$sourceFiles = [];
$targetFiles = [];
$customPaths = [];

if (!isset($argv[1]) || !isset($argv[2])) {
    die('Missing params...');
}

// Doing some param checks and load WHMCS files;
if (in_array($argv[1], ['check', 'integrity', 'all'])) {
    if (!isset($argv[3])) {
        die('Missing param...');
    }
    if (!file_exists($argv[3].'/init.php')) {
        die('Could not find target WHMCS files...');
    }
    if (!file_exists($argv[3].'/configuration.php')) {
        die('Could not load active WHMCS configuration.php file...');
    }
    $targetFolder = rtrim($argv[3], '/');
    $whmcsLocation = rtrim($argv[2], '/');

    // Include the WHMCS configuration file in order to load certain settings;
    require_once $targetFolder.'/configuration.php';
}

// Basic WHMCS security checks, there are still a lot of "providers" that fail this;
if (in_array($argv[1], ['check', 'all'])) {
    $getHeaders = get_headers($whmcsLocation, true);
    // Check if it's behind Cloudflare, if so we ignore port checks;
    if (!isset($getHeaders['CF-RAY'])) {
        $getHostname = parse_url($whmcsLocation)['host'];
        if (checkOpenPort($getHostname, 22)) {
            echo 'Sigh...you have your SSH port open to public'.PHP_EOL;
        }
        if (checkOpenPort($getHostname, 3306)) {
            echo 'Sigh...you have your MySQL port open to public'.PHP_EOL;
        }
        if (checkOpenPort($getHostname, 2222) || checkOpenPort($getHostname, 	2083)) {
            echo 'You are running your WHMCS instance in a shared environment'.PHP_EOL;
        }
    }
    if (version_compare(phpversion(), '8.1', '<')) {
        echo 'You are running an outdated PHP version, the newest version supported by WHMCS/ionCube is PHP 8.1. Anything older than 8.0 does not get security patches anymore'.PHP_EOL;
    }
    if (!isUrlSecured($whmcsLocation.'/crons')) {
        echo 'The cron folder is public and a risk'.PHP_EOL;
    }
    if (!isUrlSecured($whmcsLocation.'/downloads')) {
        echo 'The downloads folder is public and a risk'.PHP_EOL;
    }
    if (!isUrlSecured($whmcsLocation.'/attachments')) {
        echo 'The attachments folder is public and a risk'.PHP_EOL;
    }
    if (!isUrlSecured($whmcsLocation.'/templates_c')) {
        echo 'The templates_c folder is public and a risk'.PHP_EOL;
    }
    if (!isUrlSecured($whmcsLocation.'/vendor')) {
        echo 'The vendor folder is unprotected and a risk'.PHP_EOL;
    }
    if (isset($customadminpath) && !isUrlSecured($whmcsLocation.'/'.$customadminpath)) {
        echo 'The admin area is unprotected'.PHP_EOL;
    }
    if (!isset($customadminpath) && !isUrlSecured($whmcsLocation.'/admin')) {
        echo 'The admin area is not customized and is unprotected'.PHP_EOL;
    }
    $configPermissions = getFilePermission($targetFolder.'/configuration.php');
    if (!in_array($configPermissions, [0400, 0440, 0444, 400, 440, 444])) {
        echo 'The permissions of configuration.php are insecure: '.$configPermissions;
    }
}

// Integrity check, creates hashes from WHMCS source folder in order to compare it to your instance;
if (in_array($argv[1], ['integrity', 'all'])) {
    if (!isset($argv[4])) {
        die('Missing param...');
    }

    if (!file_exists($argv[4].'/init.php')) {
        die('Could not find source WHMCS files...');
    }

    $sourceFolder = rtrim($argv[4], '/');

    chdir($sourceFolder);
    $directoryIterator = new RecursiveDirectoryIterator(getcwd(), RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
        $getCleanPath = str_replace(getcwd().'/', '', $file->getPathname());
        if (substr($getCleanPath, 0, strlen('crons')) == 'crons') {
            $sourceFiles['crons'][str_replace('crons/', '', $getCleanPath)] = sha1_file($file->getPathname());
            continue;
        }
        if (substr($getCleanPath, 0, strlen('admin')) == 'admin') {
            $sourceFiles['admin'][str_replace('admin/', '', $getCleanPath)] = sha1_file($file->getPathname());
            continue;
        }
        $sourceFiles['core'][$getCleanPath] = sha1_file($file->getPathname());
    }

    chdir(__DIR__);
    chdir($targetFolder);
    $customPaths['core'] = getcwd().'/';
    $directoryIterator = new RecursiveDirectoryIterator(getcwd(), RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
        $getCleanPath = str_replace(getcwd().'/', '', $file->getPathname());

        if (!isset($crons_dir)) {
            $customPaths['crons'] = getcwd().'crons/';
            if (substr($getCleanPath, 0, strlen('crons')) == 'crons') {
                $targetFiles['crons'][str_replace('crons/', '', $getCleanPath)] = sha1_file($file->getPathname());
                continue;
            }
        } else {
            $customPaths['crons'] = $crons_dir;
            if (strpos($file->getPathname(), $crons_dir) !== false) {
                continue;
            }
        }

        if (isset($customadminpath)) {
            $customPaths['admin'] = getcwd().'/'.$customadminpath.'/';
            if (substr($getCleanPath, 0, strlen($customadminpath)) == $customadminpath) {
                $targetFiles['admin'][str_replace($customadminpath.'/', '', $getCleanPath)] = sha1_file($file->getPathname());
                continue;
            }
        } else {
            $customPaths['admin'] = getcwd().'/admin/';
            if (substr($getCleanPath, 0, strlen('admin')) == 'admin') {
                $targetFiles['admin'][str_replace('admin/', '', $getCleanPath)] = sha1_file($file->getPathname());
                continue;
            }
        }

        $targetFiles['core'][$getCleanPath] = sha1_file($file->getPathname());
    }

    if (isset($crons_dir)) {
        chdir($crons_dir);
        $directoryIterator = new RecursiveDirectoryIterator(getcwd(), RecursiveDirectoryIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
            $getCleanPath = str_replace(getcwd().'/', '', $file->getPathname());
            $targetFiles['crons'][str_replace(getcwd(), '', $getCleanPath)] = sha1_file($file->getPathname());
        }
    }

    // Include an ignore list, these files will be ignored during checks;
    if (file_exists(__DIR__.'/ignore')) {
        $ignoreList = file_get_contents(__DIR__.'/ignore');
        $ignoreList = explode(PHP_EOL, trim($ignoreList));
    }

    foreach ($sourceFiles as $part => $files) {
        foreach ($files as $file => $hash) {
            // Ignore default directories, they should be outside your webroot anyway;
            if (preg_match('/install\/|templates_c\/|downloads\/|attachments\//i', $file)) {
                continue;
            }
            if (isset($ignoreList) && isIgnored($ignoreList, $customPaths[$part].$file)) {
                continue;
            }
            // This means an original WHMCS file is missing in your instance;
            if (!isset($targetFiles[$part][$file])) {
                echo 'Missing original file '.$customPaths[$part].$file.PHP_EOL;
                continue;
            }
            // This means that an original WHMCS file is corrupted or infected in your instance;
            if ($hash != $targetFiles[$part][$file]) {
                echo 'Potential malicious file found '.$customPaths[$part].$file.PHP_EOL;
            }
        }
    }

    foreach ($targetFiles as $part => $files) {
        foreach ($files as $file => $hash) {
            if (isset($ignoreList) && isIgnored($ignoreList, $customPaths[$part].$file)) {
                continue;
            }
            // This means your instance has non-original WHMCS files, this is normal if you have customizations in place;
            if (!isset($sourceFiles[$part][$file])) {
                echo 'Non-original file found '.$customPaths[$part].$file.PHP_EOL;
                continue;
            }
        }
    }
}

// Helper function to loop through ignored files/folders;
function isIgnored($ignoreList, $file)
{
    foreach ($ignoreList as $toIgnore) {
        if (substr($file, 0, strlen($toIgnore)) == $toIgnore) {
            return true;
        }
    }
    return false;
}

// Helper function that fetches HTTP status code;
function isUrlSecured($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (in_array($httpcode, [404, 403])) {
        return true;
    }
    return false;
}

// Helper function that gets correct file permissions on Linux;
function getFilePermission($file)
{
    clearstatcache();
    $length = strlen(decoct(fileperms($file))) - 3;
    return substr(decoct(fileperms($file)), $length);
}

// Helper function to check open ports;
function checkOpenPort($host, $port) {
    $connection = @fsockopen($host, $port, $errno, $errstr, 3);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}
