<?php
/**
 * Main update script
 *
 * @package    phpMyFAQ 
 * @subpackage Installation
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Thomas Melchinger <t.melchinger@uni.de>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2002-01-10
 * @copyright  2002-2009 phpMyFAQ Team
 * @version    SVN: $Id$
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

define('NEWVERSION', '2.5.0');
define('COPYRIGHT', '&copy; 2001-2009 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

require_once PMF_ROOT_DIR.'/inc/autoLoader.php';
require_once PMF_ROOT_DIR.'/inc/constants.php';

$step    = PMF_Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = PMF_Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query   = array();

/**
 * Print out the HTML Footer
 *
 * @return   void
 * @access   public
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function HTMLFooter()
{
    printf('<p class="center">%s</p></body></html>', COPYRIGHT);
}

/**
 * Returns the SQL query to drop a DEFAULT constraint for the given database type.
 *
 * @param   string  Table name
 * @param   string  Column name
 * @param   string  Database type. Default: mssql
 * @return  string
 * @access  public
 * @since   2007-06-19
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 */
function writeDropDefaultConstraintQuery($sTableName, $sColumnName, $dbtype = 'mssql')
{
    $query = '';

    switch($dbtype) {
        case 'mssql':
        case 'sybase':
            $query = "
                -- Find the name of the constraint for the given table and column name
                DECLARE @default_constraint_name VARCHAR(255)
                SELECT
                	@default_constraint_name = t1.name
                FROM
                	sysobjects t1
                INNER JOIN
                	syscolumns t2 ON t1.id = t2.cdefault
                INNER JOIN
                	sysobjects t3 ON t1.parent_obj = t3.id
                WHERE (t3.name = '".$sTableName."') AND (t2.name = '".$sColumnName."')
                -- Drop the constraint using its name, if any
                IF @default_constraint_name IS NOT NULL
                	EXEC('ALTER TABLE ".$sTableName." DROP CONSTRAINT ' + @default_constraint_name)";
            break;
    }

    return $query;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>phpMyFAQ <?php print NEWVERSION; ?> Update</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
    <style type="text/css"><!--
    body {
        margin: 10px;
        padding: 0px;
        font-size: 12px;
        font-family: "Bitstream Vera Sans", "Trebuchet MS", Geneva, Verdana, Arial, Helvetica, sans-serif;
        background: #ffffff;
        color: #000000;
    }
    #header {
        margin: auto;
        padding: 25px;
        background: #E1F0A6;
        color: #234361;
        font-size: 36px;
        font-weight: bold;
        text-align: center;
        border-right: 3px solid silver;
        border-bottom: 3px solid silver;
        -moz-border-radius: 20px 20px 20px 20px;
        border-radius: 20px 20px 20px 20px;
    }
    #header h1 {
        font-family: "Trebuchet MS", Geneva, Verdana, Arial, Helvetica, sans-serif;
        margin: auto;
        text-align: center;
    }
    .center {
        text-align: center;
    }
    fieldset.installation {
        margin: auto;
        border: 1px solid black;
        width: 550px;
        margin-top: 10px;
    }
    legend.installation {
        border: 1px solid black;
        background-color: #D5EDFF;
        padding: 4px 8px 4px 8px;
        font-size: 14px;
        font-weight: bold;
        -moz-border-radius: 5px 5px 5px 5px;
        border-radius: 5px 5px 5px 5px;
    }
    .input {
        width: 200px;
        background-color: #f5f5f5;
        border: 1px solid black;
        margin-bottom: 8px;
    }
    span.text {
        width: 250px;
        float: left;
        padding-right: 10px;
        line-height: 20px;
    }
    #admin {
        line-height: 20px;
        font-weight: bold;
    }
    .help {
        cursor: help;
        border-bottom: 1px dotted Black;
        font-size: 14px;
        font-weight: bold;
        padding-left: 5px;
    }
    .button {
        background-color: #89AC15;
        border: 3px solid #000000;
        color: #ffffff;
        font-weight: bold;
        font-size: 24px;
        padding: 10px 30px 10px 30px;
        -moz-border-radius: 10px 10px 10px 10px;
        border-radius: 10px 10px 10px 10px;
    }
    .error {
        margin: auto;
        margin-top: 20px;
        width: 600px;
        text-align: center;
        padding: 10px;
        line-height: 20px;
        background-color: #f5f5f5;
        border: 1px solid black;
    }
    --></style>
</head>
<body>

<h1 id="header">phpMyFAQ <?php print NEWVERSION; ?> Update</h1>
<?php
if (!@is_readable(PMF_ROOT_DIR.'/inc/data.php')) {
    print '<p class="center">It seems you never run a version of phpMyFAQ.<br />Please use the <a href="installer.php">install script</a>.</p>';
    HTMLFooter();
    die();
}
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
    print '<p class="center">You need PHP 5.2.0 or later!</p>';
    HTMLFooter();
    die();
}

require PMF_ROOT_DIR . '/inc/data.php';
require PMF_ROOT_DIR . '/inc/functions.php';

define('SQLPREFIX', $DB['prefix']);
$db = PMF_Db::dbSelect($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
    
/**************************** STEP 1 OF 4 ***************************/
if ($step == 1) {
?>
<form action="update.php?step=2" method="post">
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 1 of 4)</strong></legend>
<p>This update will work <strong>only</strong> for the following versions:</p>
<ul type="square">
    <li>phpMyFAQ 1.6.x</li>
    <li>phpMyFAQ 2.0.x</li>
</ul>
<p>This update will <strong>not</strong> work for the following versions:</p>
<ul type="square">
    <li>phpMyFAQ 0.x</li>
    <li>phpMyFAQ 1.0.x</li>
    <li>phpMyFAQ 1.1.x</li>
    <li>phpMyFAQ 1.2.x</li>
    <li>phpMyFAQ 1.3.x</li>
    <li>phpMyFAQ 1.4.x</li>
    <li>phpMyFAQ 1.5.x</li>
</ul>
<p><strong>Please make a full backup of your database before running this update.</strong></p>

<p>Please select your current version:</p>
<select name="version" size="1">
    <option value="1.6.0">phpMyFAQ 1.6.0 and later</option>
    <option value="2.0.0-alpha">phpMyFAQ 2.0.0-alpha</option>
    <option value="2.0.0-beta">phpMyFAQ 2.0.0-beta</option>
    <option value="2.0.0-beta2">phpMyFAQ 2.0.0-beta2</option>
    <option value="2.0.0-RC">phpMyFAQ 2.0.0-RC and later</option>
    <option value="2.0.2">phpMyFAQ 2.0.2 and later</option>
    <option value="2.5.0-alpha">phpMyFAQ 2.5.0-alpha</option>
    <option value="2.5.0-alpha2">phpMyFAQ 2.5.0-alpha2</option>
    <option value="2.5.0-beta">phpMyFAQ 2.5.0-beta</option>
    <option value="2.5.0-RC">phpMyFAQ 2.5.0-RC and later</option>
    <option value="2.5.0-RC3">phpMyFAQ 2.5.0-RC3 and later</option>
</select>

<p class="center"><input type="submit" value="Go to step 2 of 4" class="button" /></p>
</fieldset>
</form>
<?php
    HTMLFooter();
}

