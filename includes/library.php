<?php

function addDonor() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    
    $id = $_POST['id'];

    $query = sprintf("select column_name,column_type from information_schema.columns
        where table_schema = '%s' and table_name = 'donations'", $gDbNames[$gDbControlId]);

    $stmt = DoQuery($query);
    $args = $vals = [];
    $i = 0;
    while (list( $fld, $xtype) = $stmt->fetch(PDO::FETCH_NUM)) {
        if ($fld == "id")
            continue;
        if ($fld == "section") {
            $i++;
            $args[$i] = "`$fld` = :v$i";
            $vals[":v$i"] = $_POST[$fld . "_$id"];
            continue;
        }
        if ($fld == "success" || $fld == "visible") {
            $i++;
            $args[$i] = "`$fld` = :v$i";
            $vals[":v$i"] = 1;
            continue;
        }

        $j = strpos($xtype, '(');
        if ($j == 0) {
            $type = $xtype;
        } else {
            $type = substr($xtype, 0, $j);
        }

        switch ($type) {
            case "bigint":
            case "float":
            case "int":
            case "tinyint":
                $i++;
                $args[$i] = "`$fld` = :v$i";
                $vals[":v$i"] = empty($_POST[$fld . "_$id"]) ? 0 : $_POST[$fld . "_$id"];
                break;

            case "varchar":
                $i++;
                $args[$i] = "`$fld` = :v$i";
                $key = $fld . "_$id";
                if( array_key_exists($key, $_POST ) ) {
                    $val = $_POST[$key];
                } else {
                    $val = '';
                }
                $vals[":v$i"] = $val;
                break;
        }
    }
    $query = "insert into orders set " . implode(',', $args);
    DoQuery($query, $vals);

    $obj = [];
    $obj['type'] = 'create';
    $obj['user_id'] = $gUserId;
    $obj['item'] = "create new $gArea user record, id = " . $gPDO_lastInsertID;
    EventLogRecord($obj);

    $gAction = "display";
}

function addForm() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    echo "<form name=fMain id=fMain method=post action=\"$gSourceCode\">$gLF";

    $hidden = array();
    $hidden[] = 'action';   # what needs to be done or what was pressed
    $hidden[] = 'area';
    $hidden[] = 'fields';
    $hidden[] = 'from';
    $hidden[] = 'func';
    $hidden[] = 'id';
    $hidden[] = 'key';
    $hidden[] = 'mode';
    $hidden[] = 'user_id';
    $hidden[] = 'where';

    foreach ($hidden as $var) {
        $tag = MakeTag($var);
        echo "<input type=hidden $tag />$gLF";
    }
    define('FORM_OPEN', 1);
    if ($gTrace) {
        array_pop($gFunction);
    }
}

function addHtmlHeader() {
    include 'includes/globals.php';
    
    echo "<head>";

    echo <<<EOT
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta charset='utf-8'>
    <meta http-equiv='Cache-control' content='no-cache'>
    <title>$gSiteName</title>
 <meta property="og:local" content="en_US">
 <meta property="og:type" content="website">
 <meta property="og:title" content="IHDS Payments Manager">
 <meta property="og:url" content="https://irvinehebrewday.org/pm">
 <meta property="og:description" content="Manage IHDS payments, reports and configuration">
EOT;
    $styles = array();
    $styles[] = "css/Common.css";
    $styles[] = "css/main.css";

    $scripts = array();
    $scripts[] = "https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js";
    $scripts[] = "scripts/Common.js";
    $scripts[] = "scripts/sorttable.js";
    $scripts[] = "scripts/my_ajax.js";
    $scripts[] = "scripts/payments.js";

    echo <<<EOT
    <link rel='shortcut icon' type='image/x-icon' href='assets/favicon.ico' />
EOT;

    $force = 0;

    if ($force) {
        $tag = rand(0, 1000);
        $str = "?dev=$tag";
    } else {
        $str = "";
    }
    foreach ($styles as $style) {
        printf("<link href=\"%s$str\" rel=\"stylesheet\" type=\"text/css\" />\n", $style);
    }

    foreach ($scripts as $script) {
        printf("<script type=\"text/javascript\" src=\"%s$str\"></script>\n", $script);
    }
    echo '<script type="text/javascript">debug_disabled = 0;</script>';
    if ($gEnableJavascriptDebugger) {
        echo "<script type='text/javascript'>\n";
        echo "_init()\n";
        echo "var d = new Date();\n";
        echo "debug('--- Non-Production. Start of run @ ' + d + ' ---')\n";
        echo "</script>\n";
    }
    echo "</head>";
}

function addToSidebar($buttons) {
    include 'includes/globals.php';

    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    echo <<<EOT
    <script type="text/javascript">
        var btn;
        var sidebar = document.getElementById("sidebar");
        sidebar.innerHTML += '<hr>';
EOT;
    $i = 0;

    foreach ($buttons as $obj) {
        $i++;
        $class = "sidebar-btn";
        if (is_string($obj)) {
            continue;
        }
        $label = array_key_exists('label', $obj) ? $obj['label'] : ucfirst($obj['area']);

        $jsx = array();
        $jsx[] = "setValue('mode','$gMode')";
        $jsx[] = "setValue('area','" . $obj['area'] . "')";
        if (!empty($obj["js"])) {
            $jsx[] = $obj["js"];
        }
        $action = array_key_exists('action', $obj) ? $obj['action'] : 'display';
        if ($action == 'init') {
            $jsx[] = "myConfirm('$label')";
            $label = "Init";
        } else {
            $jsx[] = "addAction('$action')";
        }
        $js = join(';', $jsx);

        $bid = ($action == 'update') ? 'update' : "bid$i";

        echo <<<EOT
            btn = document.createElement("input");
            btn.setAttribute("type", "button");
            btn.id = "$bid";
            btn.setAttribute("class", "$class");
            btn.setAttribute("value", "$label");
EOT;
        if (array_key_exists('disabled', $obj)) {
            echo 'btn.setAttribute("disabled", "true");';
        }
        echo <<<EOT
            btn.setAttribute("onclick", "$js");
            sidebar.appendChild(btn);
EOT;
    }
    echo <<<EOT
        btn = document.createElement("input");
        btn.setAttribute("type", "button");
        btn.setAttribute("class", "sidebar-btn");
        btn.setAttribute("value", "Font +");
        btn.setAttribute("onclick", "paletteFontPlus()");
        sidebar.appendChild(btn);
        btn = document.createElement("input");
        btn.setAttribute("type", "button");
        btn.setAttribute("class", "sidebar-btn");
        btn.setAttribute("value", "Font -");
        btn.setAttribute("onclick", "paletteFontMinus()");
        sidebar.appendChild(btn);
    </script>
EOT;
    if ($gTrace) {
        array_pop($gFunction);
    }
}

function checkForDownloads() {
    include 'includes/globals.php';
    
    if( $gAction == "download" ) {
        dumpCSV( $_POST['section_0'] );
    }
}

function deleteDonor() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $obj = [];
    $obj['type'] = 'update';
    $obj['user_id'] = $_POST['user_id'];

    $fields = array_unique( explode(',', $_POST['fields'] ) );
    foreach( $fields as $id ) {
        if(array_key_exists("group_$id", $_POST ) && $_POST["group_$id"] ) {
            $query = "delete from orders where id = $id";
            DoQuery($query);
            $obj['item'] = $query;
            EventLogRecord($obj);
        }
    }
    
    if( $gArea == "kravmaga" ) {
        $query = "delete from kravmaga where donationId = $id";
        DoQuery($query);
        $obj['item'] = $query;
        EventLogRecord($obj);
    }
    $gAction = "display";
}

