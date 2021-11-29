#!/usr/bin/env php
<?php
define("IN_BACKUP",true);

require_once( 'includes/config.php' );
BackupMySql();
echo "Backup Complete\n";