/**************************** STEP 2 OF 4 ***************************/
if ($step == 2) {
    $test1 = $test2 = $test3 = $test4 = $test5 = 0;

    if (!@is_writeable(PMF_ROOT_DIR."/inc/data.php")) {
        print "<p class=\"error\"><strong>Error:</strong> The file ../inc/data.php or the directory ../inc is not writeable. Please correct this!</p>";
    } else {
        $test1 = 1;
    }
    if (!@is_writeable(PMF_ROOT_DIR."/inc/config.php") && version_compare($version, '2.0.0-alpha', '<')) {
        print "<p class=\"error\"><strong>Error:</strong> The file ../inc/config.php is not writeable. Please correct this!</p>";
    } else {
        $test2 = 1;
    }
    if (!@copy(PMF_ROOT_DIR."/inc/data.php", PMF_ROOT_DIR."/inc/data.bak.php")) {
        print "<p class=\"error\"><strong>Error:</strong> The backup file ../inc/data.bak.php could not be written. Please correct this!</p>";
    } else {
        $test3 = 1;
    }
    if (!@copy(PMF_ROOT_DIR."/inc/config.php", PMF_ROOT_DIR."/inc/config.bak.php") && version_compare($version, '2.0.0-alpha', '<')) {
        print "<p class=\"error\"><strong>Error:</strong> The backup file ../inc/config.bak.php could not be written. Please correct this!</p>";
    } else {
        $test4 = 1;
    }

    if ('1.5.' == substr($_POST['version'], 0, 4)) {
        print "<p class=\"error\"><strong>Error:</strong> You can't upgrade from phpMyFAQ 1.5.x to ".NEWVERSION.". Please upgrade first to the latest version of phpMyFAQ 2.0.x.</p>";
    } else {
        $test5 = 1;
    }

    // is everything is okay?
    if ($test1 == 1 && $test2  == 1 && $test3  == 1 && $test4 == 1 && $test5 == 1) {
?>
<form action="update.php?step=3" method="post">
<input type="hidden" name="version" value="<?php print $version; ?>" />
<fieldset class="installation">
    <legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 2 of 4)</strong></legend>
    <p>A backup of your database configuration file has been made.</p>
    <p class="center"><input type="submit" value="Go to step 3 of 4" class="button" /></p>
</fieldset>
</form>
<?php
        HTMLFooter();
    } else {
        print "<p class=\"error\"><strong>Error:</strong> Your version of phpMyFAQ could not updated.</p>\n";
        HTMLFooter();
        die();
    }
}

/**************************** STEP 3 OF 4 ***************************/
if ($step == 3) {
?>
<form action="update.php?step=4" method="post">
<input type="hidden" name="version" value="<?php print $version; ?>" />
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 3 of 4)</strong></legend>
<?php
    if (version_compare($version, '2.0.0-alpha', '<')) {
        require_once(PMF_ROOT_DIR."/inc/config.php");
    }
    if (version_compare($version, '2.0.0', '<')) {
        $PMF_CONF['mod_rewrite']  = isset($PMF_CONF['mod_rewrite']) ? $PMF_CONF['mod_rewrite'] : '';
        $PMF_CONF['ldap_support'] = isset($PMF_CONF['ldap_support']) ? $PMF_CONF['ldap_support'] : '';
        $PMF_CONF['disatt']       = isset($PMF_CONF['disatt']) ? $PMF_CONF['disatt'] : '';
        $PMF_CONF['ipcheck']      = isset($PMF_CONF['ipcheck']) ? $PMF_CONF['ipcheck'] : '';
        if (version_compare($version, '1.6.1', '<')) {
            $PMF_CONF['spamEnableSafeEmail']   = isset($PMF_CONF['spamEnableSafeEmail']) ? $PMF_CONF['spamEnableSafeEmail'] : 'TRUE';
            $PMF_CONF['spamCheckBannedWords']  = isset($PMF_CONF['spamCheckBannedWords']) ? $PMF_CONF['spamCheckBannedWords'] : 'TRUE';
            $PMF_CONF['spamEnableCatpchaCode'] = isset($PMF_CONF['spamEnableCatpchaCode']) ? $PMF_CONF['spamEnableCatpchaCode'] : 'TRUE';
        } else {
            $PMF_CONF['spamEnableSafeEmail']   = isset($PMF_CONF['spamEnableSafeEmail']) ? $PMF_CONF['spamEnableSafeEmail'] : '';
            $PMF_CONF['spamCheckBannedWords']  = isset($PMF_CONF['spamCheckBannedWords']) ? $PMF_CONF['spamCheckBannedWords'] : '';
            $PMF_CONF['spamEnableCatpchaCode'] = isset($PMF_CONF['spamEnableCatpchaCode']) ? $PMF_CONF['spamEnableCatpchaCode'] : '';
        }
?>
<input type="hidden" name="edit[language]" value="<?php print $PMF_CONF["language"]; ?>" />
<input type="hidden" name="edit[detection]" value="<?php print $PMF_CONF["detection"]; ?>" />
<input type="hidden" name="edit[title]" value="<?php print str_replace('"', '&quot;', $PMF_CONF["title"]); ?>" />
<input type="hidden" name="edit[version]" value="<?php print NEWVERSION; ?>" />
<input type="hidden" name="edit[metaDescription]" value="<?php print str_replace('"', '&quot;', $PMF_CONF["metaDescription"]); ?>" />
<input type="hidden" name="edit[metaKeywords]" value="<?php print str_replace('"', '&quot;', $PMF_CONF["metaKeywords"]); ?>" />
<input type="hidden" name="edit[metaPublisher]" value="<?php print str_replace('"', '&quot;', $PMF_CONF["metaPublisher"]); ?>" />
<input type="hidden" name="edit[adminmail]" value="<?php print $PMF_CONF["adminmail"]; ?>" />
<input type="hidden" name="edit[msgContactOwnText]" value="<?php print str_replace('"', '&quot;', $PMF_CONF["msgContactOwnText"]); ?>" />
<input type="hidden" name="edit[copyright_eintrag]" value="<?php print str_replace('"', '&quot;', $PMF_CONF["copyright_eintrag"]); ?>" />
<input type="hidden" name="edit[send2friend_text]" value="<?php print str_replace('"', '&quot;', $PMF_CONF["send2friend_text"]); ?>" />
<input type="hidden" name="edit[attmax]" value="<?php print $PMF_CONF["attmax"]; ?>" />
<input type="hidden" name="edit[disatt]" value="<?php print $PMF_CONF["disatt"]; ?>" />
<input type="hidden" name="edit[tracking]" value="<?php print $PMF_CONF["tracking"]; ?>" />
<input type="hidden" name="edit[enableadminlog]" value="<?php print $PMF_CONF["enableadminlog"]; ?>" />
<input type="hidden" name="edit[ipcheck]" value="<?php print $PMF_CONF["ipcheck"]; ?>">
<input type="hidden" name="edit[numRecordsPage]" value="<?php print $PMF_CONF["numRecordsPage"]; ?>" />
<input type="hidden" name="edit[numNewsArticles]" value="<?php print $PMF_CONF["numNewsArticles"]; ?>" />
<input type="hidden" name="edit[bannedIP]" value="<?php print $PMF_CONF["bannedIP"]; ?>" />
<input type="hidden" name="edit[mod_rewrite]" value="<?php print $PMF_CONF["mod_rewrite"]; ?>" />
<input type="hidden" name="edit[ldap_support]" value="<?php print $PMF_CONF["ldap_support"]; ?>" />
<input type="hidden" name="edit[spamEnableSafeEmail]" value="<?php print $PMF_CONF["spamEnableSafeEmail"]; ?>" />
<input type="hidden" name="edit[spamCheckBannedWords]" value="<?php print $PMF_CONF["spamCheckBannedWords"]; ?>" />
<input type="hidden" name="edit[spamEnableCatpchaCode]" value="<?php print $PMF_CONF["spamEnableCatpchaCode"]; ?>" />
<?php
    }
