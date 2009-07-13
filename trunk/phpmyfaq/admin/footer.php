<?php
/**
 * Footer of the admin area
 *
 * @package    phpMyFAQ 
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2003-02-26
 * @version    SVN: $Id$
 * @copyright  2003-2009 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        <div class="right"><?php print $faqconfig->get('main.titleFAQ'); ?> is proudly powered by <strong>phpMyFAQ <?php print $faqconfig->get('main.currentVersion'); ?></strong> | <a href="http://www.phpmyfaq.de/documentation.2.5.en.php" target="_blank">phpMyFAQ documentation</a> | &copy; 2001-2009 <a href="http://www.phpmyfaq.de/" target="_blank">phpMyFAQ Team</a></div>
    </div>

</div>

<?php
if (isset($auth)) {
?>
<iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?php print $LANGCODE; ?>" style="border: none;" width="0" height="0"></iframe>

<?php
    if (isset($auth) && (('takequestion' == $action) || ('editentry'    == $action) || ('editpreview'  == $action) ||
                         ('addnews'      == $action) || ('editnews'     == $action))) {
    
        if ($faqconfig->get('main.enableWysiwygEditor') == true) {
?>
<!-- tinyMCE -->
<script type="text/javascript">
/*<![CDATA[*/ //<!--
tinyMCE.init({
    // General options
    mode : "exact",
    elements : "content",
    theme : "advanced",
    plugins : "safari,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,imagemanager,filemanager,syntaxhl",
    theme_advanced_blockformats : "p,div,h1,h2,h3,h4,h5,h6,blockquote,dt,dd,code,samp",

    // Theme options
    theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
    theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,syntaxhl,|,insertdate,inserttime,preview,|,forecolor,backcolor",
    theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
    theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    
    remove_linebreaks : false, 
    extended_valid_elements : "code",

    // Ajax-based file manager
    file_browser_callback : "ajaxfilemanager",

    // Example content CSS (should be your site CSS)
    content_css : "css/example.css",

    // Drop lists for link/image/media/template dialogs
    template_external_list_url : "js/template_list.js",
    external_link_list_url : "js/link_list.js",
    external_image_list_url : "js/image_list.js",
    media_external_list_url : "js/media_list.js",

    // Replace values for the template plugin
    template_replace_values : {
        username : "<?php print $user->userdata->get('display_name'); ?>",
        user_id  : "<?php print $user->userdata->get('user_id'); ?>"
    }
});

function ajaxfilemanager(field_name, url, type, win)
{
    var ajaxfilemanagerurl = "editor/plugins/ajaxfilemanager/ajaxfilemanager.php";
    switch (type) {
        case "image":
            break;
        case "media":
            break;
        case "flash": 
            break;
        case "file":
            break;
        default:
            return false;
    }
    tinyMCE.activeEditor.windowManager.open({
        url: "editor/plugins/ajaxfilemanager/ajaxfilemanager.php",
        width: 782,
        height: 440,
        inline : "yes",
        close_previous : "no"
    },{
        window : win,
        input : field_name
    });
}

// --> /*]]>*/
</script>
<!-- /tinyMCE -->

<!-- SyntaxHighlighter -->
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shCore.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushBash.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushCpp.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushCSharp.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushCss.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushDelphi.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushDiff.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushGroovy.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushJava.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushJScript.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushPhp.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushPlain.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushPython.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushRuby.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushScala.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushSql.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushVb.js"></script>
<script type="text/javascript" src="../inc/js/syntaxhighlighter/scripts/shBrushXml.js"></script>
<link type="text/css" rel="stylesheet" href="../inc/js/syntaxhighlighter/styles/shCore.css"/>
<link type="text/css" rel="stylesheet" href="../inc/js/syntaxhighlighter/styles/shThemeDefault.css"/>
<script type="text/javascript">
    SyntaxHighlighter.config.clipboardSwf = '../inc/js/syntaxhighlighter/scripts/clipboard.swf';
    SyntaxHighlighter.all();
</script>
<!-- /SyntaxHighlighter -->
<?php
        }
    }
}
?>
</body>
</html>