function displayBanner() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    if ($gAction == 'logout')
        return;

    echo "<div id=site>";
    displaySite();
    echo "</div><!-- end #site -->";
    
    if ($gUser->is_logged_in()) {
        echo '<div id="bannerButtons">';
        $jsx = [];
        $jsx[] = "addAction('logout')";
        $js = implode(';', $jsx);
        echo "<input type=button"
        . " id=button-logout"
        . " onclick=\"$js\""
        . " value=Logout />";

        if ($_SESSION['level'] >= $gAccessNameToLevel['control']) {
            $jsx = [];
            $jsx[] = "setValue('mode','control')";
            $jsx[] = "sidebarColor('control')";
            $jsx[] = "addAction('display')";
            $js = implode(';', $jsx);
            echo "<input type=button"
            . " id=button-control"
            . " class=control"
            . " onclick=\"$js\""
            . " value=Control />";
            if (empty($gBannerMode))
                $gBannerMode = "control";
        }

        if ($_SESSION['level'] >= $gAccessNameToLevel['admin']) {
            $jsx = [];
            $jsx[] = "setValue('mode','admin')";
            $jsx[] = "sidebarColor('admin')";
            $jsx[] = "addAction('display')";
            $js = implode(';', $jsx);
            echo "<input type=button"
            . " id=button-admin"
            . " class=admin"
            . " onclick=\"$js\""
            . " value=Admin />";
            if (empty($gBannerMode))
                $gBannerMode = "admin";
        }

        if ($_SESSION['level'] >= $gAccessNameToLevel['office']) {
            $jsx = [];
            $jsx[] = "setValue('mode','office')";
            $jsx[] = "sidebarColor('office')";
            $jsx[] = "addAction('display')";
            $js = implode(';', $jsx);
            echo "<input type=button"
            . " id=button-office"
            . " class=office"
            . " onclick=\"$js\""
            . " value=Office />";
            if (empty($gBannerMode))
                $gBannerMode = "office";
        }
        echo '</div><!-- end #bannerButtons -->';
        echo '<div><span id="IdleTime"></span></div><!-- end IdleTime -->';
    }
    
    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displayMail() {
    include 'includes/globals.php';
    
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    echo "<div class=center>";
    echo "<h2>Mail Controls</h2>";
//    echo "<span style='background-color: yellow; width: 200px; text-align: left; display: inline-block; font-size: 12pt;'>";
    echo "</span>";
    echo "</div>";
    echo "<input type=button value=Back onclick=\"addAction('main');\">";
        $jsx = [];
    $jsx[] = "setValue('from','" .  __FUNCTION__ . "')";
    $jsx[] = "setValue('func','new')";
    $jsx[] = "addAction('mail')";
    $js = implode(';', $jsx); 
    echo "&nbsp;";
    echo "<td class=c><input type=submit onclick=\"$js\" value=New /></td>";

    echo "<br><br>";
    echo "<table class=usermanager>";

    echo "<thead>";
    echo "<tr>";
    echo "<th class=col1>Label</th>";
    echo "<th class=col2>Value</th>";
    echo "<th class=col3>Enabled</th>";
    echo "<th class=col5>Action</th>";
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";
    $stmt = DoQuery("select * from mail where lower(label) like '%email:%' order by label asc");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        $label = $row['label'];
        $value = $row['value'];

        if ($label == 'Email: Server') {
            echo "<tr>";
            echo "<td class=col1>$label</td>";
            echo "<td class=col2>";
            $ajax_id = "id=\"mail__value__{$id}\"";
            echo "<select class=\"'col2' ajax\" $ajax_id>";
            for( $mid = 0; $mid < count($gMailDB); $mid++  )  {
                $selected = ( $mid == $value ) ? "selected" : "";
                echo "<option value=$mid $selected>{$gMailDB[$mid]['Label']}</option>";
            }
            echo "</select>";
            echo "</td>";
            echo "<td>&nbsp;</td>";
            echo "<td>&nbsp;</td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td class=col1>$label</td>";
            $ajax_id = "id=\"mail__value__{$id}\"";
            echo "<td class=col2><input class=\"'col2' ajax\" size=60 $ajax_id value='" . $row['value'] . "' /></td>";

            $tag = MakeTag("enabled_$id");
            $acts = array();
            $acts[] = "addField('$label|enabled|$id')";
            $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
            $acts[] = "setValue('mode','control')";
            $acts[] = "setValue('area','mail')";
            $acts[] = "setValue('func','update')";
            $acts[] = "setValue('id', '$id')";
            $acts[] = "setValue('key', '$label')";
            $acts[] = "addAction('update')";
            if( empty($row['enabled']) )  {
                $checked = "";
                $val = 1;
            } else {
                $checked = "checked";
                $val = 0;
            }
            $ajax_id = "id=\"mail__enabled__{$id}\"";
            $js = "";
            echo "<td class=box><input class=ajax type=\"checkbox\" $ajax_id $checked $js value=\"$val\"></td>\n";

            $acts = array();
            $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
            $acts[] = "setValue('area','mail')";
            $acts[] = "setValue('func','del')";
            $acts[] = "setValue('id', '$id')";
            $acts[] = "addAction('update')";
            printf("<td class='col5 c'><input type=button onClick=\"%s\" value='Del' /></td>", join(';', $acts));

            echo "</tr>";
        }
    }

    $id = 0;

    echo "<tr>";

    $tag = MakeTag('label_' . $id);
    $js = "onChange=\"toggleBgRed('add');\" onClick=\"this.select();\"";
    echo "<td class=col1><input $tag type='text' size=15 $js value='-- enter label --'></td>";

    $tag = MakeTag('value_' . $id);
    $js = "onChange=\"addField('new|value|$id');toggleBgRed('add');\"";
    echo "<td class=col2><input $tag type='text' size=60 $js></td>";

    $tag = MakeTag('enabled_' . $id);
    $js = "onChange=\"addField('new|enabled|$id');toggleBgRed('add');\"";
    echo "<td class='col3 c'><input $tag type='checkbox' value=1 $js></td>";

    $tag = MakeTag('add');
    $acts = array();
    $acts[] = "addField('new|label|$id')";
    $acts[] = "addField('new|value|$id')";
    $acts[] = "addField('new|enabled|$id')";
    $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
    $acts[] = "setValue('mode','control')";
    $acts[] = "setValue('area','mail')";
    $acts[] = "setValue('func','add')";
    $acts[] = "addAction('update')";
    printf("<td class='col5 c'><input $tag type=button onClick=\"%s\" value=Add></td>", join(';', $acts));

    echo "</tr>";
    echo "</tbody>";
    echo "</table>";
    
    echo "<br><br>";

    echo "<h1>Email: Admin</h1>";
    echo "<ul class=mail-desc>";
    echo "<li>All emails are sent from this account</li>";
    echo "<li class=warn>If enabled, emails are sent to members</b></li>";
    echo "<li>If not enabled, emails are sent to Testing accounts</li>";
    echo "</ul>";

    echo "<br><br>";

    echo "<h1>Email: Default</h1>";
    echo  "<ul class=mail-desc>";
    echo "<li>This is the default mail account if nothing else is set up</li>";
    echo "</ul>";
    
    echo "<br><br>";

    echo "<h1>Email: Testing</h1>";
    echo  "<ul class=mail-desc>";
    echo "<li>If Admin is disabled, mail is sent to these accounts</li>";
    echo "<li>Multiple accounts can be created</li>";
    echo "</ul>";
    
    if ($gTrace) {
        array_pop($gFunction
        );
    }
}

function displayMisc() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    echo "<div class=center>";
    echo "<h2>Miscellaneous Controls</h2>";
    echo "</div>";


    echo "<br>";
//    echo "<table class=scrollable>";
    echo "<table>";

    echo "<thead>";
    echo "<tr>";
    echo "<th class=col1>Label</th>";
    echo "<th class=col2>Value</th>";
    echo "<th class=col3>Enabled</th>";
    echo "<th class=col4>Privilege<br>Level</th>";
    echo "<th class=col5>Action</th>";
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";
    $stmt = DoQuery("select * from misc where lower(label) not like '%email%' order by label asc");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        $label = $row['label'];

        echo "<tr>";
        echo "<td class=col1>$label</td>";

        $ajax_id = "id=\"misc__value__{$id}\"";
        $tag = MakeTag("value_$id");
        echo "<td class=col2><input class='col2 ajax' size=60 $ajax_id value='" . $row['value'] . "'></td>";

        if( empty($row['enabled']) )  {
            $checked = "";
            $val = 1;
        } else {
            $checked = "checked";
            $val = 0;
        }
        $ajax_id = "id=\"misc__enabled__{$id}\"";
        printf("<td class='col3 c'><input class=ajax type='checkbox' $ajax_id  value=$val  $checked></td>");

        $ajax_id = "id=\"misc__privilegeLevel__{$id}\"";
        echo "<td class='col4 c'>";
        echo "<select class='ajax' $ajax_id>";
        foreach( $gAccessLevels as $name ) {
            $selected = ( $row['privilegeLevel'] == $gAccessNameToId[$name]) ? "selected" : "";
            echo "<option value=" . $gAccessNameToId[$name] . " $selected>$name</option>";
            
        }
        echo "</select>";
        echo "</td>";

        $acts = array();
        $acts[] = "setValue('from','MiscDisplay')";
        $acts[] = "setValue('area','misc')";
        $acts[] = "setValue('func','del')";
        $acts[] = "setValue('id', '$id')";
        $acts[] = "addAction('update')";
        printf("<td class='col5 c'><input type=button onClick=\"%s\" value='Del'></td>", join(';', $acts));

        echo "</tr>";
    }

    $id = 0;

    echo "<tr>";

    $tag = MakeTag('label_' . $id);
    $js = "onChange=\"toggleBgRed('add');\" onClick=\"this.select();\"";
    echo "<td class=col1><input $tag type='text' size=15 $js value='-- enter label --'></td>";

    $tag = MakeTag('value_' . $id);
    $js = "onChange=\"toggleBgRed('add');\"";
    echo "<td class=col2><input $tag type='text' size=60 $js></td>";

    $tag = MakeTag('enabled_' . $id);
    $js = "onChange=\"toggleBgRed('add');\"";
    echo "<td class='col3 c'><input $tag type='checkbox' value=1 $js></td>";

    $tag = MakeTag('privilegeLevel_' . $id);
    $js = "onChange=\"toggleBgRed('add');\"";
    echo "<td class='col4 c'><input class='col4' size=3 $tag $js value=0></td>";

    $tag = MakeTag('add');
    $acts = array();
    $acts[] = "addField('label|$id')";
    $acts[] = "addField('value|$id')";
    $acts[] = "addField('enabled|$id')";
    $acts[] = "addField('privilegeLevel|$id')";
    $acts[] = "setValue('from','MiscDisplay')";
    $acts[] = "setValue('area','misc')";
    $acts[] = "setValue('func','add')";
    $acts[] = "addAction('update')";
    printf("<td class='col5 c'><input $tag type=button onClick=\"%s\" value=Add></td>", join(';', $acts));

    echo "</tr>";
    echo "</tbody>";
    echo "</table>";

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displayPalette() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $dpv_pre = "Begin";
    $dpv_phase = 4;
    $dpv_tag = "PaletteDisplay";
    if ($gDebug) {
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if( ! empty($gMessage) ) {
        echo "<div class=warn>";
        for( $i = 0; $i < count($gMessage); $i++ ) {
            echo "$gMessage[$i]<br>";
        }
        echo "</div>";
    }
    
    switch ($gAction) {
        case "display":
            switch ($gArea) {
                case "all":
                case in_array( $gArea, $gSectionsFound):
                case "section":
                    displayDonors($gArea);
                    break;

                case "categories":
                    displayCategories();
                    break;
                
                case "debug":
                    MyDebug("display");
                    break;

                case "discounts":
                    displayDiscounts();
                    break;
                
                case "eventLog":
                    EventLogDisplay();
                    break;

                case "mail":
                    UserMail('display');
                    break;

                case "misc":
                    displayMisc();
                    break;

                case "payments":
                    paymentTypeDisplay();
                    break;

                case "privileges":
                    UserManager("privileges");
                    break;

                case "sections":
                    sectionDisplay();
                    break;

                case "source":
                    SourceDisplay();
                    break;

                case "special":
                    displaySpecial();
                    break;
                
                case "users":
                    UserManager("control");
                    break;

            }
            break;

//        case "login":
//            $gFunc = "login";
//            UserManager("login");
//            break;

        case "logout":
            UserManager("logout");
            break;

        case "login":
            if ($gFunc == "getemail") {
                UserManager('forgot');
            } elseif ($gFunc == "welcome") {
                UserManager('welcome');
            } elseif ($gFunc == "reset") {
                UserManager('reset');
            } elseif ($gFunc == "change") {
                UserManager('welcome');
            } elseif( $gFunc == 'mail' ) {
                UserManager('mail');
            }
            break;

        case "password":
            if( $gFunc == "reset" ) {
                UserManager('reset');
            } elseif( $gFunc == "newpassword" ) {
                UserManager('newpassword');
            }
            break;

        case "welcome":
            UserManager("welcome");
            break;
    }

    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }
    if ($gTrace) {
        array_pop($gFunction);
    }
}

