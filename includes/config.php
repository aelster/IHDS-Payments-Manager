<?php
include 'includes/globals.php';

//set timezone
date_default_timezone_set('America/Los_Angeles');

$opt = 0;
if( $opt ) {
    ini_set('output_buffering', 'off');         // Turn off output bufferingx
    ini_set('zlib.output_compression', false);  // Turn off PHP output compression
    ob_implicit_flush(TRUE);                    //ob_start();
}

$http_host = $_SERVER['HTTP_HOST'];

if (preg_match('/^dev.ihds.org/', $http_host) || $http_host == "10.0.0.7" ) {
    $gProduction = 0;
    $gSiteDir = "/usr/local/site";
    $gSiteName = "MacBook Air";
    
} elseif (  defined("IN_BACKUP") || preg_match( '/irvinehebrewday.org/', $_SERVER['HTTP_HOST']) ) {
    $gProduction = 1;
    $gSiteDir = '/home1/joylearn/site';
    $gSiteName = 'irvinehebrewday.org';
    $gEnableIdleTimer = true;
    $gMaxIdleTime = 60*10;
}

$gProject = "IHDS Payment Manager";
$gSiteSubPath = "ihds";
$gCommonRoot = $gSiteDir . "/Common-IHDS-Payments-Manager";
$gWebRoot = dirname($_SERVER["SCRIPT_FILENAME"]);
$gError = [];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require( "$gSiteDir/PHPMailer/src/PHPMailer.php");
require( "$gSiteDir/PHPMailer/src/SMTP.php" );
require( "$gSiteDir/PHPMailer/src/Exception.php");
require( "$gSiteDir/fpdf/fpdf.php");

$path = join(PATH_SEPARATOR, array(
    $gSiteDir . "/Common-IHDS-Payments-Manager",
    $gSiteDir . "/$gSiteSubPath/payments",
    $gSiteDir . "/PHPMailer",
    $gSiteDir . "/fpdf"
    ));

set_include_path(get_include_path() . PATH_SEPARATOR . $path );

include 'includes/globals.php';
include 'includes/library.php';

include 'local-mailer.php';
include 'local-portico.php';
include 'local-payments.php';

require_once( 'php/SiteLoader.php' );
SiteLoad( 'php/library');


selectDB();

$gUser = new User2($gDb = $gPDO[$gDbControlId]['inst']);