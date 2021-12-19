#!/usr/bin/env php
<?php
define("IN_BACKUP",true);

require_once( 'includes/config.php' );
dumpCSV("all","email");
echo "Email Sent\n";