function paymentTypeDisplay() {
    include 'includes/globals.php';
    echo <<<EOT
<h2>Payment Types</h2>
<table>
  <thead>
    <tr>
      <th>Label</th>
      <th>Enabled</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
EOT;
    $stmt = DoQuery("select * from payment_types order by label ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        echo "<tr>";
        $ajax_id = "id=\"payment_types__label__$id\"";
        echo "<td class=center><input type=text size=8 class=\"ajax\" $ajax_id value=\"{$row['label']}\"></td>\n";
        
        if ($row['enable']) {
            $checked = "checked";
            $val = 0;
        } else {
            $checked = "";
            $val = 1;
        }
        $ajax_id = "id=\"payment_types__enable__$id\"";
        echo "<td class=center><input type=checkbox class=ajax $ajax_id value=$val $checked></td>\n";

        $acts = array();
        $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
        $acts[] = "setValue('area','payments')";
        $acts[] = "setValue('func','del')";
        $acts[] = "setValue('id', '$id')";
        $acts[] = "addAction('update')";
        printf("<td class=center><input type=button onClick=\"%s\" value='Del'></td>", join(';', $acts));
    }

    echo "</tbody>";
    
}

function sectionDisplay() {
    include 'includes/globals.php';

    echo <<<EOT
<h2>Sections</h2>
<table>
  <thead>
    <tr>
      <th>Label</th>
      <th>Enabled</th>
      <th>Emails (separate with a ;)</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
EOT;

    $stmt = DoQuery("select * from sections order by label ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        echo "<tr>";
        $ajax_id = "id=\"sections__label__$id\"";
        echo "<td><input type=text size=10 class=ajax $ajax_id value=\"{$row['label']}\"></td>\n";

        if ($row['enabled']) {
            $checked = "checked";
            $val = 0;
        } else {
            $checked = "";
            $val = 1;
        }
        $ajax_id = "id=\"sections__enabled__$id\"";
        echo "<td class=center><input type=checkbox class=ajax $ajax_id value=$val $checked></td>\n";
        
        $ajax_id = "id=\"sections__email__$id\"";
        echo "<td class=center><textarea rows=4 cols=50 class=ajax $ajax_id>{$row['email']}</textarea></td>\n";
        
        $acts = array();
        $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
        $acts[] = "setValue('area','sections')";
        $acts[] = "setValue('func','del')";
        $acts[] = "setValue('id', '$id')";
        $acts[] = "addAction('update')";
        printf("<td class=center><input type=button onClick=\"%s\" value='Del'></td>", join(';', $acts));
//
//        $ajax_id = "id=\"sections__description__$id\"";
//        echo "<td>Description</td>";
//        echo "<td><textarea rows=2 cols=50 class=ajax $ajax_id value=\"{$row['description']}\"></textarea></td>\n";
//        echo "</tr>";
//        
//        echo "<tr>";
//        echo "<td>Email Subject</td>";
//        $ajax_id = "id=\"sections__emailSubject__$id\"";
//        echo "<td><input type=text class=ajax $ajax_id value=\"{$row['emailSubject']}\"></td>\n";
//        echo "</tr>";
//                
//        echo "<tr>";
//        echo "<td>Email Distribution</td>";
//        $ajax_id = "id=\"sections__emailDistribution__$id\"";
//        echo "<td><input type=text class=ajax $ajax_id value=\"{$row['emailDistribution']}\"></td>\n";
//        echo "</tr>";
//        
//        echo "<tr>";
//        echo "<td>Email Body</td>";
//        $ajax_id = "id=\"sections__emailBody__$id\"";
//        echo "<td><textarea rows=2 cols=50 class=ajax $ajax_id value=\"{$row['emailBody']}\"></textarea></td>\n";
        echo "</tr>";
    }
    $id = 0;
    echo "<tr>";
    $tag = MakeTag('label' . $id);
    $js = "onChange=\"addField('new|label|$id');\"";
    echo "<td class=center><input type=text $tag size=10 $js></td>\n";

    $checked = "";
    $val = 1;
    $tag = MakeTag('enabled_' . $id);
    $js = "onChange=\"addField('new|enabled|$id');\"";
    echo "<td class=center><input type=checkbox $tag $js value=$val $checked></td>\n";

    $acts = array();
    $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
    $acts[] = "setValue('area','sections')";
    $acts[] = "setValue('func','add')";
    $acts[] = "setValue('id', '$id')";
    $acts[] = "addAction('update')";
    printf("<td class=center><input type=button onClick=\"%s\" value='Add'></td>", join(';', $acts));
    echo "</tr>\n";
    echo "</tbody>";
    echo "</table>";
    
}
function displaySidebar() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__ . " ($gBannerMode)";
        Logger();
    }
    if ($gAction == 'logout')
        return;

    if ($gDebug) {
        DumpPostVars(sprintf("displaySidebar: gAction: [%s], gMode: [%s], gArea: [%s],"
                        . " gFunc: [%s]", $gAction, $gMode, $gArea, $gFunc));
    }
    # bbl => button below the line
    #   label defaults to ucfirst(area)
    #   action defaults to sidebar

    if ($gUser->is_logged_in()) {
        $buttons = [];

        $skipActions = ['reset', 'start'];

        if (!array_key_exists($gAction, $skipActions)) {
            if ($gAction != "start") {
                if (!empty($gArea)) {
                    if (empty($gMode)) {
                        $gMode = array_key_exists($gArea, $gAreaToMode) ? $gAreaToMode[$gArea] : "";
                    }
                }
                if (empty($gMode))
                    $gMode = 'office';

                if (array_key_exists($gMode, $gModeToButtons)) {
                    $buttons = $gModeToButtons[$gMode];
                }
            }

            $bbl = array();

            if ($gAction != 'forgot' && $gAction != "edit") {
                if (!empty($buttons)) {
                    foreach ($buttons as $obj) {
                        $addBBL = 0;
                        if(! empty($gArea) && preg_match( "/$gArea/i", $obj['area'] ) ) {
                            $addBBL = 1;
                            $class = "sidebar-btn selected";
                        } else {
                            $class = "sidebar-btn";
                        }
                        if ($addBBL && array_key_exists('bbl', $obj)) {
                            $bbl = $obj['bbl'];
                        }

                        $label = array_key_exists('label', $obj) ? $obj['label'] : ucfirst($obj['area']);
                        $left = sprintf("<input type=button id=\"%s\" class=\"%s\" value=\"%s\"", $obj['area'], $class, $label);
                        $jsx = array();
                        $jsx[] = "setValue('mode','$gMode')";
                        $jsx[] = "setValue('area','" . $obj['area'] . "')";
                        $jsx[] = "setValue('func','show')";
                        $jsx[] = "setValue('where','sidebar')";

                        if (!empty($obj["js"])) {
                            $jsx[] = $obj["js"];
                        }
                        $action = array_key_exists('action', $obj) ? $obj['action'] : 'display';
                        $jsx[] = "addAction('$action')";
                        $js = join(';', $jsx);
                        echo $left . " onclick=\"$js\">";
                    }
                }
            }
            addToSidebar($bbl);
        }
    }