?>
<p class="center">The configuration will be updated after the next step.</p>
<p class="center"><input type="submit" value="Go to step 4 of 4" class="button" /></p>
</fieldset>
</form>
<?php
    HTMLFooter();
}

/**************************** STEP 4 OF 4 ***************************/
if ($step == 4) {
    if (version_compare($version, '2.0.0-alpha', '<')) {
        require_once PMF_ROOT_DIR . '/inc/config.php';
    }

    require_once PMF_ROOT_DIR . '/inc/Configuration.php';
    require_once PMF_ROOT_DIR . '/inc/Db.php';
    require_once PMF_ROOT_DIR . '/inc/PMF_DB/Driver.php';
    require_once PMF_ROOT_DIR . '/inc/Link.php';
    
    $images = array();

    //
    // UPDATES FROM 2.0-ALPHA
    //
    if (version_compare($version, '2.0.0-alpha', '<')) {
        // Fix old/odd errors
        // 1/1. Fix faqchanges.usr
        switch($DB["type"]) {
            default:
                $query[] = 'UPDATE '.SQLPREFIX.'faqchanges SET usr = 1 WHERE usr = 0';
                break;
        }
        // Start 1.6.x -> 2.0.0 migration
        // 1/13. Fix faqfragen table
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Create the new faqquestions table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqquestions (
                            id integer NOT NULL,
                            ask_username varchar(100) NOT NULL,
                            ask_usermail varchar(100) NOT NULL,
                            ask_rubrik integer NOT NULL,
                            ask_content text NOT NULL,
                            ask_date varchar(20) NOT NULL,
                            is_visible char(1) default 'Y',
                            PRIMARY KEY (id))";
                // Copy data from the faqfragen table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqquestions
                            (id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date)
                            SELECT id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date
                            FROM '.SQLPREFIX.'faqfragen';
                // Drop the faqfragen table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqfragen';
                break;
            case 'pgsql':
                // Create the new faqquestions table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqquestions (
                            id SERIAL NOT NULL,
                            ask_username varchar(100) NOT NULL,
                            ask_usermail varchar(100) NOT NULL,
                            ask_rubrik varchar(100) NOT NULL,
                            ask_content text NOT NULL,
                            ask_date varchar(20) NOT NULL,
                            is_visible char(1) default 'Y',
                            PRIMARY KEY (id))";
                // Copy data from the faqfragen table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqquestions
                            (id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date)
                            SELECT id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date
                            FROM '.SQLPREFIX.'faqfragen';
                // Drop the faqfragen table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqfragen';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqfragen RENAME TO '.SQLPREFIX.'faqquestions';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqquestions ADD is_visible CHAR NOT NULL DEFAULT \'Y\' AFTER ask_date';
                break;
        }
        // 2/13. Fix faqcategories table
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Rename the current faqcategories table
                $query[] = 'EXEC sp_rename \''.SQLPREFIX.'faqcategories\', \''.SQLPREFIX.'faqcategories_PMF16x_old\'';
                // Create the new faqcategories table
                // Set the admin (id == 1) as the owner of the pre-existing categories
                $query[] = "CREATE TABLE ".SQLPREFIX."faqcategories (
                            id integer NOT NULL,
                            lang varchar(5) NOT NULL,
                            parent_id SMALLINT NOT NULL,
                            name varchar(255) NOT NULL,
                            description varchar(255) NOT NULL,
                            user_id integer NOT NULL DEFAULT 1,
                            PRIMARY KEY (id, lang))";
                // Copy data from the faqcategories_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqcategories
                            (id, lang, parent_id, name, description)
                            SELECT id, lang, parent_id, name, description
                            FROM '.SQLPREFIX.'faqcategories_PMF16x_old';
                // Drop the faqcategories_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqcategories_PMF16x_old';
                break;
            case 'pgsql':
                // Rename the current faqcategories table
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcategories RENAME TO '.SQLPREFIX.'faqcategories_PMF16x_old';
                // Create the new faqcategories table
                $query[] = "CREATE TABLE  ".SQLPREFIX."faqcategories (
                            id SERIAL NOT NULL,
                            lang varchar(5) NOT NULL,
                            parent_id int4 NOT NULL,
                            name varchar(255) NOT NULL,
                            description varchar(255) NOT NULL,
                            user_id int4 NULL,
                            PRIMARY KEY (id, lang))";
                // Copy data from the faqcategories_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqcategories
                            (id, lang, parent_id, name, description)
                            SELECT id, lang, parent_id, name, description
                            FROM '.SQLPREFIX.'faqcategories_PMF16x_old';
                // Set the admin (id == 1) as the owner of the pre-existing categories
                $query[] = 'UPDATE '.SQLPREFIX.'faqcategories SET user_id = 1';
                // Drop the faqcategories_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqcategories_PMF16x_old';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcategories ADD user_id INT(11) NOT NULL AFTER description';
                // Set the admin (id == 1) as the owner of the pre-existing categories
                $query[] = 'UPDATE '.SQLPREFIX.'faqcategories SET user_id = 1';
                break;
        }
        // 3/13. Fix faqdata table
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Rename the current faqdata table
                $query[] = 'EXEC sp_rename \''.SQLPREFIX.'faqdata\', \''.SQLPREFIX.'faqdata_PMF16x_old\'';
                // Create the new faqdata table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata (
                            id integer NOT NULL,
                            lang varchar(5) NOT NULL,
                            solution_id integer NOT NULL,
                            revision_id integer NOT NULL DEFAULT 0,
                            active char(3) NOT NULL,
                            keywords text NOT NULL,
                            thema text NOT NULL,
                            content text NOT NULL,
                            author varchar(255) NOT NULL,
                            email varchar(255) NOT NULL,
                            comment char(1) default 'y',
                            datum varchar(15) NOT NULL,
                            links_state varchar(7) NOT NULL DEFAULT '',
                            links_check_date integer DEFAULT 0 NOT NULL,
                            date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                            date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                            PRIMARY KEY (id, lang))";
                // Copy data from the faqdata_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqdata
                            (id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum)
                            SELECT id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum
                            FROM '.SQLPREFIX.'faqdata_PMF16x_old';
                // Drop the faqdata_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqdata_PMF16x_old';
                break;
            case 'pgsql':
                // Rename the current faqdata table
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata RENAME TO '.SQLPREFIX.'faqdata_PMF16x_old';
                // Create the new faqdata table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata (
                            id SERIAL NOT NULL,
                            lang varchar(5) NOT NULL,
                            solution_id int4 NOT NULL,
                            revision_id int4 NOT NULL DEFAULT 0,
                            active char(3) NOT NULL,
                            keywords text NOT NULL,
                            thema text NOT NULL,
                            content text NOT NULL,
                            author varchar(255) NOT NULL,
                            email varchar(255) NOT NULL,
                            comment char(1) NOT NULL default 'y',
                            datum varchar(15) NOT NULL,
                            links_state varchar(7) NOT NULL,
                            links_check_date int4 DEFAULT 0 NOT NULL,
                            date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                            date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                            PRIMARY KEY (id, lang))";
                // Copy data from the faqdata_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqdata
                            (id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum)
                            SELECT id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum
                            FROM '.SQLPREFIX.'faqdata_PMF16x_old';
                // Drop the faqdata_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqdata_PMF16x_old';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata ADD links_state VARCHAR(7) NOT NULL AFTER datum';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata ADD links_check_date INT(11) NOT NULL DEFAULT 0 AFTER links_state';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata ADD date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\' AFTER links_check_date';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata ADD date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\' AFTER date_start';
                break;
        }
        // 4/13. Fix faqdata_revisions table
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Rename the current faqdata_revisions table
                $query[] = 'EXEC sp_rename \''.SQLPREFIX.'faqdata_revisions\', \''.SQLPREFIX.'faqdata_revisions_PMF16x_old\'';
                // Create the new faqdata_revisions table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata_revisions (
                            id integer NOT NULL,
                            lang varchar(5) NOT NULL,
                            solution_id integer NOT NULL,
                            revision_id integer NOT NULL DEFAULT 0,
                            active char(3) NOT NULL,
                            keywords text NOT NULL,
                            thema text NOT NULL,
                            content text NOT NULL,
                            author varchar(255) NOT NULL,
                            email varchar(255) NOT NULL,
                            comment char(1) default 'y',
                            datum varchar(15) NOT NULL,
                            links_state varchar(7) NOT NULL DEFAULT '',
                            links_check_date integer DEFAULT 0 NOT NULL,
                            date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                            date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                            PRIMARY KEY (id, lang, solution_id, revision_id))";
                // Copy data from the faqdata_revisions_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqdata_revisions
                            (id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum)
                            SELECT id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum
                            FROM '.SQLPREFIX.'faqdata_revisions_PMF16x_old';
                // Drop the faqdata_revisions_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqdata_revisions_PMF16x_old';
                break;
            case 'pgsql':
                // Rename the current faqdata_revisions table
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions RENAME TO '.SQLPREFIX.'faqdata_revisions_PMF16x_old';
                // Create the new faqdata_revisions table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata_revisions (
                            id int4 NOT NULL,
                            lang varchar(5) NOT NULL,
                            solution_id int4 NOT NULL,
                            revision_id int4 NOT NULL DEFAULT 0,
                            active char(3) NOT NULL,
                            keywords text NOT NULL,
                            thema text NOT NULL,
                            content text NOT NULL,
                            author varchar(255) NOT NULL,
                            email varchar(255) NOT NULL,
                            comment char(1) default 'y',
                            datum varchar(15) NOT NULL,
                            links_state varchar(7) NOT NULL,
                            links_check_date int4 DEFAULT 0 NOT NULL,
                            date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                            date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                            PRIMARY KEY (id, lang, solution_id, revision_id))";
                // Copy data from the faqdata_revisions_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqdata_revisions
                            (id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum)
                            SELECT id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum
                            FROM '.SQLPREFIX.'faqdata_revisions_PMF16x_old';
                // Drop the faqdata_revisions_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqdata_revisions_PMF16x_old';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions ADD links_state VARCHAR(7) NOT NULL AFTER datum';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions ADD links_check_date INT(11) NOT NULL DEFAULT 0 AFTER links_state';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions ADD date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\' AFTER links_check_date';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions ADD date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\' AFTER date_start';
                break;
        }
        // 5/13. Fix faqnews table
        $defaultLang = str_replace(array('language_', '.php'), '', $PMF_CONF['language']);
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Rename the current faqnews table
                $query[] = 'EXEC sp_rename \''.SQLPREFIX.'faqnews\', \''.SQLPREFIX.'faqnews_PMF16x_old\'';
                // Create the new faqnews table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqnews (
                            id integer NOT NULL,
                            lang varchar(5) NOT NULL DEFAULT '".$defaultLang."',
                            header varchar(255) NOT NULL,
                            artikel text NOT NULL,
                            datum varchar(14) NOT NULL,
                            author_name  varchar(255) NULL,
                            author_email varchar(255) NULL,
                            active char(1) default 'y',
                            comment char(1) default 'n',
                            date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                            date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                            link varchar(255) NOT NULL,
                            linktitel varchar(255) NOT NULL,
                            target varchar(255) NOT NULL,
                            PRIMARY KEY (id))";
                // Copy data from the faqnews_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqnews
                            (id, header, artikel, datum, link, linktitel, target)
                            SELECT id, header, artikel, datum, link, linktitel, target
                            FROM '.SQLPREFIX.'faqnews_PMF16x_old';
                // Drop the faqnews_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqnews_PMF16x_old';
                break;
            case 'pgsql':
                // Rename the current faqnews table
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews RENAME TO '.SQLPREFIX.'faqnews_PMF16x_old';
                // Create the new faqnews table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqnews (
                            id SERIAL NOT NULL,
                            lang varchar(5) NOT NULL,
                            header varchar(255) NOT NULL,
                            artikel text NOT NULL,
                            datum varchar(14) NOT NULL,
                            author_name  varchar(255) NULL,
                            author_email varchar(255) NULL,
                            active char(1) default 'y',
                            comment char(1) default 'n',
                            date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                            date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                            link varchar(255) NOT NULL,
                            linktitel varchar(255) NOT NULL,
                            target varchar(255) NOT NULL,
                            PRIMARY KEY (id))";
                // Copy data from the faqnews_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqnews
                            (id, header, artikel, datum, link, linktitel, target)
                            SELECT id, header, artikel, datum, link, linktitel, target
                            FROM '.SQLPREFIX.'faqnews_PMF16x_old';
                $query[] = 'UPDATE '.SQLPREFIX.'faqnews SET lang = \''.$defaultLang.'\'';
                // Drop the faqnews_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqnews_PMF16x_old';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ADD lang VARCHAR(5) NULL AFTER id';
                $query[] = 'UPDATE '.SQLPREFIX.'faqnews SET lang = \''.$defaultLang.'\'';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ADD author_name VARCHAR(255) NULL AFTER datum';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ADD author_email VARCHAR(255) NULL AFTER author_name';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ADD active CHAR(1) NOT NULL default \'y\' AFTER author_email';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ADD comment CHAR(1) NOT NULL default \'n\' AFTER active';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ADD date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\' AFTER active';
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ADD date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\' AFTER date_start';
                break;
        }

        // 6/13. Fix faqcomments table
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Rename the current faqcomments table
                $query[] = 'EXEC sp_rename \''.SQLPREFIX.'faqcomments\', \''.SQLPREFIX.'faqcomments_PMF16x_old\'';
                // Create the new faqcomments table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqcomments (
                            id_comment integer NOT NULL,
                            id integer NOT NULL,
                            type varchar(10) NOT NULL DEFAULT 'faq',
                            usr varchar(255) NOT NULL,
                            email varchar(255) NOT NULL,
                            comment text NOT NULL,
                            datum varchar(64) NOT NULL,
                            helped text NOT NULL,
                            PRIMARY KEY (id_comment))";
                // Copy data from the faqcomments_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqcomments
                            (id_comment, id, usr, email, comment, datum, helped)
                            SELECT id_comment, id, usr, email, comment, datum, helped
                            FROM '.SQLPREFIX.'faqcomments_PMF16x_old';
                // Drop the faqcomments_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqcomments_PMF16x_old';
                break;
            case 'pgsql':
                // Rename the current faqcomments table
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcomments RENAME TO '.SQLPREFIX.'faqcomments_PMF16x_old';
                // Create the new faqcomments table
                $query[] = "CREATE TABLE ".SQLPREFIX."faqcomments (
                            id_comment SERIAL NOT NULL,
                            id int4 NOT NULL,
                            type varchar(10) NOT NULL,
                            usr varchar(255) NOT NULL,
                            email varchar(255) NOT NULL,
                            comment text NOT NULL,
                            datum int4 NOT NULL,
                            helped text NOT NULL,
                            PRIMARY KEY (id_comment))";
                // Copy data from the faqcomments_PMF16x_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqcomments
                            (id_comment, id, usr, email, comment, datum, helped)
                            SELECT id_comment, id, usr, email, comment, datum, helped
                            FROM '.SQLPREFIX.'faqcomments_PMF16x_old';
                $query[] = 'UPDATE '.SQLPREFIX.'faqcomments SET type = \'faq\'';
                // Drop the faqcomments_PMF16x_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqcomments_PMF16x_old';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcomments ADD type VARCHAR(10) NOT NULL AFTER id';
                $query[] = 'UPDATE '.SQLPREFIX.'faqcomments SET type = \'faq\'';
                break;
        }

        // 7/13. Rename faquser table for preparing the users migration
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                $query[] = 'EXEC sp_rename \''.SQLPREFIX.'faquser\', \''.SQLPREFIX.'faquser_PMF16x_old\'';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faquser RENAME TO '.SQLPREFIX.'faquser_PMF16x_old';
                break;
        }

        // 8/13. Add the new/changed tables for PMF 2.0.0
        require_once($DB['type'].'.update.sql.php');

        // 9/13. Run the user migration and remove the faquser_PMF16x_old table
        // Populate faquser table
        $now = date("YmdHis", $_SERVER['REQUEST_TIME']);
        // Fix any FK constraints issue: remove these FK cons
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Expected FK in tables: <SQLPREFIX>faqadminlog, <SQLPREFIX>faqchanges
                $queryDropConstraints = "
                    SELECT
                    	'ALTER TABLE ' + t2.Table_Name + '
                    	DROP CONSTRAINT ' + t1.Constraint_Name
                    	AS 'query'
                    FROM
                    	INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS t1
                    INNER JOIN
                    	INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE t2
                    ON
                    		t1.CONSTRAINT_NAME = t2.CONSTRAINT_NAME
                    	AND t2.TABLE_NAME LIKE '".SQLPREFIX."%'";
                $_result = $db->query($queryDropConstraints);
                while ($row = $db->fetch_object($_result)) {
                    $query[] = $row->query;
                }
                break;
        }
        switch($DB["type"]) {
            default:
                // Copy all the users
                $query[] = 'INSERT INTO '.SQLPREFIX.'faquser
                            (user_id, login, account_status, auth_source)
                            SELECT id, name, \'active\', \'local\'
                            FROM '.SQLPREFIX.'faquser_PMF16x_old';
                // Grant the 'admin' user the 'protected' status
                $query[] = 'UPDATE '.SQLPREFIX.'faquser
                            SET account_status = \'protected\'
                            WHERE login = \'admin\'';
                $query[] = 'UPDATE '.SQLPREFIX.'faquser
                            SET session_timestamp = 0';
                $query[] = 'UPDATE '.SQLPREFIX.'faquser
                            SET ip = \'127.0.0.1\'';
                $query[] = 'UPDATE '.SQLPREFIX.'faquser
                            SET member_since = \''.$now.'\'';
                // Evaluate last_login and member_since fields using the faqadminlog table
                $_result = $db->query('SELECT usr FROM '.SQLPREFIX.'faqadminlog GROUP BY usr');
                while ($row = $db->fetch_object($_result)) {
                    $_loginData[$row->usr] = array('last_login' => null, 'member_since' => null);
                }
                foreach ($_loginData as $_key => $_value) {
                    $_result = $db->query('SELECT MIN(time) AS member_since, MAX(time) AS last_login FROM '.SQLPREFIX.'faqadminlog WHERE usr = '.$_key);
                    while ($row = $db->fetch_object($_result)) {
                        $_loginData[$_key]['last_login'] = $row->last_login;
                        $_loginData[$_key]['member_since'] = $row->member_since;
                    }
                }
                $_result = $db->query('SELECT id FROM '.SQLPREFIX.'faquser ORDER BY id');
                while ($row = $db->fetch_object($_result)) {
                    if (isset($_loginData[$row->id])) {
                        $query[] = "UPDATE ".SQLPREFIX."faquser
                                    SET
                                        last_login = '".date('YmdHis', $_loginData[$row->id]['last_login'])."',
                                        member_since = '".date('YmdHis', $_loginData[$row->id]['member_since'])."'
                                    WHERE user_id = ".$row->id;
                    }
                }
                // Populate faquserdata table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faquserdata
                            (user_id, display_name, email)
                            SELECT id, realname, email
                            FROM '.SQLPREFIX.'faquser_PMF16x_old';
                $query[] = 'UPDATE '.SQLPREFIX.'faquserdata
                            SET last_modified = '.$now;
                // Populate faquserlogin table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faquserlogin
                            (login, pass)
                            SELECT name, pass
                            FROM '.SQLPREFIX.'faquser_PMF16x_old';
                // Populate faquser_right table
                $_records = array();
                // Read the data from the current faquser table (PMF 1.6.x)
                $_result = $db->query('SELECT id, rights FROM '.SQLPREFIX.'faquser ORDER BY id');
                while ($row = $db->fetch_object($_result)) {
                    $_records[] = array('id' => $row->id, 'rights' => $row->rights);
                }
                foreach ($_records as $_r) {
                    // PMF 1.6.x: # 23 rights (it ends with changebtrevs)
                    // PMF 2.0.0: # 29 rights
                    // 23-29: addglossary, editglossary, delglossary, changebtrevs, addgroup, editgroup, delgroup:
                    // - id = '1' is supposed to be the 'admin' user
                    // - changebtrevs is the 26th right in PMF 2.0.0, whilst it is the 23rd in PMF 1.6.x
                    $newRights = ('1' == $_r['id']) ? '1111111' : '000'.substr($_r['rights'], 22, 1).'000';
                    $userStringRights = substr($_r['rights'], 0, 22).$newRights;
                    for ($i = 0; $i < 29; $i++) {
                        if ('1' == $userStringRights[$i]) {
                            $query[] = 'INSERT INTO '.SQLPREFIX.'faquser_right
                                        (user_id, right_id)
                                        VALUES ('.$_r['id'].', '.($i+1).')';
                        }
                    }
                }
                // Remove the old faquser table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faquser_PMF16x_old';
                break;
        }
        // 10/13. Move each image filename in each of the faq content, from '/images' to '/images/Image'
        require_once(PMF_ROOT_DIR.'/inc/Linkverifier.php');
        $oLnk     = new PMF_Linkverifier();
        $_records = array();
        // Read the data from the current faqdata table
        $_result = $db->query('SELECT id, revision_id, lang, content FROM '.SQLPREFIX.'faqdata ORDER BY id');
        while ($row = $db->fetch_object($_result)) {
            $_records[] = array('id'          => $row->id,
                                'revision_id' => $row->revision_id,
                                'lang'        => $row->lang,
                                'content'     => $row->content
                                );
        }
        foreach ($_records as $_r) {
            // Extract URLs from content
            $oLnk->resetPool();
            $oLnk->parse_string($_r['content']);
            $fixedContent = $_r['content'];
            // Search for src attributes only
            if (isset($oLnk->urlpool['src'])) {
                foreach ($oLnk->urlpool['src'] as $image) {
                    if (!(strpos($image, '/images/') === false)) {
                        $newImagePath = str_replace('/images/', '/images/Image/', $image);
                        $fixedContent   = str_replace($image, $newImagePath, $fixedContent);
                        if (!in_array($image, $images)) {
                            $images[] = $image;
                        }
                    }
                }
                if ($_r['content'] != $fixedContent) {
                    $fixedContent = $db->escape_string($fixedContent);
                    $query[]  = 'UPDATE '.SQLPREFIX.'faqdata
                                SET content = \''.$fixedContent.'\'
                                WHERE id = '.$_r['id'].' AND revision_id = '.$_r['revision_id'].' AND lang = \''.$_r['lang'].'\'';
                }
            }
        }
        // Read the data from the current faqdata_revisions table
        $_result = $db->query('SELECT id, revision_id, lang, content FROM '.SQLPREFIX.'faqdata_revisions ORDER BY id');
        while ($row = $db->fetch_object($_result)) {
            $_records[] = array('id'          => $row->id,
                                'revision_id' => $row->revision_id,
                                'lang'        => $row->lang,
                                'content'     => $row->content
                                );
        }
        foreach ($_records as $_r) {
            // Extract URLs from content
            $oLnk->resetPool();
            $oLnk->parse_string($_r['content']);
            $fixedContent = $_r['content'];
            // Search for src attributes only
            if (isset($oLnk->urlpool['src'])) {
                foreach ($oLnk->urlpool['src'] as $image) {
                    if (!(strpos($image, '/images/') === false)) {
                        $newImagePath = str_replace('/images/', '/images/Image/', $image);
                        $fixedContent   = str_replace($image, $newImagePath, $fixedContent);
                        if (!in_array($image, $images)) {
                            $images[] = $image;
                        }
                    }
                }
                if ($_r['content'] != $fixedContent) {
                    $fixedContent = $db->escape_string($fixedContent);
                    $query[]  = 'UPDATE '.SQLPREFIX.'faqdata_revisions
                                SET content = \''.$fixedContent.'\'
                                WHERE id = '.$_r['id'].' AND revision_id = '.$_r['revision_id'].' AND lang = \''.$_r['lang'].'\'';
                }
            }
        }
    }

    print '<p class="center">';
    // Perform the queries for updating/migrating the database from 1.x
    if (count($query)) {
        @ob_flush();
        flush();
        $count = 0;
        foreach ($query as $current_query) {
            $result = @$db->query($current_query);
            print "| ";
            if (!$result) {
                print "\n<div class=\"error\">\n";
                print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
                print "<div style=\"text-align: left;\"><p>Query:\n";
                print "<pre>".PMF_htmlentities($current_query)."</pre></p></div>\n";
                print "</div>";
                die();
            }
            wait(25);
            $count++;
            if (!($count % 10)) {
                @ob_flush();
                flush();
            }
        }
        @ob_flush();
        flush();
    }

    // Clear the array with the queries
    unset($query);
    $query = array();

    if (version_compare($version, '2.0.0-alpha', '<')) {
        // 11/13. Move each image file in each of the faq content, from '/images' to '/images/Image'
        foreach ($images as $image) {
            $newImagePath = str_replace('/images/', '/images/Image/', $image);
            @rename(PMF_ROOT_DIR.$image, PMF_ROOT_DIR.$newImagePath);
        }
        // 12/13. Move the PMF configurarion: from inc/config.php to the faqconfig table
        $PMF_CONF['main.administrationMail'] = $PMF_CONF['adminmail'];
        $PMF_CONF['main.bannedIPs'] = $PMF_CONF['bannedIP'];
        $PMF_CONF['main.contactInformations'] = $PMF_CONF['msgContactOwnText'];
        $PMF_CONF['main.currentVersion'] = NEWVERSION;
        $PMF_CONF['main.disableAttachments'] = $PMF_CONF['disatt'];
        $PMF_CONF['main.enableAdminLog'] = $PMF_CONF['enableadminlog'];
        $PMF_CONF['main.enableRewriteRules'] = $PMF_CONF['mod_rewrite'];
        $PMF_CONF['main.enableUserTracking'] = $PMF_CONF['tracking'];
        $PMF_CONF['main.language'] = $PMF_CONF['language'];
        $PMF_CONF['main.languageDetection'] = $PMF_CONF['detection'];
        $PMF_CONF['main.maxAttachmentSize'] = $PMF_CONF['attmax'];
        $PMF_CONF['main.metaDescription'] = $PMF_CONF['metaDescription'];
        $PMF_CONF['main.metaKeywords'] = $PMF_CONF['metaKeywords'];
        $PMF_CONF['main.metaPublisher'] = $PMF_CONF['metaPublisher'];
        $PMF_CONF['main.numberOfRecordsPerPage'] = $PMF_CONF['numRecordsPage'];
        $PMF_CONF['main.numberOfShownNewsEntries'] = $PMF_CONF['numNewsArticles'];
        $PMF_CONF['main.referenceURL'] = PMF_Link::getSystemUri('/install/update.php');
        $PMF_CONF['main.send2friendText'] = $PMF_CONF['send2friend_text'];
        $PMF_CONF['main.titleFAQ'] = $PMF_CONF['title'];

        $PMF_CONF['spam.checkBannedWords'] = $PMF_CONF['spamCheckBannedWords'];
        $PMF_CONF['spam.enableCatpchaCode'] = $PMF_CONF['spamEnableCatpchaCode'];
        $PMF_CONF['spam.enableSafeEmail'] = $PMF_CONF['spamEnableSafeEmail'];

        foreach ($PMF_CONF as $key => $value) {
            $PMF_CONF[$key] = html_entity_decode($value);
            if ('TRUE' == $value) {
                $PMF_CONF[$key] = 'true';
            }
        }
        $oPMFConf = new PMF_Configuration($db);
        $oPMFConf->update($PMF_CONF);
    }

    //
    // UPDATES FROM 2.0-BETA
    //
    if (version_compare($version, '2.0.0-beta', '<')) {
        // 1/4. Fix faqsessions table
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Rename the current faqsessions table
                $query[] = 'EXEC sp_rename \''.SQLPREFIX.'faqsessions\', \''.SQLPREFIX.'faqsessions_PMF200_old\'';
                // Create the new faqsessions table
                $query[] = 'CREATE TABLE '.SQLPREFIX.'faqsessions (
                            sid integer NOT NULL,
                            user_id integer NOT NULL DEFAULT \'-1\',
                            ip varchar(64) NOT NULL,
                            time integer NOT NULL,
                            PRIMARY KEY (sid)
                            )';
                // Copy data from the faqsessions_PMF200_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqsessions
                            (sid, ip, time)
                            SELECT sid, ip, time
                            FROM '.SQLPREFIX.'faqsessions_PMF200_old';
                // Drop the faqsessions_PMF200_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqsessions_PMF200_old';
                break;
            case 'pgsql':
                // Rename the current faqsessions table
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqsessions RENAME TO '.SQLPREFIX.'faqsessions_PMF200_old';
                // Create the new faqsessions table
                $query[] = 'CREATE TABLE '.SQLPREFIX.'faqsessions (
                            sid SERIAL NOT NULL,
                            user_id int4 NOT NULL,
                            ip text NOT NULL,
                            time int4 NOT NULL,
                            PRIMARY KEY (sid)
                            )';
                // Copy data from the faqsessions_PMF200_old table
                $query[] = 'INSERT INTO '.SQLPREFIX.'faqsessions
                            (sid, ip, time)
                            SELECT sid, ip, time
                            FROM '.SQLPREFIX.'faqsessions_PMF200_old';
                $query[] = 'UPDATE '.SQLPREFIX.'faqsessions SET user_id = -1';
                // Drop the faqsessions_PMF200_old table
                $query[] = 'DROP TABLE '.SQLPREFIX.'faqsessions_PMF200_old';
                break;
            default:
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqsessions ADD user_id INT(11) NOT NULL AFTER sid';
                $query[] = 'UPDATE '.SQLPREFIX.'faqsessions SET user_id = -1';
                break;
        }

        // 2/4. Add missing anonymous user account in 2.0.0-alpha
        $anonymous = new PMF_User();
        $anonymous->createUser('anonymous', null, -1);
        $anonymous->setStatus('protected');
        $anonymousData = array(
            'display_name' => 'Anonymous User',
            'email'        => null);
        $anonymous->setUserData($anonymousData);

        // 3/4. Add new config key, 'phpMyFAQToken', into the faqconfig table
        $query[] = 'INSERT INTO '.SQLPREFIX.'faqconfig (config_name, config_value) VALUES (\'phpMyFAQToken\', \''.md5(uniqid(rand())).'\')';

        // 4/4. Fill the new tables for user and group permissions
        $query[] = 'INSERT INTO '.SQLPREFIX.'faqcategory_group (category_id, group_id) SELECT DISTINCT id, -1 FROM '.SQLPREFIX.'faqcategories';
        $query[] = 'INSERT INTO '.SQLPREFIX.'faqcategory_user (category_id, user_id) SELECT DISTINCT id, -1 FROM '.SQLPREFIX.'faqcategories';
        $query[] = 'INSERT INTO '.SQLPREFIX.'faqdata_group (record_id, group_id) SELECT DISTINCT id, -1 FROM '.SQLPREFIX.'faqdata';
        $query[] = 'INSERT INTO '.SQLPREFIX.'faqdata_user (record_id, user_id) SELECT DISTINCT id, -1 FROM '.SQLPREFIX.'faqdata';
    }

    //
    // UPDATES FROM 2.0-BETA2
    //
    if (version_compare($version, '2.0.0-beta2', '<')) {
        // Note: these stage could be avoided if we're coming from v1.6.x-
        // Refactored configuration keys
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.administrationMail' WHERE config_name = 'adminmail'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.bannedIPs' WHERE config_name = 'bannedIP'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.contactInformations' WHERE config_name = 'msgContactOwnText'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.currentVersion' WHERE config_name = 'version'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.disableAttachments' WHERE config_name = 'disatt'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.enableAdminLog' WHERE config_name = 'enableadminlog'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.enableRewriteRules' WHERE config_name = 'mod_rewrite'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.enableUserTracking' WHERE config_name = 'tracking'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.ipCheck' WHERE config_name = 'ipcheck'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.language' WHERE config_name = 'language'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.languageDetection' WHERE config_name = 'detection'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.ldapSupport' WHERE config_name = 'ldap_support'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.maxAttachmentSize' WHERE config_name = 'attmax'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.metaDescription' WHERE config_name = 'metaDescription'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.metaKeywords' WHERE config_name = 'metaKeywords'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.metaPublisher' WHERE config_name = 'metaPublisher'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.numberOfRecordsPerPage' WHERE config_name = 'numRecordsPage'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.numberOfShownNewsEntries' WHERE config_name = 'numNewsArticles'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.permLevel' WHERE config_name = 'permLevel'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.phpMyFAQToken' WHERE config_name = 'phpMyFAQToken'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.referenceURL' WHERE config_name = 'referenceURL'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.send2friendText' WHERE config_name = 'send2friendText'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.titleFAQ' WHERE config_name = 'title'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'main.urlValidateInterval' WHERE config_name = 'URLValidateInterval'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.enableVisibilityQuestions' WHERE config_name = 'enablevisibility'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.numberOfRelatedArticles' WHERE config_name = 'numRelatedArticles'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'spam.checkBannedWords' WHERE config_name = 'spamCheckBannedWords'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'spam.enableCatpchaCode' WHERE config_name = 'spamEnableCatpchaCode'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'spam.enableSafeEmail' WHERE config_name = 'spamEnableSafeEmail'";
        // Added sorting configuration
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig (config_name, config_value) VALUES ('records.orderby', 'id')";
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig (config_name, config_value) VALUES ('records.sortby', 'DESC')";
        // Added default beahviour configuration
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('records.defaultActivation', 'false')";
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('records.defaultAllowComments', 'false')";
    }

    //
    // UPDATES FROM 2.0-RC
    //
    if (version_compare($version, '2.0.0-rc', '<')) {
        switch($DB["type"]) {
            case 'mssql':
            case 'sybase':
                // Fix categories.description
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcategories ALTER COLUMN description VARCHAR(255) NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqcategories', 'description');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcategories WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'description DEFAULT NULL FOR description';
                // Fix faqchanges.what
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqchanges ALTER COLUMN what TEXT NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqchanges', 'what');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqchanges WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'what DEFAULT NULL FOR what';
                // Fix faqcomments.helped
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcomments ALTER COLUMN helped TEXT NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqcomments', 'helped');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqcomments WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'helped DEFAULT NULL FOR helped';
                // Fix faqconfig.config_value
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqconfig ALTER COLUMN config_value VARCHAR(255) NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqconfig', 'config_value');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqconfig WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'config_value DEFAULT NULL FOR config_value';
                // Fix faqdata.keywords
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata ALTER COLUMN keywords TEXT NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqdata', 'keywords');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'keywords DEFAULT NULL FOR keywords';
                // Fix faqdata.content
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata ALTER COLUMN content TEXT NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqdata', 'content');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'content DEFAULT NULL FOR content';
                // Fix faqdata.links_state
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata ALTER COLUMN links_state VARCHAR(7) NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqdata', 'links_state');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'links_state DEFAULT NULL FOR links_state';
                // Fix faqdata_revisions.keywords
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions ALTER COLUMN keywords TEXT NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqdata_revisions', 'keywords');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'keywords_revisions DEFAULT NULL FOR keywords';
                // Fix faqdata_revisions.content
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions ALTER COLUMN content TEXT NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqdata_revisions', 'content');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'content_revisions DEFAULT NULL FOR content';
                // Fix faqdata_revisions.links_state
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions ALTER COLUMN links_state VARCHAR(7) NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqdata_revisions', 'links_state');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqdata_revisions WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'links_state_revisions DEFAULT NULL FOR links_state';
                // Fix faqnews.linktitel
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ALTER COLUMN linktitel VARCHAR(255) NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqnews', 'linktitel');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'linktitel DEFAULT NULL FOR linktitel';
                // Fix faqnews.link
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews ALTER COLUMN link VARCHAR(255) NULL';
                $query[] = writeDropDefaultConstraintQuery(SQLPREFIX.'faqnews', 'link');
                $query[] = 'ALTER TABLE '.SQLPREFIX.'faqnews WITH NOCHECK ADD CONSTRAINT DF_'.SQLPREFIX.'link DEFAULT NULL FOR link';
                break;
            default:
                $query[] = "DROP TABLE ".SQLPREFIX."faqadminsessions";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqcategories CHANGE description description VARCHAR(255) DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqchanges CHANGE what what TEXT DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqcomments CHANGE helped helped TEXT DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqconfig CHANGE config_value config_value VARCHAR(255) DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata CHANGE keywords keywords TEXT DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata CHANGE content content LONGTEXT DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata CHANGE links_state links_state VARCHAR(7) DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions CHANGE keywords keywords TEXT DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions CHANGE content content LONGTEXT DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions CHANGE links_state links_state VARCHAR(7) DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqnews CHANGE linktitel linktitel VARCHAR(255) DEFAULT NULL";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqnews CHANGE link link VARCHAR(255) DEFAULT NULL";
                break;
        }
    }

    //
    // UPDATES FROM 2.0.2
    //
    if (version_compare($version, '2.0.2', '<')) {
        $query[] = 'CREATE INDEX '.SQLPREFIX.'idx_user_time ON '.SQLPREFIX.'faqsessions (user_id, time)';
    }

    //
    // UPDATES FROM 2.5.0-alpha2
    //
    if (version_compare($version, '2.5.0-alpha2', '<')) {
        $query[] = "CREATE TABLE ".SQLPREFIX."faqsearches (
                    id INTEGER NOT NULL ,
                    lang VARCHAR(5) NOT NULL ,
                    searchterm VARCHAR(255) NOT NULL ,
                    searchdate TIMESTAMP,
                    PRIMARY KEY (id, lang))";
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.enableWysiwygEditor', 'true')";
    }
    
    //
    // UPDATES FROM 2.5.0-beta
    //
    if (version_compare($version, '2.5.0-beta', '<')) {
        $query[] = "CREATE TABLE ".SQLPREFIX."faqstopwords (
                    id INTEGER NOT NULL,
                    lang VARCHAR(5) NOT NULL,
                    stopword VARCHAR(64) NOT NULL,
                    PRIMARY KEY (id, lang))";
        
        // Add stopwords list
        require 'stopwords.sql.php';

        switch($DB['type']) {
        	case 'sqlite':
                $query[] = "BEGIN TRANSACTION";
                $query[] = "CREATE TEMPORARY TABLE ".SQLPREFIX."faqdata_temp (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_temp SELECT * FROM ".SQLPREFIX."faqdata";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata";
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    sticky INTEGER NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata SELECT id, lang, solution_id, revision_id, active, NULL,
                    keywords, thema, content, author, email, comment, datum, links_state, links_check_date, date_start,
                    date_end FROM ".SQLPREFIX."faqdata_temp";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_temp";
                $query[] = "COMMIT";
                
                $query[] = "BEGIN TRANSACTION";
                $query[] = "CREATE TEMPORARY TABLE ".SQLPREFIX."faqdata_revisions_temp (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_revisions_temp SELECT * FROM ".SQLPREFIX."faqdata_revisions";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_revisions";
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata_revisions (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    sticky INTEGER NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_revisions SELECT id, lang, solution_id, revision_id, active, NULL,
                    keywords, thema, content, author, email, comment, datum, links_state, links_check_date, date_start,
                    date_end FROM ".SQLPREFIX."faqdata_revisions_temp";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_revisions_temp";
                $query[] = "COMMIT";
        		break;
        	default:
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata ADD sticky INTEGER NOT NULL AFTER active";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions ADD sticky INTEGER NOT NULL AFTER active";
                break;
        }
    }
    
    //
    // UPDATES FROM 2.5.0-RC
    //
    if (version_compare($version, '2.5.0-RC', '<')) {
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (30, 'addtranslation', 'Right to add translation', 1, 1)"; 
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (31, 'edittranslation', 'Right to edit translation', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (32, 'deltranslation', 'Right to delete translation', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 30)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 31)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 32)";
    }

    //
    // UPDATES FROM 2.5.0-RC3
    //
    if(version_compare($version, '2.5.0-RC3', '<')) {
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (33, 'approverec', 'Right to approve records', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 33)";
        
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.attachmentsPath', 'attachments')";
    }
    
    // Perform the queries for updating/migrating the database from 2.x
    if (isset($query)) {
        ob_flush();
        flush();
        $count = 0;
        foreach ($query as $current_query) {
            $result = @$db->query($current_query);
            print '| ';
            if (!$result) {
                print "\n<div class=\"error\">\n";
                print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
                print "<div style=\"text-align: left;\"><p>Query:\n";
                print "<pre>".PMF_htmlentities($current_query)."</pre></p></div>\n";
                print "</div>";
                die();
            }
            wait(25);
            $count++;
            if (!($count % 10)) {
                ob_flush();
                flush();
            }
        }
        ob_flush();
        flush();
    }

    // Clear the array with the queries
    unset($query);
    $query = array();

    // Always the last step: Update version number
    if (version_compare($version, NEWVERSION, '<')) {
        $oPMFConf = PMF_Configuration::getInstance();
        $oPMFConf->update(array('main.currentVersion' => NEWVERSION));
    }

    // optimize tables
    switch ($DB["type"]) {
        case 'mssql':
        case 'sybase':
            // Get all table names
            $db->getTableNames(SQLPREFIX);
            foreach ($db->tableNames as $tableName) {
                $query[] = 'DBCC DBREINDEX ('.$tableName.')';
            }
            break;
        case 'mysql':
        case 'mysqli':
            // Get all table names
            $db->getTableNames(SQLPREFIX);
            foreach ($db->tableNames as $tableName) {
                $query[] = 'OPTIMIZE TABLE '.$tableName;
            }
            break;
        case 'pgsql':
            $query[] = "VACUUM ANALYZE;";
            break;
    }

    // Perform the queries for optimizing the database
    if (isset($query)) {
        foreach ($query as $current_query) {
            $result = $db->query($current_query);
            printf('<span title="%s">|</span> ', $current_query);
            if (!$result) {
                print "\n<div class=\"error\">\n";
                print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
                print "<div style=\"text-align: left;\"><p>Query:\n";
                print "<pre>".PMF_htmlentities($current_query)."</pre></p></div>\n";
                print "</div>";
                die();
            }
            wait(25);
        }
    }

    print "</p>\n";

    print '<p class="center">The database was updated successfully.</p>';
    print '<p class="center"><a href="../index.php">phpMyFAQ</a></p>';
    foreach (glob(PMF_ROOT_DIR.'/inc/*.bak.php') as $filename) {
        if (!@unlink($filename)) {
            print "<p class=\"center\">Please manually remove the backup file '".$filename."'.</p>\n";
        }
    }

    if (version_compare($version, '2.0.0-alpha', '<')) {
        // 13/13. Remove the old config file
        if (@unlink(PMF_ROOT_DIR.'/inc/config.php')) {
            print "<p class=\"center\">The file 'inc/config.php' was deleted automatically.</p>\n";
        } else {
            print "<p class=\"center\">Please delete the file 'inc/config.php' manually.</p>\n";
        }
        @chmod(PMF_ROOT_DIR.'/inc/config.php.original', 0600);
        if (@unlink(PMF_ROOT_DIR.'/inc/config.php.original')) {
            print "<p class=\"center\">The file 'inc/config.php.original' was deleted automatically.</p>\n";
        } else {
            print "<p class=\"center\">Please delete the file 'inc/config.php.original' manually.</p>\n";
        }
    }

    // Remove 'scripts' folder: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/scripts') && is_dir(PMF_ROOT_DIR.'/scripts')) {
        @rmdir(PMF_ROOT_DIR.'/scripts');
    }
    // Remove 'phpmyfaq.spec' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/phpmyfaq.spec')) {
        @unlink(PMF_ROOT_DIR.'/phpmyfaq.spec');
    }
    // Remove 'installer.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/installer.php')) {
        print "<p class=\"center\">The file <em>./install/installer.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/installer.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/update.php')) {
        print "<p class=\"center\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }

    HTMLFooter();
}