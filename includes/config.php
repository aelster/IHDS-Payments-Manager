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

$parts[] = $gSiteDir . "/ihds/php";
$parts[] = $gSiteDir . "/bin";
$parts[] = $gSiteDir . "/php-common-societies";
$parts[] = $gSiteDir . "/PHPMailer";
$parts[] = $gSiteDir . "/fpdf";
$path = join(PATH_SEPARATOR, $parts);

set_include_path(get_include_path() . PATH_SEPARATOR . $path );

include 'includes/globals.php';
include 'includes/library.php';

include 'local-ihds-societies.php';
include 'local_mailer.php';

$gDreamweaver = 1;
$gDbPrefix = $prefix;    

$gMailSignatureImage = 'assets/SignatureImage.png';
$gMailSignatureImageSize = ['width' => 550, 'height' => 97]; 

$gMailSignature = [];
$gMailSignature[] = "";
$gMailSignature[] = "";
$gMailSignature[] = "<span style='font-family:george,serif; font-size:15px; font-weight:900;'><i>Andy Elster";
$gMailSignature[] = "Co-Founder, CFO, Board of Directors</i></span>";
$gMailSignature[] = "";
$gMailSignature[] = "<img src=\"cid:sigimg\" width='200' height='33'/>";
$gMailSignature[] = '<div><font face="tahoma, sans-serif" color="#6aa84f" size="small">';
$gMailSignature[] = "<a href='https://goo.gl/maps/HZKrQKjxue52'>1500 E 17th Street</a>";
$gMailSignature[] = "<a href='https://goo.gl/maps/HZKrQKjxue52'>Santa Ana, CA 92705</a>";
$gMailSignature[] = "Mobile: <a href='tel:9494786818'>(949) 478-6818</a>";
$gMailSignature[] = "<a href='https://irvinehebrewday.org/'>www.irvinehebrewday.org</a>";
$gMailSignature[] = "</font>";
$gMailSignature[] = "</div>";

require_once( 'SiteLoader.php' );
SiteLoad( 'Common');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require( "$gSiteDir/PHPMailer/src/PHPMailer.php");
require( "$gSiteDir/PHPMailer/src/SMTP.php" );
require( "$gSiteDir/PHPMailer/src/Exception.php");
require( "$gSiteDir/fpdf/fpdf.php");

//include 'includes/pdf.php';

session_start();

selectDB();

$user = new User2($gDb = $gPDO[$gDbControlId]['inst']);