//    ob_flush();

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displayDiscounts() {
    include 'includes/globals.php';

    echo <<<EOT
<h2>Discounts</h2>
<table>
  <thead>
    <tr>
      <th>Code</th>
      <th>Amount</th>
      <th>Percent?</th>
      <th>Dollars?</th>
      <th>Enabled</th>
      <th>Description</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
EOT;

    $stmt = DoQuery("select * from discounts order by code ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        echo "<tr>";
        $ajax_id = "id=\"discounts__code__$id\"";
        echo "<td><input type=text size=10 class=ajax $ajax_id value=\"{$row['code']}\"></td>\n";

        $ajax_id = "id=\"discounts__amount__$id\"";
        echo "<td><input type=text size=5 class=ajax $ajax_id value=\"{$row['amount']}\"></td>\n";

        if ($row['percent']) {
            $checked = "checked";
            $val = 0;
        } else {
            $checked = "";
            $val = 1;
        }
        $ajax_id = "id=\"discounts__percent__$id\"";
        echo "<td class=center><input type=checkbox class=ajax $ajax_id value=$val $checked></td>\n";

        if ($row['dollars']) {
            $checked = "checked";
            $val = 0;
        } else {
            $checked = "";
            $val = 1;
        }
        $ajax_id = "id=\"discounts__dollars__$id\"";
        echo "<td class=center><input type=checkbox class=ajax $ajax_id value=$val $checked></td>\n";

        if ($row['enabled']) {
            $checked = "checked";
            $val = 0;
        } else {
            $checked = "";
            $val = 1;
        }
        $ajax_id = "id=\"discounts__enabled__$id\"";
        echo "<td class=center><input type=checkbox class=ajax $ajax_id value=$val $checked></td>\n";

        $ajax_id = "id=\"discounts__description__$id\"";
        echo "<td><input type=text class=ajax $ajax_id value=\"{$row['description']}\"</td>\n";
        
        $acts = array();
        $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
        $acts[] = "setValue('area','discounts')";
        $acts[] = "setValue('func','del')";
        $acts[] = "setValue('id', '$id')";
        $acts[] = "addAction('update')";
        printf("<td class=center><input type=button onClick=\"%s\" value='Del'></td>", join(';', $acts));
        echo "</tr>\n";
    }
    $id = 0;
    echo "<tr>";
    $tag = MakeTag('code_' . $id);
    $js = "onChange=\"addField('new|code|$id');\"";
    echo "<td class=center><input type=text $tag size=10 $js></td>\n";

    $tag = MakeTag('amount_' . $id);
    $js = "onChange=\"addField('new|amount|$id');\"";
    echo "<td><input type=text $tag size=5 $js></td>\n";

    $checked = "";
    $val = 1;
    $tag = MakeTag('percent_' . $id);
    $js = "onChange=\"addField('new|percent|$id');\"";
    echo "<td class=center><input class=c type=checkbox $tag $js value=$val $checked></td>\n";

    $checked = "";
    $val = 1;
    $tag = MakeTag('dollars_' . $id);
    $js = "onChange=\"addField('new|dollars|$id');\"";
    echo "<td class=center><input class=c type=checkbox $tag $js value=$val $checked></td>\n";

    $checked = "";
    $val = 1;
    $tag = MakeTag('enabled_' . $id);
    $js = "onChange=\"addField('new|enabled|$id');\"";
    echo "<td class=center><input type=checkbox $tag $js value=$val $checked></td>\n";

    $tag = MakeTag('description_' . $id);
    $js = "onChange=\"addField('new|description|$id');\"";
    echo "<td><input type=text $tag $js size=30></td>\n";
    
    $acts = array();
    $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
    $acts[] = "setValue('area','discounts')";
    $acts[] = "setValue('func','add')";
    $acts[] = "setValue('id', '$id')";
    $acts[] = "addAction('update')";
    printf("<td class=center><input type=button onClick=\"%s\" value='Add    '></td>", join(';', $acts));
    echo "</tr>\n";
    echo "</tbody>";
    echo "</table>";
    
    echo "<br>";
    echo "<h2>Where do they apply?</h2>";
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Code</th>";
    $section = [];
    $stmt = DoQuery( "select * from sections order by label asc" );
    while( list( $id, $label, $discount ) = $stmt->fetch(PDO::FETCH_NUM) ) {
        $tmp = empty($discount) ? [] : explode(',',$discount);
        $section[$id] = [ 'id' => $id, 'label' => "$label", 'discounts' => $tmp ];
        echo "<th>$label</th>";
    }
    echo "</tr>";
    echo "</thead>";
    
    $order = array_keys( $section );
    
    echo "<tbody>";
    $stmt = DoQuery( "select id, code from discounts order by code asc" );
    while( list( $codeId, $code ) = $stmt->fetch(PDO::FETCH_NUM) ) {
        echo "<tr>";
        echo "<td class=center>$code</td>";
        foreach( $order as $sectionId ) {
            if( in_array($codeId, $section[$sectionId]['discounts']) ) {
                $checked = "checked";
                $val = 0;
            } else {
                $checked = "";
                $val = 1;
            }
            $ajax_id = "id=\"sections__discounts__{$sectionId}_{$codeId}\"";
            echo "<td class=center><input type=checkbox class=ajax $ajax_id value=$val $checked></td>\n";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";                
}
function displayCategories() {
    include 'includes/globals.php';

    echo <<<EOT
<h2>Categories</h2>
<table>
  <thead>
    <tr>
      <th>Id</th>
      <th>Rank</th>
      <th>Description</th>
      <th>Amount</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
EOT;

    $rank = 0;
    
    $stmt = DoQuery("select * from categories order by myRank ASC, description ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rank += 10;
        $id = $row['id'];
        DoQuery( "update categories set myRank = $rank where id = $id");
        echo "<tr>";

        echo "<td class=center>$id</td>";
        
        $ajax_id = "id=\"categories__myRank__$id\"";
        echo "<td><input type=text size=2 class=\"ajax center\" $ajax_id value=\"{$row['myRank']}\"</td>\n";

        $ajax_id = "id=\"categories__description__$id\"";
        echo "<td><input type=text class=ajax $ajax_id value=\"{$row['description']}\"</td>\n";

        $ajax_id = "id=\"categories__amount__$id\"";
        $str = "$ " . number_format($row['amount'],0);
        echo "<td><input type=text size=5 class=ajax $ajax_id value=\"$str\"></td>\n";

        $acts = array();
        $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
        $acts[] = "setValue('area','categories')";
        $acts[] = "setValue('func','del')";
        $acts[] = "setValue('id', '$id')";
        $acts[] = "addAction('update')";
        printf("<td class=center><input type=button onClick=\"%s\" value='Del'></td>", join(';', $acts));
        echo "</tr>\n";
    }
    $id = 0;
    echo "<tr>";
    echo "<td>&nbsp;</td>";
    
    $tag = MakeTag('myRank_' . $id);
    $js = "onChange=\"addField('new|myRank|$id');\"";
    echo "<td class=center><input type=text $tag size=3 $js></td>\n";

    $tag = MakeTag('description_' . $id);
    $js = "onChange=\"addField('new|description|$id');\"";
    echo "<td class=center><input type=text $tag size=30 $js></td>\n";

    $tag = MakeTag('amount_' . $id);
    $js = "onChange=\"addField('new|amount|$id');\"";
    echo "<td><input type=text $tag size=5 $js></td>\n";
    
    $acts = array();
    $acts[] = sprintf("setValue('from','%s')", __FUNCTION__);
    $acts[] = "setValue('area','categories')";
    $acts[] = "setValue('func','add')";
    $acts[] = "setValue('id', '$id')";
    $acts[] = "addAction('update')";
    printf("<td class=center><input type=button onClick=\"%s\" value='Add'></td>", join(';', $acts));
    echo "</tr>\n";
    echo "</tbody>";
    echo "</table>";              
}

function displayDonors() {
    include 'includes/globals.php';

    $section = $_POST['area'];

    $control = ($gUserAccess == 'control');

    $onetime = $monthly = 0.0;
    
    error_log( "gArea : $gArea" );
        
    $quals = [];
    if( in_array($gArea, $gSectionsFound ) ) {
            $quals[] = "section = \"$section\"";
            $quals[] = "success = 1";
            $quals[] = "visible = 1";
            
            $query = "select sum(paymentAmount) from orders where paymentFrequency like '%month%' and " . implode(" and ", $quals);
            $stat = DoQuery($query);        
            list( $monthly ) = $stat->fetch(PDO::FETCH_NUM);

            $query = "select sum(paymentAmount) from orders where paymentFrequency not like '%month%' and " . implode(" and ", $quals);
            $stat = DoQuery($query);        
            list( $oneTime ) = $stat->fetch(PDO::FETCH_NUM);
    } else {
            $quals[] = "success = 1";
            $quals[] = "visible = 1";

            $query = "select sum(paymentAmount) from orders where paymentFrequency like '%month%' and " . implode(" and ", $quals);
            $stat = DoQuery($query);        
            list( $monthly ) = $stat->fetch(PDO::FETCH_NUM);

            $query = "select sum(paymentAmount) from orders where paymentFrequency not like '%month%' and " . implode(" and ", $quals);
            $stat = DoQuery($query);        
            list( $oneTime ) = $stat->fetch(PDO::FETCH_NUM);
            
    }
    
    if( $gArea == 'kravmaga' ) {
        kravmagaReport();
    }
        
    $fields = ["visible", "id", "anonymous", "hideAmount", "txnId", "listAs", "firstName", "lastName", "paymentAmount", "paymentFrequency", "address", "city", "state", "zip", "phone", "email"];
    if ($section == "all") {
        array_splice($fields, 0, 0, ["$section", "success"]);
    }

    echo "<br>";
    $jsx = [];
    $jsx[] = "setValue('area','$section')";
    $jsx[] = "addAction('download')";
    $js1 = join(';', $jsx);
    echo "<div class=\"employees tight_table\">"
    . "<div class=\"status\" id=statusBox>-</div><br>"
    . "<input type=button onclick=\"$js1\" value=\"Download\">";
    
    echo "<br><br>";
    echo "One Time Total: \$" . number_format(floatval($oneTime),2);
    echo ",&nbsp;&nbsp;";
    echo "Monthly Totals: \$" . number_format(floatval($monthly), 2);
    echo "</div>";
    
    echo "<br>";
    echo "<br>";

    echo "<ul class=sort>";
    echo "<li>Click on a column header to sort, click again to reverse sort</li>";
    echo "<li>All fields can be edited</li>";
    if( $control ) {
        echo "<li>You can see all donations, Office can only see ones marked Visible</li>";
        echo "<li>To delete an entry, first mark it not visible</li>";
    }
    echo "</ul>";

    echo "<br>";
    echo "<br>";

    echo "<table class=\"section sortable scrollable\">";
    echo "<thead>";
    echo "<tr>";
    
    if( 1 || $control ) {
        $jsx = [];
        $jsx[] = "setValue('area','$section')";
        $jsx[] = "setValue('func','delete')";
        $str = sprintf( "Are you sure you want to delete the selected donations?" );
        $jsx[] = "myConfirm('$str')";
        $js = sprintf("onclick=\"%s\"", join(';', $jsx));

        echo "<th><input type=button class=delete value=Del $js /></th>";
    }
    
    echo "<th>Date/Time</th>";
    foreach ($fields as $f) {
        if( $f == 'all' ) continue;
        printf("<th>%s</th>", ucfirst($f));
    }
    echo "</tr>\n";
    echo "</thead>";

    $sizes = [];
    $sizes['txnId'] = '14';
    $sizes['firstName'] = '20';
    $sizes['lastName'] = '20';
    $sizes['section'] = 6;
    $sizes['paymentAmount'] = '8';
    $sizes['address'] = '30';
    $sizes['city'] = '20';
    $sizes['state'] = '3';
    $sizes['phone'] = '10';
    $sizes['email'] = '30';

    echo "<tbody>";
    array_pop( $quals ); // This is really ugly, but it removes the visible qualifier
    $query = "select * from orders where " . implode(' and ', $quals );
    $query .= " order by time desc";

    $stmt = DoQuery($query);
    $num = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $freq = ($row["paymentFrequency"] == "monthly") ? "monthly" : "once";
        echo "<tr class=\"$freq\">";
        $phpdate = strtotime($row['time']);
        $mysqldate = date('m/d/Y H:m', $phpdate);
        $id = $row['id'];

        echo "<td class=center>";
        if( 1 || $control ) {
            if( $row['visible'] ) {
                $jsx = [];
                $jsx[] = "addField($id)";
                $jsx[] = "setValue('id',$id)";
                $jsx[] = "setValue('area','$section')";
                $jsx[] = "setValue('func','delete')";
                $str = sprintf( "Are you sure you want to delete the \$ %s donation from %s %s?",
                        number_format($row['paymentAmount'],2), $row['firstName'], $row['lastName']);
                $jsx[] = "myConfirm('$str')";
                $js = sprintf("onclick=\"%s\"", join(';', $jsx));
                echo "<p class=hidden id=del_box_$id><input type=checkbox name=group_$id value=1 onclick=\"addField('$id');\" /></p>";

            } else {
                echo "<p id=del_box_$id><input type=checkbox name=group_$id value=1 onclick=\"addField('$id');\" /></p>";
            }
        }
        echo "</td>";
        
//        if( $row['visible'] ) {
//            echo "<script type=\"text/javascript\">del_text_clear($id);</script>";
//        } else {
//            echo "<script type=\"text/javascript\">del_text_load($id);</script>";
//        }
//        

        
        echo "<td sorttable_customkey=\"{$row['time']}\">$mysqldate</td>";
        foreach ($fields as $f) {
            if( $f == 'all' ) continue;
            $ajax_id = "id=\"donations__{$f}__{$id}\"";
            $size = array_key_exists($f, $sizes) ? $sizes[$f] : 5;
            if ($f == "amount") {
                echo "<td class=\"sort r\">";
                printf("<input type=text size=$size class=\"ajax r\" $ajax_id value=\"%s\" sorttable_customkey=\"%.2f\"></td>",
                        number_format($row[$f], 2), $row[$f]);
            } elseif ($f == "frequency") {
                echo "<td class=\"sort\">";
                echo "<select class=\"jq ajax\" $ajax_id sorttable_customkey=\"{$row[$f]}\">";
                foreach (["oneTime", "monthly"] as $opt) {
                    $selected = ( $row[$f] == $opt ) ? "selected" : "";
                    echo "<option value=\"$opt\" $selected>$opt</option>";
                }
            } elseif( $f == "success") {
                echo "<td class=\"sort\">";
                if( $row[$f] == 1 ) {
                    $s = "Active";
                } elseif( $row[$f] == 2 ) {
                    $s = "Inactive";
                }
                printf("<input type=text size=$size class=\"ajax\" $ajax_id value=\"%s\" sorttable_customkey=\"%s\"></td>",
                        $s, $row[$f]);
            } elseif( $f == "visible" ) {
                echo "<td class=\"sort c\">";
                if( $row[$f] ) {
                    $state = "checked";
                    $val = 0;
                } else {
                    $state = "";
                    $val = 1;
                }
                printf("<input type=checkbox class=\"ajax\" $ajax_id value=$val $state onclick=\"deleteBoxState(event);\" sorttable_customkey=\"%s\" /></td>",
                        $row[$f]);
            } elseif( $f == "anonymous" || $f == "hideAmount" ) {
                echo "<td class=\"sort c\">";
                if( $row[$f] ) {
                    $state = "checked";
                    $val = 0;
                } else {
                    $state = "";
                    $val = 1;
                }
                printf("<input type=checkbox class=\"ajax\" $ajax_id value=$val $state sorttable_customkey=\"%s\"></td>",
                        $row[$f]);
            } else {
                echo "<td class=\"sort\">";
                printf("<input type=text size=$size class=\"ajax\" $ajax_id value='%s' sorttable_customkey=\"%s\"></td>",
                        $row[$f], strtolower($row[$f]));
            }
            echo "</td>";
        }
        echo "</tr>\n";
    }
    echo "</tbody>";
    echo "<tfoot>";
    $id = 0;
    $jsx = [];
    $jsx[] = "setValue('id',$id)";
    $jsx[] = "setValue('area','$section')";
    $jsx[] = "setValue('func','add')";
    $jsx[] = "addAction('update')";
    $js = sprintf("onclick=\"%s\"", join(';', $jsx));
    echo "<tr>";
    echo "<td class=c><input type=button class=add value=Add $js></td>";
    echo "<td>&nbsp;</td>";
    $row = [];
    foreach ($fields as $f) {
        $row[$f] = "";
        $tag = MakeTag(implode("_", [$f, $id]));
        $size = array_key_exists($f, $sizes) ? $sizes[$f] : 5;
        echo "<td>";
        if ($f == "amount") {
            printf("<input $tag type=text size=$size class=\"r\" placeholder=\"??\"></td>" );
        } elseif ($f == "frequency") {
            echo "<select $tag>";
            $selected = "selected";
                foreach (["oneTime", "monthly"] as $opt) {
                echo "<option value=\"$opt\" $selected>$opt</option>";
                $selected = "";
            }
        } elseif( $gArea == "all" && $f == "section" ) {
            echo "<select $tag>";
            $selected = "";
            foreach ($gSectionsFound as $opt) {
                echo "<option value=\"$opt\">$opt</option>";
                $selected = "selected";
            }
        } else {
            printf("<input type=text $tag size=$size value='%s' placeholder=\"??\"></td>",
                    $row[$f], $row[$f]);
        }
        echo "</td>";
    }
    $missingFields = [
        'method' => 'manual',
        'schedEndDate' => '0000-00-00',
        'schedFrequency' => 'Monthly',
        'schedStartDate' => date("%Y-%m-%d"),
        'schedNumPayments' => 12,
        'untilCancelled' => 0 ];
    foreach( $missingFields as $f => $v ) {
        $tag = MakeTag(implode("_", [$f, $id]));
        echo "<input type=hidden $tag value=\"$v\"></input>";
    }
    if( $section != "all" ) {
        $tag = MakeTag(implode("_", ['section', $id]));
        echo "<input type=hidden $tag value=\"$gArea\"></input>";
    }
    echo "</tr>";
    echo "</tfoot>";
    echo "</table>";
}

function displaySite() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    if ($gAction == 'logout')
        return;

    $uname  = "";
    if( $gUser->is_logged_in() ) {
        $uname = "$gUserName";
    } elseif( $gAction == "password" && $gFunc == "newpassword" ) {
        $stat = DoQuery( "select username from users where resetToken = '$gResetKey'" );
        if( $gPDO_num_rows  ) {
            list($str) = $stat->fetch(PDO::FETCH_NUM);
            $uname = "$str";
        }
    }
    $mode = (! $gMailLive) ? "<span class=mail-test>Mail Safe</span>" : "<span class=mail-live>** Mail Live **</span>";

    echo "$gSiteName (<span id=site-prod>User: {$uname}, {$mode}</span>): ";

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function displaySpecial() {
    include 'includes/globals.php';
    if( $gTrace ) {
        $gFunction[] = __FUNCTION__;
    }
    
    $jsx = [];
    $jsx[] = "setValue('mode','control')";
    $jsx[] = "setValue('area','bad-card')";
    $jsx[] = "addAction('special')";
    $js = implode(';', $jsx);
    echo "<input type=button"
    . " id=button-control"
    . " class=control"
    . " onclick=\"$js\""
    . " value='Bad Card' />";
    
    if( $gTrace ) {
        array_pop($gFunction);
    }
}

function dumpCSV() {
    include 'includes/globals.php';

    $section = func_get_arg(0);
    
    if(func_num_args() == 1 ) { # Manual Download
        $date = date('Ymd');
        $file = "Donor Report - $section - $date.csv";
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $file . '";');
        $fh = fopen('php://output', 'w');
        $mode = "manual";
        
    } else {
        $file = "$gSiteDir/tmp/Donor-Report-$section.csv";
        $fh = fopen($file, "w");
        $mode = "cron";
    }

    $quals = [];
    $quals[] = "success = 1";
    if( in_array($section, $gSectionsFound) )
        $quals[] = "section = \"$section\"";

    $query = "select * from orders where " . implode(" and ", $quals);

    $mapToLGL = [];
    $mapToLGL['time'] = 'Gift date';
    $mapToLGL['txnId'] = 'External gift ID';
    $mapToLGL['firstName'] = 'First name';
    $mapToLGL['lastName'] = 'Last name';
    $mapToLGL['address'] = 'Address line 1';
    $mapToLGL['city'] = 'City';
    $mapToLGL['state'] = 'State/province';
    $mapToLGL['zip'] = 'Postal/ZIP code';
    $mapToLGL['phone'] = 'Phone number';
    $mapToLGL['email'] = 'Email address';
    $mapToLGL['paymentAmount'] = 'Gift amount';
    $mapToLGL['paymentFrequency'] = 'Note text';
    $mapToLGL['paymentType'] = 'Payment type';
    $mapToLGL['section'] = 'Gift category';
    $mapToLGL['memo'] = 'Gift note';
    $mapToLGL['anonymous'] = 'Gift is anonymous?';
    $mapToLGL['listAsWeek'] = 'Tribute Dedication';
    
    $stmt = DoQuery($query);
    $num = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $num++;
        if ($num == 1) {
            $fields = [];
            foreach ($row as $key => $val) {
                if( array_key_exists($key, $mapToLGL) ) {
                    $fields[] = $mapToLGL[$key];
                } else {
                    $fields[] = $key;
                }
            }
            fputcsv($fh, $fields, ',');
        }
        $values = [];
        foreach ($row as $key => $val) {
            $values[] = $val;
        }
        fputcsv($fh, $values, ',');
    }
    fclose($fh);
    
    if( $mode == "manual" ) {
        exit();
    } else {
        $temp = DoQuery( "select value from mail where label = 'Email: Nightly' and enabled > 0");
        $dist_list = array();
        while( list( $name ) = $temp->fetch(PDO::FETCH_NUM) ) {
            $tmp = explode(",", $name);
            $dist_list[] = $tmp[0];
        }
        $dist = implode(" ", $dist_list);
        $sh_file =  "$gSiteDir/tmp/cron-email.sh";
        $fh = fopen($sh_file,"w");
        fputs($fh, "#!/bin/bash -x\n");
        fputs($fh, "cd $gSiteDir/tmp\n");
        fputs($fh, "echo | mailx -a $file -s \"Donor Database\" $dist\n");
        fclose($fh);
        chmod($sh_file, 0700);
        exec($sh_file,$output,$retval);
        #unlink($sh_file);
        if( $retval) {
            error_log(print_r($output,true) );
        }
    }
}

