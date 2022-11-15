<?php
require_once( 'includes/config.php' );
include 'includes/globals.php';

checkForDownloads();
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <?php addHtmlHeader(); ?>
    <body>
        <?php $gPhase = 1; phase1(); # phase1 is for pre-output actions that would interfere with PDF production ?>
        <?php $gPhase = 2; phase2(); # phase2 is for making updates to the database ?>
        <div id="container">
            <div id="banner"><?php displayBanner(); ?></div><!-- end #banner -->
            <div id="content">
                <div id="sidebar"><?php displaySidebar(); ?></div><!-- end #sidebar -->
                <div id="palette"><?php phase3(); displayPalette(); ?></div><!-- end #palette -->
            </div><!-- end #content -->
        </div><!-- end #container -->
    <?php
    echo "<script type=\"text/javascript\">\n";
    if ($gUser->is_logged_in()) {
        if( $gEnableIdleTimer ) {
            echo "<script type='text/javascript'>createIdleTimer();</script>";
        }
        echo "sidebarColor('$gMode');\n";
    }
    if ($gDebug & $gDebugWindow) {
        //echo "if (debugWindow) debugWindow.document.close();\n";
    }
    echo "</script>\n";
    ?>
</body>
<script type='text/javascript'>
    scrollableTable();
    setValue('user_id',<?php echo $gUserId?>);
    </script>
</html>