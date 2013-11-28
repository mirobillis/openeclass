<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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

/* ===========================================================================
  quotacours.php
  @last update: 31-05-2006 by Pitsiougas Vagelis
  @authors list: Karatzidis Stratos <kstratos@uom.gr>
  Pitsiougas Vagelis <vagpits@uom.gr>
  ==============================================================================
  @Description: Edit quota of a course

  This script allows the administrator to edit the quota of a selected
  course

  The user can : - Edit the quota of a course
  - Return to edit course list

  @Comments: The script is organised in four sections.

  1) Get course quota information
  2) Edit that information
  3) Update course quota
  4) Display all on an HTML page

  ============================================================================== */

$require_departmentmanage_user = true;

require_once '../../include/baseTheme.php';

if (!isset($_GET['c'])) {
    die();
}

require_once 'include/lib/hierarchy.class.php';
require_once 'include/lib/course.class.php';
require_once 'include/lib/user.class.php';
require_once 'hierarchy_validations.php';

$tree = new Hierarchy();
$course = new Course();
$user = new User();

// validate course Id
$cId = course_code_to_id($_GET['c']);
validateCourseNodes($cId, isDepartmentAdmin());

$nameTools = $langQuota;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
$navigation[] = array('url' => 'listcours.php', 'name' => $langListCours);
$navigation[] = array('url' => 'editcours.php?c=' . q($_GET['c']), 'name' => $langCourseEdit);

// Initialize some variables
$quota_info = '';
define('MB', 1048576);

// Update course quota
if (isset($_POST['submit'])) {
    $dq = $_POST['dq'] * MB;
    $vq = $_POST['vq'] * MB;
    $gq = $_POST['gq'] * MB;
    $drq = $_POST['drq'] * MB;
    // Update query
    $sql = db_query("UPDATE course SET doc_quota='$dq', video_quota='$vq', group_quota='$gq', dropbox_quota='$drq'
			WHERE code = " . autoquote($_GET['c']));
    // Some changes occured
    if (mysql_affected_rows() > 0) {
        $tool_content .= "<p>" . $langQuotaSuccess . "</p>";
    }
    // Nothing updated
    else {
        $tool_content .= "<p>" . $langQuotaFail . "</p>";
    }
}
// Display edit form for course quota
else {
    $q = mysql_fetch_array(db_query("SELECT code, title, doc_quota, video_quota, group_quota, dropbox_quota
			FROM course WHERE code = " . autoquote($_GET['c'])));
    $quota_info .= $langTheCourse . " <b>" . q($q['title']) . "</b> " . $langMaxQuota;
    $dq = $q['doc_quota'] / MB;
    $vq = $q['video_quota'] / MB;
    $gq = $q['group_quota'] / MB;
    $drq = $q['dropbox_quota'] / MB;

    $tool_content .= "
	<form action='$_SERVER[SCRIPT_NAME]?c=" . q($_GET['c']) . "' method='post'>
        <fieldset>
            <legend>" . $langQuotaAdmin . "</legend>
            <table width='100%' class='tbl'>
                <tr><td colspan='2' class='sub_title1'>" . $quota_info . "</td></tr>
                <tr><td>$langLegend <b>$langDoc</b>:</td>
                    <td><input type='text' name='dq' value='$dq' size='4' maxlength='4'> Mb.</td></tr>
                <tr><td width='250'>$langLegend <b>$langVideo</b>:</td>
                    <td><input type='text' name='vq' value='$vq' size='4' maxlength='4'> Mb.</td></tr>
                <tr><td>$langLegend <b>$langGroups</b>:</td>
                    <td><input type='text' name='gq' value='$gq' size='4' maxlength='4'> Mb.</td></tr>
                <tr><td>$langLegend <b>$langDropBox</b>:</td>
                    <td><input type='text' name='drq' value='$drq' size='4' maxlength='4'> Mb.</td></tr>
                <tr><td>&nbsp;</td>
                    <td class='right'><input type='submit' name='submit' value='$langModify'></td></tr>
            </table>
	</fieldset></form>\n";
}
// If course selected go back to editcours.php
if (isset($_GET['c'])) {
    $tool_content .= "<p align=\"right\"><a href='editcours.php?c=" . htmlspecialchars($_GET['c']) . "'>" . $langBack . "</a></p>";
}
// Else go back to index.php directly
else {
    $tool_content .= "<p align=\"right\"><a href=\"index.php\">" . $langBackAdmin . "</a></p>";
}

draw($tool_content, 3);
