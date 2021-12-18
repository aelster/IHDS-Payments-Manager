<?php
//set timezone
date_default_timezone_set('America/Los_Angeles');

// Turn off output buffering
ini_set('output_buffering', 'off');

// Turn off PHP output compression
ini_set('zlib.output_compression', false);

//ob_start();
ob_implicit_flush(TRUE);

$prefix = "NoPrefixFound";

$http_host = $_SERVER['HTTP_HOST'];
    
if (preg_match('/^dev.irvinehebrewday.org/', $http_host)) {
    $gProduction = 0;
    $gSiteDir = "/usr/local/site";
    $prefix = "";

} elseif ( defined("IN_BACKUP") || preg_match( '/^irvinehebrewday.org/', $http_host) ) {
    $gProduction = 1;
    $gSiteDir = "/home1/joylearn/site";
    $prefix = "joylearn_";

} elseif ( preg_match( '/^joy-learner.org/', $http_host) ) {
    $gProduction = 1;
    $gSiteDir = "/home1/joylearn/site";
    $prefix = "joylearn_";
}

$gDbPrefix = $prefix;    

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require( "$gSiteDir/PHPMailer/src/PHPMailer.php");
require( "$gSiteDir/PHPMailer/src/SMTP.php" );
require( "$gSiteDir/PHPMailer/src/Exception.php");
require( "$gSiteDir/fpdf/fpdf.php");

$parts = array();
$parts[] = $gSiteDir . "/ihds/php";
$parts[] = $gSiteDir . "/bin";
$parts[] = $gSiteDir . "/Common-IHDS-Societies";
$parts[] = $gSiteDir . "/PHPMailer";
$parts[] = $gSiteDir . "/fpdf";
$path = join(PATH_SEPARATOR, $parts);

set_include_path(get_include_path() . PATH_SEPARATOR . $path );

include 'includes/globals.php';
include 'includes/library.php';

include 'local-ihds-societies.php';
include 'local_mailer.php';

require_once( 'php/SiteLoader.php' );
SiteLoad( 'php/library');

session_start();

selectDB();

$user = new User2($gDb = $gPDO[$gDbControlId]['inst']);