function initialize() {
    include 'includes/globals.php';
    $gDb = $gPDO[$gDbControlId]['inst'];

    $gAction = array_key_exists('action', $_POST) ? $_POST["action"] : "welcome";

    if (array_key_exists('last_login', $_SESSION)) {
        $delta = time() - strtotime($_SESSION['last_login']);
        error_log("$delta seconds since last login");
        if ($delta > $gMaxIdleTime) {
            session_unset();
            $gError = "Session Timed Out";
            $gAction = "welcome";
        }
    }
    $req = $_SERVER['QUERY_STRING'];
    if (!empty($req)) {
        $tmp = parse_str($req, $qs);
        if (array_key_exists('action', $qs) && $qs['action'] == 'password' &&
                array_key_exists('func', $qs) && $qs['func'] == 'newpassword') {
            $gAction = 'password';
            $gFunc = 'newpassword';
            $gFrom = 'email';
            $gResetKey = $qs['key'];
        } elseif (!array_key_exists('XDEBUG_SESSION_START', $qs)) {
            UserManager('logout');
            exit;
        }
    }

    $proto = ( array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on" ) ? "https" : "http";
    $gSourceCode = sprintf("%s://%s%s", $proto, $_SERVER['SERVER_NAME'], $_SERVER['SCRIPT_NAME']);
    $gFunction = array();

    $tmp = ['action', 'area', 'from', 'func', 'mode', 'where'];
    foreach ($tmp as $name) {
        $gn = 'g' . ucfirst($name);
        if (!isset($$gn)) {
            $$gn = array_key_exists($name, $_POST) ? $_POST[$name] : "";
        }
    }

    $gAccessNameToLevel = array();
    $gAccessNameEnabled = array();
    $gAccessLevels = array();
    $stmt = DoQuery('select * from privileges order by level desc');
    if ($stmt->rowCount() == 0) {
        $query = "insert into privileges set name = :name, level = :level, enabled = :enabled";
        DoQuery($query, [':name' => 'control', ':level' => 500, ':enabled' => 1]);
        DoQuery($query, [':name' => 'admin', ':level' => 400, ':enabled' => 0]);
        DoQuery($query, [':name' => 'office', ':level' => 300, ':enabled' => 0]);
        DoQuery($query, [':name' => 'all', ':level' => 0, ':enabled' => 0]);
        $stmt = DoQuery('select * from privileges order by level desc');
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gAccessNameToId[$row['name']] = $row['id'];
        $gAccessNameToLevel[$row['name']] = $row['level'];
        $gAccessNameEnabled[$row['name']] = $row['enabled'];
        $gAccessLevelEnabled[$row['level']] = $row['enabled'];
        $gAccessLevels[] = $row['name'];
    }

    loadMailSettings();

    $stmt = DoQuery("select value from misc where label = 'Site_Name'");
    if ($gPDO_num_rows) {
        list($gSiteName) = $stmt->fetch(PDO::FETCH_NUM);
    }

    $gModeToButtons = $gAreaToMode = [];
//=====================================    
    $mode = 'control';
    $buttons = [];
    $buttons[] = ['area' => 'source', 'js' => "setValue('func','display')",
        'bbl' => [['area' => 'source', 'label' => 'Refresh', 'js' => "setValue('func','display')"]]];
    $buttons[] = ['area' => 'backup', 'action' => 'backup'];
    $buttons[] = ['area' => 'payments', 'action' => 'display'];
    $buttons[] = ['area' => 'eventLog', 'label' => 'Event Log'];
    $buttons[] = ['area' => 'mail',
        'bbl' => [['area' => 'mail', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
    $buttons[] = ['area' => 'misc',
        'bbl' => [['area' => 'misc', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
    $buttons[] = ['area' => 'users',
        'bbl' => [['area' => 'users', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
    $buttons[] = ['area' => 'privileges',
        'bbl' => [['area' => 'privileges', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
//    $buttons[] = ['area' => 'debug', 'label' => 'Debug', 'js' => "setValue('func','display')"];
    $buttons[] = ['area' => 'special', 'label' => 'Special', 'js' => "setValue('func','special')"];

    $gModeToButtons[$mode] = $buttons;
    foreach ($buttons as $obj) {
        $gAreaToMode[$obj['area']] = $mode;
    }
//=====================================    
    $mode = 'admin';
    $buttons = array();
    $buttons[] = ['area' => 'categories', 'label' => 'Categories'];
    $buttons[] = ['area' => 'discounts', 'label' => 'Discounts'];
    $buttons[] = ['area' => 'sections', 'label' => 'Sections'];
    $buttons[] = ['area' => 'misc',
        'bbl' => [['area' => 'misc', 'action' => 'update', 'label' => 'Update', 'js' => "setValue('func','update')"]]];
    /*
      $buttons[] = ['area' => 'fees'];
      $buttons[] = ['area' => 'financials',
      'bbl' => [
      ['area' => 'financials', 'action' => 'display', 'label' => 'Reset Filters', 'js' => "setValue('func','reset')"]
      ]];
      $buttons[] = ['area' => 'discounts'];
      $buttons[] = ['area' => 'events', 'label' => 'Event Log',
      'bbl' => [['area' => 'events', 'action' => 'init', 'label' => 'Are you sure?', 'js' => "setValue('func','confirm')"]]];
      $buttons[] = ['area' => 'message'];
      $buttons[] = ['area' => 'statistics'];
     */
    $gModeToButtons[$mode] = $buttons;
    foreach ($buttons as $obj) {
        $gAreaToMode[$obj['area']] = $mode;
    }
//=====================================    
    $mode = 'office';
    $buttons = array();
    $stmt = DoQuery("select distinct section from orders order by section asc");
    while (list($name) = $stmt->fetch(PDO::FETCH_NUM)) {
        $buttons[] = ['area' => "$name", 'js' => "setValue('func','show'),setValue('area','$name')"];
    }
    $buttons[] = ['area' => 'all', 'js' => "setValue('func','show'),setValue('area','all')"];

    $gModeToButtons[$mode] = $buttons;
    foreach ($buttons as $obj) {
        $gAreaToMode[$obj['area']] = $mode;
    }
    
    $stmt = DoQuery( "select distinct section from orders");
    $gSectionsFound = [];
    while( list( $name ) = $stmt->fetch(PDO::FETCH_NUM) ) {
        $gSectionsFound[] = $name;
    }
/*
 * Verify new fields in Database
 */
    $stmt = DoQuery( "show columns from orders where field like '%item%'");
    if( $gPDO_num_rows == 0 ) {
        DoQuery( "ALTER TABLE `donations` ADD `itemName` VARCHAR(255) NULL DEFAULT NULL AFTER `section`;");
    }
}

function kravmagaReport() {
    include 'includes/globals.php';
    
    echo <<<EOT
<script type="text/javascript">
    section = 'kravmaga';
</script>
EOT;
    $stmt = DoQuery( "select id, code from discounts" );
    $dLabels = [];
    while( list( $id, $code ) = $stmt->fetch(PDO::FETCH_NUM) ) {
        $dLabels[$id] = $code;
    }
    $dLabels[0] = 'n/a';
    
    $colNames = $colHeaders = $colQuery = [];
    $stmt = DoQuery( "show columns from kravmaga like '%Count'");
    while( list( $colName ) = $stmt->fetch(PDO::FETCH_NUM) ) {
        $colNames[] = $colName;
        $str = preg_replace("/Count/","",$colName );
        $colHeaders[] = ucfirst($str);
        $colQuery[] = "sum($colName)";
    }
    
    echo "<h2>Signup Totals</h2>";
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Discount</th>";
    foreach( $colHeaders as $colName ) {
        echo "<th>#<br>$colName</th>";
    }
    echo "</tr>";
    echo "</thead>";
    
    $tmp = [];
    echo "<tbody>";
    $stmt1 = DoQuery("select distinct discountId from kravmaga ");
    while( list($did) = $stmt1->fetch(PDO::FETCH_NUM) ) {
        $sums = 0;
        
        $query = "select " . implode(',',$colQuery) . "from kravmaga"
            . " where discountId = $did and visible = 1";
        $stmt2 = DoQuery( $query );
        $row = $stmt2->fetch(PDO::FETCH_NUM);
        echo "<tr>";
        echo "<td class=center>$dLabels[$did]</td>";
        for( $i = 0; $i < count($colNames); $i++ ) {
            echo "<td class=center>" . $row[$i] . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    
    echo "<br>";
    
    echo "<h2>Details</h2>";
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Discount</th>";
    echo "<th>Time</th>";
    echo "<th>Inv#</th>";
    echo "<th>Name</th>";
    echo "<th>Phone</th>";
    echo "<th>Email</th>";
    foreach( $colHeaders as $colName ) {
        echo "<th>$colName</th>";
    }
    echo "<th>Detail</th>";
    echo "</tr>";
    echo "</thead>";
    
    echo "<tbody>";
    // kravmaga: a; donations: b
    $query = "select a.*, b.id, b.time, b.firstName, b.lastName, b.phone, b.email, b.orderDetail"
        . " from orders b inner join kravmaga a"
        . " on a.donationId = b.id"
        . " where b.success = 1 and b.visible = 1"
        . " order by a.discountId asc, b.time desc";
    $stmt = DoQuery( $query );
    while( $row = $stmt->fetch(PDO::FETCH_ASSOC ) ) {
        echo "<tr>";
        printf( "<td class=center>%s</td>", $dLabels[$row['discountId']]);
        printf( "<td class=center>%s</td>", $row['time']);
        printf( "<td class=center>%d</td>", $row['id']);
        $name = $row['lastName'] . ", " . $row['firstName'];
        printf( "<td class=center>%s</td>", $name);
        printf( "<td class=center>%s</td>", formatPhone($row['phone']));
        printf( "<td class=center>%s</td>", $row['email']);
        foreach( $colNames as $colName ) {
            printf( "<td class=center>%d</td>", $row[$colName]);
        }
        printf( "<td><textarea rows=2 cols=20>%s</textarea></td", $row['orderDetail']);        
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
}
function loadMailSettings() {
    include 'includes/globals.php';

    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $gMailAdmin = "";
    $gMailBackup = [];
    $gMailTesting = [];

    foreach( $gMailModes as $mode ) {
        DoQuery("select id from mail where mode = '$mode'");
        if($gPDO_num_rows == 0) {
            if( $mode == 'Live' ) {
                DoQuery( "insert into mail (mode, value, enabled) values ('$mode', 'If not enabled, all emails go to enabled Testing accounts', 0)");
            } elseif( $mode == 'Server' ) {
                DoQuery( "insert into mail (mode, value, enabled) values ('$mode', 0, 0)");
            } else {
                DoQuery("insert into mail (mode, value, enabled) values ('$mode','$gMailDefault',0)"); # set in the Site local-mailer.php
            }
        }
    }

    $stmt = DoQuery("select mode, value, enabled from mail");
    $gMailLive = 0;
    while (list( $mode, $value, $enabled ) = $stmt->fetch(PDO::FETCH_NUM)) {
        if( $mode == 'Live' && $enabled ) {
            $gMailLive = 1;
            
        } elseif( $mode == 'Server' ) {
            $gMailServer = $gMailDB[$value];
            if( ! array_key_exists('Usertext', $gMailServer ) ) {
                $gMailServer['Usertext'] = $gMailServer['Username'];
            }
            
        } else {
            $tmp = preg_split("/,/", $value, 0, PREG_SPLIT_NO_EMPTY);
            $j = count($tmp);
            if ($j == 1) {
                $email = $name = $tmp[0];
            } elseif ($j == 2) {
                $email = $tmp[0];
                $name = $tmp[1];
            }
            if( $mode == "Admin" ) {
                $gMailAdmin = ['email' => "$email", 'name' => "$name"];

            } elseif( $mode == 'Backup' && $enabled ) {
                $gMailBackup[] = ['email' => "$email", 'name' => "$name"];
                
            } elseif( $mode == 'Testing' && $enabled) {
                $gMailTesting[] = ['email' => "$email", 'name' => "$name"];
                
            }
        }
    }
    
    if( empty( $gMailAdmin ) ) {
        $gMailAdmin = $gMailDefault;
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function phase1() {     # Phase1 is for pre-output actions that would interfere with PDF production
    include 'includes/globals.php';

    if( in_array( $gAction, $gSectionsFound ) || $gAction == "all" ) {
        dumpCSV($action);
        exit();
    }
    $dpv_pre = "Begin";
    $dpv_phase = 1;
    $dpv_tag = "pre-html";
    
    if ($gDebug) {
        Logger('****************************************************************************');
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    addForm();

    if ($gAction == 'password' && $gFunc == 'verify') {
        Logger("Logging in from welcome, verifying password");
        UserManager('verify');
    }

    if( $gAction == "backup" ) {
        BackupMySql();
    }

    if ($gAction == 'logout' && $gEnableIdleTimer) {
        echo "<script type='text/javascript'>\n";
        echo "cancelIdleTimer();\n";
        echo "</script>\n";
    }
    
    $val = 0;
    if ($gUser->is_logged_in()) {
        Logger("user logged in");
        UserManager('load', $_SESSION['user_id']);
        $saveDb = $gDb;
        $gDb = $gPDO[$gDbControlId]['inst'];
        $stmt = DoQuery( "select priv_id from access where id = $gUserId");
        list($val) = $stmt->fetch(PDO::FETCH_NUM);
        $stmt = DoQuery( "select name from privileges where id = $val");
        list($gUserAccess) = $stmt->fetch(PDO::FETCH_NUM);
        $gDb = $saveDb;
        if( $gEnableIdleTimer ) {
            echo "<script type='text/javascript'>createIdleTimer();</script>";
        }
    } else if( $gAction == 'password' ) {
        // Don't interfere here
    } else {
//        $gAction !== 'reset' && $gAction !== 'sendReset' && $gAction !== 'password' || $gArea !== 'password' ) {
        Logger("user not logged in");
        $gAction = "welcome";
    }
//    $val = 6;

    logger("Phase1: gUserId: [$gUserId]");
    logger("Phase1: val : [$val]");

    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function phase2() { # updates
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }

    $dpv_pre = "Begin";
    $dpv_phase = 2;
    $dpv_tag = "pre-html";

    if ($gDebug) {
        Logger('****************************************************************************');
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    switch ($gAction) {
        case "add":
            addDonor();
            break;

        case "password":
            if( $gFunc == 'sendreset' ) {
                UserManager('sendResetLink');
                if( $gArea == 'users' ) {
                    $gAction = "display";
                } else {
                    $gAction = 'welcome';
                }
            } elseif( $gFunc == 'update' ) {
                UserManager('update');
                $gAction = 'display';
            }
            break;
        
        case "special":
            if( $gArea == 'bad-card' ) {
                
            }
            break;
        
        case "update":
            switch ($gArea) {
                case "debug":
                    MyDebug("update");
                    $gAction = "display";
                    break;

                case "categories":
                    updateCategories();
                    $gAction = "display";
                    break;
                
                case "discounts":
                    updateDiscounts();
                    $gAction = "display";
                    break;
                
                case "mail":
                    updateMail();
                    $gAction = "display";
                    break;

                case "misc":
                    updateMisc();
                    $gAction = "display";
                    break;
                    
                case 'password':
                    UserManager('update');
                    $gAction = 'display';
                    break;
                
                case 'privileges':
                    UserManager('update');
                    $gAction = 'display';
                    break;
                
                case "all":
                case in_array($gArea, $gSectionsFound):
                    if( $gFunc == 'delete') {
                        deleteDonor();
                    } elseif( $gFunc == 'add') {
                        addDonor();
                    }
                    $gAction = "display";
                    break;

                case "users":
                    if( $gFunc == 'reset') {
                        UserManager('mail');
                    } elseif( $gFunc == 'password' ) {
                        UserManager('update');
                        $gUser->login($_SESSION['username'],$_POST['newpassword1']);
                        Logger("user logged in");
                        UserManager('load', $_SESSION['userid']);
                        $saveDb = $gDb;
                        $gDb = $gPDO[$gDbControlId]['inst'];
                        $stmt = DoQuery( "select priv_id from access where id = $gUserId");
                        list($val) = $stmt->fetch(PDO::FETCH_NUM);
                        $stmt = DoQuery( "select name from privileges where id = $val");
                        list($gUserAccess) = $stmt->fetch(PDO::FETCH_NUM);
                        $gDb = $saveDb;
                        if( $gEnableIdleTimer ) {
                            echo "<script type='text/javascript'>createIdleTimer();</script>";
                        }
                    } elseif( $gFunc == 'delete' ) {
                        UserManager('delete');
                    } elseif( $gFunc == 'add' ) {
                        UserManager('add');
                    }
                    $gAction = "display";
                    break;
            }

            break;
    }

    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function phase3() { # display
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    $dpv_pre = "Begin";
    $dpv_phase = 3;
    $dpv_tag = "prePaletteDisplay";

    if ($gDebug) {
        Logger('****************************************************************************');
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    switch ($gAction ) {
        case "password":
            if ($gFunc == "send") {
                UserManager("forgot");
            }
            break;
   
        case "reset":
            if( $gArea == 'password' ) {
                UserManager('newpassword');
            }
            break;

        case 'sendReset': # Email reset link to address
            UserManager('sendResetLink');
            $gAction = 'welcome';
            $gError = "Please check your email for a reset link";
            break;
    }
    
    if( $gFunc == 'test' ) {
        UserMail('test');
    } elseif( $gFunc == 'special' ) {
        securityAddTime();
    }

    if ($gDebug) {
        $dpv_pre = "End";
        DumpPostVars(sprintf("-- %s Phase #%d (%s): gAction: [%s], gFrom: [%s], gMode: [%s], gArea: [%s], gFunc: [%s]",
                        $dpv_pre, $dpv_phase, $dpv_tag, $gAction, $gFrom, $gMode, $gArea, $gFunc));
    }

    if ($gTrace) {
        array_pop($gFunction);
    }
}

function updateMisc() {
    include( 'includes/globals.php' );
    $gFunction[] = __FUNCTION__;

    $id = $_POST['id'];
    if( $gFunc == "del" ) {
        DoQuery( "delete from misc where id = $id");

    } elseif( $gFunc == "add" )  {
        $str = htmlspecialchars($_POST['fields']);
        $tmp = explode(',', $str);
        $pairs = [];
        foreach( $tmp as $str ) {
            list( $key, $val ) = explode('|',$str);
            if( array_key_exists( "{$key}_0", $_POST ) ) {
                $val2 = $_POST["{$key}_0"];
            } else {
                $val2 = 0;
            }
            $vals[] = "$key = '$val2'";
        }
        $query = "insert into misc set " . implode(',', $vals );
        DoQuery($query);
    }
    array_pop($gFunction);
}

function securityAddTime() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
    }
    $stmt = DoQuery( "select id, value from misc where label = 'Security_NextTransactionTime'");
    list( $id, $val ) = $stmt->fetch(PDO::FETCH_NUM);
    $ts = strtotime($val);
    $ts = time() + 1 * 60;
    $val = date('Y-m-j H:i:s', $ts );
    DoQuery( "update misc set value = '$val' where id = $id");
            
    if( $gTrace) {
        array_pop($gFunction);
    }
}

function selectDB() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        if (defined('DB_OPEN')) {
            Logger('fy select (DB_OPEN defined):');
        } else {
            Logger('fy select (DB_OPEN not defined - first call):');
        }
    }
    for ($i = 0; $i < count($gPDO); $i++) {
        $tmp = [];
        $tmp[] = $gPDO[$i]['host'];
        $tmp[] = 'dbname=' . $gPDO[$i]['dbname'];
        $tmp[] = 'charset=' . $gPDO[$i]['charset'];
        $dsn = implode(';', $tmp);
        $user = $gPDO[$i]['user'];
        $pass = $gPDO[$i]['pass'];
        $attr = $gPDO[$i]['attr'];
        $gDbNames[$i] = $gPDO[$i]['dbname'];
        try {
            //create PDO connection
            if ($gProduction) {
                $attr[PDO::ATTR_ERRMODE] = PDO::ERRMODE_SILENT;
            } else {
                $attr[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            }
            $gPDO[$i]['inst'] = new PDO($dsn, $user, $pass, $attr);
        } catch (PDOException $e) {
            echo "failed to open DB #$i<br>";
            //show error
            echo '<p class="bg-danger">' . $e->getMessage() . '</p>';
            $gDbControl = NULL;
            throw $e;
        }
    }
    $gDb = $gPDO[$gDbControlId]['inst'];

    initialize();
}

function updateDiscounts() {
    include 'includes/globals.php';
    if ($gFunc == 'add') {
        $v = preg_split('/,/', $_POST['fields'], 0, PREG_SPLIT_NO_EMPTY);
        $flds = array_unique($v);
        $qx = [];
        $args = [];
        $i = 0;
        $ok = 1;
        foreach ($flds as $fld) {
            list( $label, $colName, $id ) = preg_split('/\|/', $fld);
            $var = implode("_", [$colName, $id]);
            if (array_key_exists($var, $_POST) && !empty($_POST[$var])) {
                $val = $_POST[$var];
                $qx[] = sprintf("`%s` = :v%d", $colName, $i);
                $args[":v$i"] = $val;
                $i++;
            }
        }
        $query = "insert into discounts set " . join(',', $qx);
        if ($ok) {
            Logger("query: [$query]");
            Logger("args: [" . print_r($args, true) . "]");
            DoQuery($query, $args);
        }
    } elseif ($gFunc == 'del') {
        $id = $_POST['id'];
        DoQuery("delete from discounts where id = :id", [':id' => $id]);
    }
}
function updateCategories() {
    include 'includes/globals.php';
    if ($gFunc == 'add') {
        $v = preg_split('/,/', $_POST['fields'], 0, PREG_SPLIT_NO_EMPTY);
        $flds = array_unique($v);
        $qx = [];
        $args = [];
        $i = 0;
        $ok = 1;
        foreach ($flds as $fld) {
            list( $label, $colName, $id ) = preg_split('/\|/', $fld);
            $var = implode("_", [$colName, $id]);
            if (array_key_exists($var, $_POST) && !empty($_POST[$var])) {
                $val = $_POST[$var];
                $qx[] = sprintf("`%s` = :v%d", $colName, $i);
                $args[":v$i"] = $val;
                $i++;
            }
        }
        $id = rand(1000, 9999);
        $numFound = 1;
        while( $numFound > 0 ) {
            $stmt = DoQuery( "select count(id) from categories where id = $id" );
            list($numFound) = $stmt->fetch(PDO::FETCH_NUM);
            if( $numFound > 0 ) {
                $id = rand(1000,9999);
            } 
        }
        $qx[] = sprintf("`%s` = :v%d", 'id', $i);
        $args[":v$i"] = $id;
        $query = "insert into categories set " . join(',', $qx);
        if ($ok) {
            Logger("query: [$query]");
            Logger("args: [" . print_r($args, true) . "]");
            DoQuery($query, $args);
        }
    } elseif ($gFunc == 'del') {
        $id = $_POST['id'];
        DoQuery("delete from categories where id = :id", [':id' => $id]);
    }
}

function updateMail() {
    include 'includes/globals.php';
    if ($gTrace) {
        $gFunction[] = __FUNCTION__;
        Logger();
    }
    if ($gFunc == 'add') {
        $v = preg_split('/,/', $_POST['fields'], 0, PREG_SPLIT_NO_EMPTY);
        $flds = array_unique($v);
        $qx = [];
        $args = [];
        $i = 0;
        $ok = 1;
        foreach ($flds as $fld) {
            list( $label, $colName, $id ) = preg_split('/\|/', $fld);
            $var = implode("_", [$colName, $id]);
            if (array_key_exists($var, $_POST) && !empty($_POST[$var])) {
                $val = $_POST[$var];
                if (stripos($val, "email: admin") !== false) {
                    DoQuery("select id from mail where lower(label) like \"%email: admin%\"");
                    if ($gPDO_num_rows > 0)
                        $ok = 0;
                }
                if (stripos($val, "email: default") !== false) {
                    DoQuery("select id from mail where lower(label) like \"%email: default%\"");
                    if ($gPDO_num_rows > 0)
                        $ok = 0;
                }
                $qx[] = sprintf("`%s` = :v%d", $colName, $i);
                $args[":v$i"] = $val;
                $i++;
            }
        }
        $query = "insert into mail set " . join(',', $qx);
        if ($ok) {
            Logger("query: [$query]");
            Logger("args: [" . print_r($args, true) . "]");
            DoQuery($query, $args);
        }
    } elseif ($gFunc == 'del') {
        $id = $_POST['id'];
        DoQuery("delete from mail where id = :id", [':id' => $id]);
    } elseif ($gFunc == 'update') {
        $v = preg_split('/,/', $_POST['fields'], 0, PREG_SPLIT_NO_EMPTY);
        $flds = array_unique($v);
        foreach ($flds as $fld) {
            $args = [];
            list( $label, $colName, $id ) = preg_split('/\|/', $fld);
            $query = "update mail set " . sprintf("%s = :v1", $colName);
            $query .= " where id = :v2";
            $var = implode("_", [$colName, $id]);
            $newVal = array_key_exists($var, $_POST) ? $_POST[$var] : 0;
            $args[":v1"] = $newVal;
            $args[":v2"] = $id;
            DoQuery($query, $args);
        }
    }
    loadMailSettings();
    if ($gTrace) {
        array_pop($gFunction);
    }
}
