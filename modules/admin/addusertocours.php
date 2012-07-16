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

$require_power_user = true;

require_once '../../include/baseTheme.php';

if(!isset($_GET['c'])) { die(); }

$nameTools = $langAdminUsers;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
$navigation[] = array('url' => 'listcours.php', 'name' => $langListCours);
$navigation[] = array('url' => 'editcours.php?c='. q($_GET['c']), 'name' => $langCourseEdit);

// Initialize some variables
$cid = intval(course_code_to_id($_GET['c']));

// Register - Unregister students - professors to course
if (isset($_POST['submit']))  {
        $regstuds = isset($_POST['regstuds'])? array_map('intval', $_POST['regstuds']): array();
        $regprofs = isset($_POST['regprofs'])? array_map('intval', $_POST['regprofs']): array();
        $reglist = implode(', ', array_merge($regstuds, $regprofs));

	// Remove unneded users - guest user (statut == 10) is never removed
        if ($reglist) {
                $reglist = "AND user_id NOT IN ($reglist)";
                db_query("DELETE FROM group_members
                                 WHERE group_id IN (SELECT id FROM `group` WHERE course_id = $cid)
                                       $reglist");
        }
        db_query("DELETE FROM course_user
                         WHERE course_id = $cid AND statut <> 10 $reglist");


        function regusers($cid, $users, $statut)
        {
                foreach ($users as $uid) {
                        db_query("INSERT IGNORE INTO course_user (course_id, user_id, statut, reg_date)
                                  VALUES ($cid, $uid, $statut, CURDATE())");
                }
                $reglist = implode(', ', $users);
                if ($reglist) {
                        db_query("UPDATE course_user SET statut = $statut WHERE user_id IN ($reglist)");
                }
        }
        regusers($cid, $regstuds, 5);
        regusers($cid, $regprofs, 1);

	$tool_content .= "<p>".$langQuickAddDelUserToCoursSuccess."</p>";

}
// Display form to manage users
else {
        load_js('tools.js');

	$tool_content .= "<form action='". q($_SERVER['SCRIPT_NAME'] ."?c=". q($_GET['c'])) ."' method='post'>";
	$tool_content .= "<table class='FormData' width='99%' align='left'><tbody>
                          <tr><th colspan='3'>".$langFormUserManage."</th></tr>
                          <tr><th align=left>".$langListNotRegisteredUsers."<br />
                          <select id='unregusers_box' name='unregusers[]' size='20' multiple class='auth_input'>";

	// Registered users not registered in the selected course
	$sqll= "SELECT DISTINCT u.user_id , u.nom, u.prenom FROM user u
		LEFT JOIN course_user cu ON u.user_id = cu.user_id
                     AND cu.course_id = $cid
		WHERE cu.user_id IS NULL ORDER BY nom";

	$resultAll = db_query($sqll);
	while ($myuser = mysql_fetch_assoc($resultAll)) {
                $tool_content .= "<option value='". q($myuser['user_id']) ."'>".
                        q("$myuser[nom] $myuser[prenom]") . '</option>';
	}

	$tool_content .= "</select></th>
	<td width='3%' class='center' nowrap>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p align='center'><b>$langStudents</b></p>
	<p align='center'><input type='button' onClick=\"move('unregusers_box','regstuds_box')\" value='   >>   ' />
	<input type='button' onClick=\"move('regstuds_box','unregusers_box')\" value='   <<   ' />
	</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p align='center'><b>$langTeachers</b></p>";

	$tool_content .= "<p align='center'><input type='button' onClick=\"move('unregusers_box','regprofs_box')\" value='   >>   ' />
	<input type='button' onClick=\"move('regprofs_box','unregusers_box')\" value='   <<   ' /></p>
	</td>
	<th>".$langListRegisteredStudents."<br />
	<select id='regstuds_box' name='regstuds[]' size='8' multiple class='auth_input'>";

	// Students registered in the selected course
	$resultStud = db_query("SELECT DISTINCT u.user_id , u.nom, u.prenom
				FROM user u, course_user cu
				WHERE cu.course_id = $cid
				AND cu.user_id=u.user_id
				AND cu.statut=5 ORDER BY nom");

	$a=0;
	while ($myStud = mysql_fetch_assoc($resultStud)) {
                $tool_content .= "<option value='". q($myStud['user_id']) ."'>".
                        q("$myStud[nom] $myStud[prenom]") . '</option>';
		$a++;
	}

	$tool_content .= "</select>
		<p>&nbsp;</p>
		$langListRegisteredProfessors<br />
		<select id='regprofs_box' name='regprofs[]' size='8' multiple class='auth_input'>";
	// Professors registered in the selected course
	$resultProf = db_query("SELECT DISTINCT u.user_id , u.nom, u.prenom
				FROM user u, course_user cu
				WHERE cu.course_id = $cid
				AND cu.user_id = u.user_id
				AND cu.statut = 1
				ORDER BY nom, prenom");
	$a=0;
	while ($myProf = mysql_fetch_assoc($resultProf)) {
                $tool_content .= "<option value='". q($myProf['user_id']) ."'>".
                        q("$myProf[nom] $myProf[prenom]") . "</option>";
		$a++;
	}
	$tool_content .= "</select></th></tr><tr><td>&nbsp;</td>
                <td><input type=submit value='$langAcceptChanges' name='submit' onClick=\"selectAll('regstuds_box',true);selectAll('regprofs_box',true)\"></td>
		<td>&nbsp;</td>
		</tr></tbody></table>";
	$tool_content .= "</form>";
}

if (isset($_GET['c'])) {
        // If course selected go back to editcours.php
	$tool_content .= "<p align='right'>
	<a href='editcours.php?c=".q($_GET['c'])."'>".$langBack."</a></p>";
} else {
        // Else go back to index.php directly
	$tool_content .= "<p align='right'><a href='index.php'>".$langBackAdmin."</a></p>";
}

draw($tool_content, 3, null, $head_content);
