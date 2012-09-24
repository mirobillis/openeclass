<?php
/* ========================================================================
 * Open eClass 2.6
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2011  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */


// Subsystems
define('MAIN', 0);
define('GROUP', 1);
define('EBOOK', 2);
$can_upload = $is_editor;
if (defined('GROUP_DOCUMENTS')) {
        include '../group/group_functions.php';
	$subsystem = GROUP;
        initialize_group_id();
        initialize_group_info($group_id);        
        $subsystem_id = $group_id;
        $navigation[] = array('url' => $urlAppend . '/modules/group/group.php?course='.$code_cours, 'name' => $langGroups);
        $navigation[] = array('url' => $urlAppend . '/modules/group/group_space.php?course='.$code_cours.'&amp;group_id=' . $group_id, 'name' => q($group_name));
        $groupset = "group_id=$group_id&amp;";
        $base_url = $_SERVER['SCRIPT_NAME'] . '?course=' .$code_cours .'&amp;' . $groupset;
        $group_sql = "course_id = $cours_id AND subsystem = $subsystem AND subsystem_id = $subsystem_id";
        $group_hidden_input = "<input type='hidden' name='group_id' value='$group_id' />";
        $basedir = $webDir . 'courses/' . $currentCourseID . '/group/' . $secret_directory;
	$can_upload = $can_upload || $is_member;
        $nameTools = $langGroupDocumentsLink;
} elseif (defined('EBOOK_DOCUMENTS')) {
        if (isset($_REQUEST['ebook_id'])) {    
            $ebook_id = intval($_REQUEST['ebook_id']);
        }
	$subsystem = EBOOK;
        $subsystem_id = $ebook_id;
        $groupset = "ebook_id=$ebook_id&amp;";
        $base_url = $_SERVER['SCRIPT_NAME'] . '?course=' .$code_cours .'&amp;' . $groupset;
        $group_sql = "course_id = $cours_id AND subsystem = $subsystem AND subsystem_id = $subsystem_id";
        $group_hidden_input = "<input type='hidden' name='ebook_id' value='$ebook_id' />";
        $basedir = $webDir . 'courses/' . $currentCourseID . '/ebook/' . $ebook_id;
} else {
	$subsystem = MAIN;
        $base_url = $_SERVER['SCRIPT_NAME'] . '?course=' .$code_cours .'&amp;';
        $subsystem_id = 'NULL';
        $groupset = '';
        $group_sql = "course_id = $cours_id AND subsystem = $subsystem";
        $group_hidden_input = '';
        $basedir = $webDir . 'courses/' . $currentCourseID . '/document';
        $nameTools = $langDoc;
}       
mysql_select_db($mysqlMainDb);
