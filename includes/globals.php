<?php

global $mysql_last_id;
global $mysql_numrows;
global $mysql_result;
#=====================================================

// This are the PDO global variables that I will use

global $gPDO;
global $gPDO_lastInsertID;
global $gPDO_num_rows;

#=====================================================

global $gAccessAdmin;
global $gAccessControl;
global $gAccessLevel;
global $gAccessLevelEnabled;
global $gAccessLevels;
global $gAccessLevels;
global $gAccessNameEnabled;
global $gAccessNameToId;
global $gAccessNameToLevel;
global $gAccessOffice;
global $gAction;
global $gArea;
global $gAreaToMode;
global $gAuctionYear;
global $gBannerMode;
global $gCrumbs;
global $gDatabases;     # Array that holds all the Db instances
global $gDb;            # This is the database id for all queries
global $gDbControlId;
global $gDbCurrentId;   # Fiscal Year ID for the current year
global $gDbLabels;
global $gDbNames;
global $gDbNextId;      # Fiscal Year ID for the next year
global $gDbPrefix;
global $gDebug;
global $gDreamweaver;
global $gEnrollMeFiscalYear;
global $gEnrollMeKnownStatus;
global $gError;
global $gFamilyLastNames;
global $gFamilyName;
global $gFiscalYearLookup;
global $gFrom;
global $gFunc; # used for buttons/actions
global $gFunction; # used for call tree
global $gGala;
global $gId;
global $gIHDSStatus;
global $gInFamilies;
global $gLF;
global $gMail;
global $gMailAdmin;
global $gMailBackup;
global $gMailDB;
global $gMailDefault;
global $gMailLive;
global $gMailServer;
global $gMailSignature;
global $gMailSignatureImage;
global $gMailSignatureImageSize;
global $gMailTesting;
global $gMaxIdleTime;
global $gMode;
global $gModeToButtons;
global $gPreDreamweaver;
global $gProduction;
global $gResetKey;
global $gRosterFiscalYear;
global $gRosterSessionTitle;
global $gSidebarButtons;
global $gSiteDir;
global $gSiteName;
global $gSiteSubPath;
global $gSourceCode;
global $gSpecialOutput;
global $gSpiritIDstats;
global $gSpiritIDtoDesc;
global $gSpiritIDtoType;
global $gTestModeEnabled;
global $gTrace;
global $gUserId;
global $gUserName;
global $gUserAccess;
global $gUsers;

global $error;
global $user;           // Object: active user

global $time_offset;

$gFunction = array('index.php');
#=====================================================
global $PaymentCredit;
global $PaymentCheck;
global $PaymentCall;

$PaymentCheck = 1;
$PaymentEFT = 2;
$PaymentCreditCard = 3;

#=====================================================
global $PledgeTypeFinancial;
global $PledgeTypeSpiritual;
global $PledgeTypeFinGoal;

$PledgeTypeFinancial = 1;
$PledgeTypeSpiritual = 2;
$PledgeTypeFinGoal = 3;

#=====================================================
global $SpiritualTorah;
global $SpiritualAvodah;
global $SpiritualGemilut;

$SpiritualTorah = 1;
$SpiritualAvodah = 2;
$SpiritualGemilut = 3;

#=====================================================
# Auction Specific
#=====================================================
global $gCategories;
global $gPackages;
global $gPreSelected; # set to item_id
global $gPreUser;     # set to user_hash

global $gStatus;
global $gStatusOpen;
global $gStatusClosed;
global $gStatusHidden;

$gStatus = array();
$gStatusOpen = 0;
$gStatusClosed = 1;
$gStatusHidden = 2;
$gStatus[ $gStatusOpen   ] = 'Open';
$gStatus[ $gStatusClosed ] = 'Closed';
$gStatus[ $gStatusHidden ] = 'Hidden';

global $gSendTop;
global $gSendOld;
global $gSendBought;
global $gSendOldBought;

$gSendTop = 1;
$gSendOld = 2;
$gSendBought = 3;
$gSendOldBought = 4;

global $gFees;
global $gFeeIdApplication;
global $gFeeIdMaterials;

global $gDiscounts;
global $gDebugInLine; # 0
global $gDebugErrorLog; # 1
global $gDebugWindow; # 2
global $gDebugHTML; # 3
global $gDebugAll;

$gDebugInLine = 2**0;
$gDebugErrorLog = 2**1;
$gDebugWindow = 2**2;
$gDebugHTML = 2**3;
$gDebugAll = 2**4 - 1;

$bit0 = 2 ** 0;
$bit1 = 2 ** 1;
$bit2 = 2 ** 2;
$bit3 = 2 ** 3;
$bit4 = 2 ** 4;
$bit5 = 2 ** 5;
$bit6 = 2 ** 6;
$bit7 = 2 ** 7;
$bit8 = 2 ** 8;
$bit9 = 2 ** 9;

$gDreamweaver = 1;

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