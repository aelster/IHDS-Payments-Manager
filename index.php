<?php
require_once( 'includes/config.php' );

Phase1(); // Anything to do before generating output?
Phase2(); // Make any updates that are required
?>
    <div id="container">
        <div id="banner">
            <div id="site"><?php displaySite(); ?></div>
            <div id="bannerButtons"><?php displayBanner(); ?></div>
            <div><span id="IdleTime"></span></div>
        </div>
        <div id="content">
            <div id="sidebar"><?php displaySidebar(); ?></div>
            <div id="palette"><?php phase3(); displayPalette(); ?></div>
        </div>
    </div>
    <?php
    echo "<script type=\"text/javascript\">\n";
    if ($user->is_logged_in()) {
        echo "createIdleTimer($gMaxIdleTime);\n";
        echo "sidebarColor('$gMode');\n";
    }
    if ($gDebug & $gDebugWindow) {
        echo "if (debugWindow) debugWindow.document.close();\n";
    }
    echo "</script>\n";
    ?>
</body>
<script type='text/javascript'>
    scrollableTable();
    setValue('user_id',<?php echo $gUserId?>);
    </script>
</html>