<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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

/**
 *
 * @brief display groups
 * @file index.php
 */

$require_current_course = TRUE;
$require_login = TRUE;
$require_help = TRUE;
$helpTopic = 'Group';

require_once '../../include/baseTheme.php';
require_once 'include/course_settings.php';
require_once 'group_functions.php';
require_once 'include/log.php';
/* * ***Required classes for wiki creation*** */
require_once 'modules/wiki/lib/class.wiki.php';
require_once 'modules/wiki/lib/class.wikipage.php';
require_once 'modules/wiki/lib/class.wikistore.php';
/* ***Required classes for forum deletion*** */
require_once 'modules/search/indexer.class.php';
/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
$action->record(MODULE_ID_GROUPS);
/* * *********************************** */

$toolName = $langGroups;
$totalRegistered = 0;
unset($message);
unset($_SESSION['secret_directory']);
unset($_SESSION['forum_id']);

$user_groups = user_group_info($uid, $course_id);

$multi_reg = setting_get(SETTING_GROUP_MULTIPLE_REGISTRATION, $course_id);
$student_desc = setting_get(SETTING_GROUP_STUDENT_DESCRIPTION, $course_id);
//check if social bookmarking is enabled for this course
$social_bookmarks_enabled = setting_get(SETTING_COURSE_SOCIAL_BOOKMARKS_ENABLE, $course_id);
if (isset($_GET['socialview'])) {
    $socialview = true;
    $socialview_param = '&amp;socialview';
} else {
    $socialview = false;
    $socialview_param = '';
}
if (isset($_GET['urlview'])) {
    $urlview = urlencode($_GET['urlview']);
} else {
    $urlview = '';
}

if ($is_editor) {
    if (isset($_GET['deletecategory'])) { // delete group category
        $id = $_GET['id'];
        delete_group_category($id);
        Session::Messages($langGroupCategoryDeleted, 'alert-success');
        redirect_to_home_page("modules/group/index.php");
    } elseif (isset($_GET['deletegroup'])) { // delete group
        $id = $_GET['id'];
        delete_group($id);
        Session::Messages($langGroupDeleted, 'alert-success');
        redirect_to_home_page("modules/group/index.php");
    }
    if (isset($_POST['creation'])) { // groups creation
        $v = new Valitron\Validator($_POST);        
        $v->rule('required', array('group_quantity'));
        $v->rule('numeric', array('group_quantity'));
        $v->rule('min', array('group_quantity'), 1);
        $v->rule('required', array('group_max'));
        $v->rule('numeric', array('group_max'));
        $v->rule('min', array('group_max'), 1);
        $v->labels(array(
            'group_quantity' => "$langTheField $langNewGroups",
            'group_max' => "$langTheField $langNewGroupMembers"
        ));
        
        if($v->validate()) {
            $group_max = $_POST['group_max'];            
            $group_quantity = $_POST['group_quantity'];
            $group_description = isset($_POST['description']) ? $_POST['description'] : '';  
            $private_forum = isset($_POST['private_forum']) ? $_POST['private_forum'] : 0;   
            if (isset($_POST['group_name'])) {
                $group_name = $_POST['group_name'];
            }
                        
            if (isset($_POST['all'])) { // default values if we create multiple groups
                $self_reg = 1;
                $allow_unreg = 0;
            } else {
                if (isset($_POST['self_reg']) and $_POST['self_reg'] == 'on') {
                    $self_reg = 1;
                } else {
                    $self_reg = 0;
                }

                if (isset($_POST['allow_unreg']) and $_POST['allow_unreg'] == 'on') {
                    $allow_unreg = 1;
                } else {
                    $allow_unreg = 0;
                }
            }
            if (isset($_POST['forum']) and $_POST['forum'] == 'on') {
                $has_forum = 1;
            } else {
                $has_forum = 0;
            }

            if (isset($_POST['documents']) and $_POST['documents'] == 'on'){
                $documents = 1;
            } else {
                $documents = 0;
            }

            if (isset($_POST['wiki']) and $_POST['wiki'] == 'on'){
                $wiki = 1;
            } else {
                $wiki = 0;
            }
            $group_num = Database::get()->querySingle("SELECT COUNT(*) AS count FROM `group` WHERE course_id = ?d", $course_id)->count;

            // Create a hidden category for group forums
            $req = Database::get()->querySingle("SELECT id FROM forum_category
                                    WHERE cat_order = -1
                                    AND course_id = ?d", $course_id);
            if ($req) {
                $cat_id = $req->id;
            } else {
                $req2 = Database::get()->query("INSERT INTO forum_category (cat_title, cat_order, course_id)
                                             VALUES (?s, -1, ?d)", $langCatagoryGroup, $course_id);
                $cat_id = $req2->lastInsertID;
            }
            for ($i = 1; $i <= $group_quantity; $i++) {
                if (isset($_POST['all'])) {
                    $g_name = "$langGroup $group_num";
                    $res = Database::get()->query("SELECT id FROM `group` WHERE name = '$langGroup ". $group_num . "'");
                    if ($res) {
                        $group_num++;
                    }
                    $forumname = "$langForumGroup $group_num";                    
                } else {
                    $g_name = $_POST['group_name'];
                    $forumname = "$langForumGroup $g_name";
                }                                
                // Create a unique path to group documents to try (!)
                // avoiding groups entering other groups area
                $secretDirectory = uniqid('');
                make_dir("courses/$course_code/group/$secretDirectory");
                touch("courses/$course_code/group/index.php");
                touch("courses/$course_code/group/$secretDirectory/index.php");
                
                // group forum creation
                $q = Database::get()->query("INSERT INTO forum SET name = '$forumname',
                                                    `desc` = ' ', num_topics = 0, 
                                                    num_posts = 0, last_post_id = 1, 
                                                    cat_id = ?d, course_id = ?d", $cat_id, $course_id);
                $forum_id = $q->lastInsertID;
                
                $id = Database::get()->query("INSERT INTO `group` SET
                                             course_id = ?d,
                                             name = ?s,
                                             description = ?s,
                                             forum_id = ?d,
                                             max_members = ?d,
                                             secret_directory = ?s,
                                             category_id = ?d",
                                    $course_id, $g_name, $group_description, $forum_id, $group_max, $secretDirectory, $_POST['selectcategory'])->lastInsertID;
					
                
                if (isset($_POST['tutor'])) {
                    $user_tutor_id = 0;
                    foreach ($_POST['tutor'] as $user_tutor_id) {
                        Database::get()->query("INSERT INTO group_members SET group_id = ?d, user_id = ?d, is_tutor = 1", $id, $user_tutor_id);
                    }
                }
                if (isset($_POST['ingroup'])) {
                    $new_group_members = count($_POST['ingroup']);                               
                    for ($i = 0; $i < $new_group_members; $i++) {
                       Database::get()->query("INSERT INTO group_members (user_id, group_id)
                                              VALUES (?d, ?d)", $_POST['ingroup'][$i], $id);
                    }               
                }
                
                $query_vars = [
                    $course_id, 
                    $id,
                    $self_reg,
                    $allow_unreg,
                    $has_forum,
                    $private_forum,
                    $documents, 
                    $wiki
                ];
                
                $group_info = Database::get()->query("INSERT INTO `group_properties` SET course_id = ?d,
                                                                    group_id = ?d, self_registration = ?d, 
                                                                    allow_unregister = ?d, 
                                                                    forum = ?d, private_forum = ?d, 
                                                                    documents = ?d, wiki = ?d, 
                                                                    agenda = 0", $query_vars);                
                
                /** ********Create Group Wiki*********** */
                //Set ACL
                $wikiACL = array();
                $wikiACL['course_read'] = true;
                $wikiACL['course_edit'] = false;
                $wikiACL['course_create'] = false;
                $wikiACL['group_read'] = true;
                $wikiACL['group_edit'] = true;
                $wikiACL['group_create'] = true;
                $wikiACL['other_read'] = false;
                $wikiACL['other_edit'] = false;
                $wikiACL['other_create'] = false;

                $wiki_obj = new Wiki();
                $wiki_obj->setTitle($langGroup . " " . $group_num . " - Wiki");
                $wiki_obj->setDescription('');
                $wiki_obj->setACL($wikiACL);
                $wiki_obj->setGroupId($id);
                $wikiId = $wiki_obj->save();

                $mainPageContent = $langWikiMainPageContent;

                $wikiPage = new WikiPage($wikiId);
                $wikiPage->create($uid, '__MainPage__', $mainPageContent, '', date("Y-m-d H:i:s"), true);
                /*             * ************************************ */

                Log::record($course_id, MODULE_ID_GROUPS, LOG_INSERT, array('id' => $id,
                                                                            'name' => "$langGroup $group_num",
                                                                            'max_members' => $group_max,
                                                                            'secret_directory' => $secretDirectory));
            }
            
            Session::Messages($langGroupsAdded2, "alert-success");
            redirect_to_home_page("modules/group/index.php?course=$course_code");

        } else {
            Session::flashPost()->Messages($langFormErrors)->Errors($v->errors());
            redirect_to_home_page("modules/group/group_creation.php?course=$course_code");
        }
    } elseif (isset($_POST['properties'])) {
	
	if (isset($_POST['self_reg']) and $_POST['self_reg'] == 'on') {
		$self_reg = 1;
	}
	else $self_reg = 0;
        
        if (isset($_POST['allow_unreg']) and $_POST['allow_unreg'] == 'on') {
		$allow_unreg = 1;
	}
	else $allow_unreg = 0;
			
	if (isset($_POST['forum']) and $_POST['forum'] == 'on') {
		$has_forum = 1;
	}
	else $has_forum = 0;
	
	if (isset($_POST['documents']) and $_POST['documents'] == 'on'){
		$documents = 1;
	}
	else $documents = 0;
	
	if (isset($_POST['wiki']) and $_POST['wiki'] == 'on'){
		$wiki = 1;
	}
	else $wiki = 0;

	$private_forum = $_POST['private_forum'];
	$group_id = $_POST['group_id'];
	    
	Database::get()->query("UPDATE group_properties SET
                                self_registration = ?d,
                                allow_unregister = ?d,
                                forum = ?d,
                                private_forum = ?d,
                                documents = ?d,
                                wiki = ?d WHERE course_id = ?d AND group_id = ?d",
                     $self_reg, $allow_unreg, $has_forum, $private_forum, $documents, $wiki, $course_id, $group_id);
        $message = $langGroupPropertiesModified;

    } elseif (isset($_POST['submitCategory'])) {
        if (!isset($_POST['token']) || !validate_csrf_token($_POST['token'])) csrf_token_error();
        submit_group_category();
        $messsage = isset($_POST['id']) ? $langCategoryModded : $langCategoryAdded;
        Session::Messages($messsage, 'alert-success');
        redirect_to_home_page("modules/group/index.php");
    } elseif (isset($_REQUEST['delete_all'])) {
        /*         * ************Delete All Group Wikis********** */
        $sql = "SELECT id "
                . "FROM wiki_properties "
                . "WHERE group_id "
                . "IN (SELECT id FROM `group` WHERE course_id = ?d)";

        $results = Database::get()->queryArray($sql, $course_id);
        if (is_array($results)) {
            foreach ($results as $result) {
                $wikiStore = new WikiStore();
                $wikiStore->deleteWiki($result->id);
            }
        }
        /*         * ******************************************** */
        /*         * ************Delete All Group Forums********** */
        $results = Database::get()->queryArray("SELECT `forum_id` FROM `group` WHERE `course_id` = ?d AND `forum_id` <> 0 AND `forum_id` IS NOT NULL", $course_id);
        if (is_array($results)) {

            foreach ($results as $result) {
                $forum_id = $result->forum_id;
                $result2 = Database::get()->queryArray("SELECT id FROM forum_topic WHERE forum_id = ?d", $forum_id);
                foreach ($result2 as $result_row2) {
                    $topic_id = $result_row2->id;
                    Database::get()->query("DELETE FROM forum_post WHERE topic_id = ?d", $topic_id);
                    Indexer::queueAsync(Indexer::REQUEST_REMOVEBYTOPIC, Indexer::RESOURCE_FORUMPOST, $topic_id);
                }
                Database::get()->query("DELETE FROM forum_topic WHERE forum_id = ?d", $forum_id);
                Indexer::queueAsync(Indexer::REQUEST_REMOVEBYFORUM, Indexer::RESOURCE_FORUMTOPIC, $forum_id);
                Database::get()->query("DELETE FROM forum_notify WHERE forum_id = ?d AND course_id = ?d", $forum_id, $course_id);
                Database::get()->query("DELETE FROM forum WHERE id = ?d AND course_id = ?d", $forum_id, $course_id);
                Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_FORUM, $forum_id);
            }
        }
        /*         * ******************************************** */

        Database::get()->query("DELETE FROM group_members WHERE group_id IN (SELECT id FROM `group` WHERE course_id = ?d)", $course_id);
        Database::get()->query("DELETE FROM `group` WHERE course_id = ?d", $course_id);
        Database::get()->query("DELETE FROM document WHERE course_id = ?d AND subsystem = 1", $course_id);
        // Move all groups to garbage collector and re-create an empty work directory
        $groupGarbage = $course_code . '_groups_' . uniqid(20);

        make_dir("courses/garbage");
        @touch("courses/garbage/index.php");
        rename("courses/$course_code/group", "courses/garbage/$groupGarbage");
        make_dir("courses/$course_code/group");
        touch("courses/$course_code/group/index.php");

        $message = $langGroupsDeleted;
    } elseif (isset($_REQUEST['delete'])) {
        $id = intval($_REQUEST['delete']);

        // move group directory to garbage collector
        if (!file_exists("courses/garbage")) {
            make_dir("courses/garbage");
        }
        $groupGarbage = "courses/garbage/{$course_code}_group_{$id}_" . uniqid(20);
        $myDir = Database::get()->querySingle("SELECT secret_directory, forum_id, name FROM `group` WHERE id = ?d", $id);
        if ($myDir and $myDir->secret_directory) {
            $secret_dir = "courses/$course_code/group/" . $myDir->secret_directory;
            if (file_exists($secret_dir)) {
                rename($secret_dir, $groupGarbage);
            }
        }
        // delete group forum
        $result = Database::get()->querySingle("SELECT `forum_id` FROM `group` WHERE `course_id` = ?d AND `id` = ?d AND `forum_id` <> 0 AND `forum_id` IS NOT NULL", $course_id, $id);
        if ($result) {

            $forum_id = $result->forum_id;
            $result2 = Database::get()->queryArray("SELECT id FROM forum_topic WHERE forum_id = ?d", $forum_id);
            foreach ($result2 as $result_row2) {
                $topic_id = $result_row2->id;
                Database::get()->query("DELETE FROM forum_post WHERE topic_id = ?d", $topic_id);
                Indexer::queueAsync(Indexer::REQUEST_REMOVEBYTOPIC, Indexer::RESOURCE_FORUMPOST, $topic_id);
            }
            Database::get()->query("DELETE FROM forum_topic WHERE forum_id = ?d", $forum_id);
            Indexer::queueAsync(Indexer::REQUEST_REMOVEBYFORUM, Indexer::RESOURCE_FORUMTOPIC, $forum_id);
            Database::get()->query("DELETE FROM forum_notify WHERE forum_id = ?d AND course_id = ?d", $forum_id, $course_id);
            Database::get()->query("DELETE FROM forum WHERE id = ?d AND course_id = ?d", $forum_id, $course_id);
            Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_FORUM, $forum_id);
        }
        /*         * *********************************** */

        Database::get()->query("DELETE FROM document WHERE course_id = ?d AND subsystem = 1 AND subsystem_id = ?d", $course_id, $id);
        Database::get()->query("DELETE FROM group_members WHERE group_id = ?d", $id);
		Database::get()->query("DELETE FROM group_properties WHERE group_id = ?d", $id);
        Database::get()->query("DELETE FROM `group` WHERE id = ?d", $id);

        /*         * ********Delete Group Wiki*********** */
        $result = Database::get()->querySingle("SELECT id FROM wiki_properties WHERE group_id = ?d", $id);
        if ($result) {
            $wikiStore = new WikiStore();
            $wikiStore->deleteWiki($result->id);
        }
        /*         * *********************************** */

        Log::record($course_id, MODULE_ID_GROUPS, LOG_DELETE, array('gid' => $id,
            'name' => $myDir? $myDir->name:"[no name]"));

        Session::Messages($langGroupDel, "alert-success");
        redirect_to_home_page("modules/group/index.php?course=$course_code");
    } elseif (isset($_REQUEST['empty'])) {
        Database::get()->query("DELETE FROM group_members
                                   WHERE group_id IN
                                   (SELECT id FROM `group` WHERE course_id = ?d)", $course_id);
        $message = $langGroupsEmptied;
    } elseif (isset($_REQUEST['fill'])) {
        $resGroups = Database::get()->queryArray("SELECT id, max_members -
                                                      (SELECT COUNT(*) FROM group_members WHERE group_members.group_id = id)
                                                  AS remaining
                                              FROM `group` WHERE course_id = ?d ORDER BY id", $course_id);
        foreach ($resGroups as $resGroupsItem) {
            $idGroup = $resGroupsItem->id;
            $places = $resGroupsItem->remaining;
            if ($places > 0) {
                $placeAvailableInGroups[$idGroup] = $places;
            }
        }
        // Course members not registered to any group
        $resUserSansGroupe = Database::get()->queryArray("SELECT u.id, u.surname, u.givenname
                                FROM (user u, course_user cu)
                                WHERE cu.course_id = ?d AND
                                      cu.user_id = u.id AND
                                      cu.status = 5 AND
                                      cu.tutor = 0 AND
                                      u.id NOT IN (SELECT user_id FROM group_members, `group`
                                                                  WHERE `group`.id = group_members.group_id AND
                                                                        `group`.course_id = ?d)
                                GROUP BY u.id
                                ORDER BY u.surname, u.givenname", $course_id, $course_id);        
        
        // gets first group with highest value and adds user id
        foreach ($resUserSansGroupe as $idUser) {
            
            $idGroupChoisi = array_keys($placeAvailableInGroups, max($placeAvailableInGroups));
            $idGroupChoisi = $idGroupChoisi[0];
            if ($placeAvailableInGroups[$idGroupChoisi] > 0){            
                $userOfGroups[$idGroupChoisi][] = $idUser->id;
                $placeAvailableInGroups[$idGroupChoisi] --;
            } else {
                continue;
            }
        }

        // NOW we have $userOfGroups containing new affectation. We must write this in database
        if (isset($userOfGroups) and is_array($userOfGroups)) {
            reset($userOfGroups);
            while (list($idGroup, $users) = each($userOfGroups)) {
                while (list(, $idUser) = each($users)) {
                    Database::get()->query("INSERT INTO group_members SET user_id = ?d, group_id = ?d", $idUser, $idGroup);
                }
            }
        }
        $message = $langGroupFilledGroups;
    }

    // Show DB messages
    if (isset($message)) {
        $tool_content .= "<div class='alert alert-success'>$message</div><br>";
    }

    $groupSelect = Database::get()->queryArray("SELECT id FROM `group` WHERE course_id = ?d ORDER BY id", $course_id);
    $num_of_groups = count($groupSelect);
    $tool_content .= action_bar(array(
                array('title' => $langCreateOneGroup,
                      'url' => "group_creation.php?course=$course_code",
                      'icon' => 'fa-plus-circle',
                      'level' => 'primary-label',
                      'button-class' => 'btn-success'),
                array('title' => $langCreationGroups,
                      'url' => "group_creation.php?course=$course_code&amp;all=1",
                      'icon' => 'fa-plus-circle',
                      'level' => 'primary-label',
                      'button-class' => 'btn-success'),
                array('title' => $langCategoryAdd,
                      'url' => "group_category.php?course=$course_code&amp;addcategory=1",
                      'icon' => 'fa-plus-circle'),
                array('title' => $langFillGroupsAll,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;fill=yes",
                      'icon' => 'fa-pencil',    
                      'show' => $num_of_groups > 0),
                array('title' => $langDeleteGroups,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;delete_all=yes",
                      'icon' => 'fa-times',
                      'confirm' => $langDeleteGroupAllWarn,
                      'show' => $num_of_groups > 0),                
                array('title' => $langEmptyGroups,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;empty=yes",
                      'icon' => 'fa-trash',
                      'class' => 'delete',
                      'confirm' => $langEmptyGroups,
                      'confirm_title' => $langEmptyGroupsAll,
                      'show' => $num_of_groups > 0),
                array('title' => $langGroupProperties,
                      'url' => "group_settings.php?course=$course_code",
                      'icon' => 'fa-gears')));

        $groupSelect = Database::get()->queryArray("SELECT * FROM `group` WHERE course_id = ?d AND (category_id = 0 OR category_id IS NULL) ORDER BY name", $course_id);
        $num_of_groups = count($groupSelect);
	$cat = Database::get()->queryArray("SELECT * FROM `group_category` WHERE course_id = ?d ORDER BY `name`", $course_id);
	$num_of_cat = count($cat);
	$q = count(Database::get()->queryArray("SELECT id FROM `group` WHERE course_id = ?d ORDER BY name", $course_id));
        // groups list
    if ($num_of_groups>0 || $num_of_cat>0) {
        $head_content .= "
                            <script>
                            $(function(){
                                $('#userFeedbacks').on('show.bs.modal', function (event) {
                                  var button = $(event.relatedTarget) // Button that triggered the modal
                                  var content = button.data('content') // Extract info from data-* attributes
                                  var modal = $(this)
                                  modal.find('.modal-body').html(content);
                                })
                            });
                            </script>";
    }
	if ($num_of_groups==0 && $num_of_cat==0) {
            $tool_content .= "<div class='alert alert-warning'>$langNoGroup</div>";
        }
	elseif ($num_of_groups==0 && $num_of_cat>0) {
            $tool_content .= "
            <div class='row'>
                <div class='col-sm-12'>
                <div class='table-responsive'>
                <table class='table-default nocategory-links'>
				<tr class='list-header'><th class='text-left list-header'>$langGroupTeam</th>
                <th class=' option-btn-cell text-center'>" . icon('fa-gears') . "</th>
                </tr>
                <tr><td class='not_visible nocategory-link'> - $langNoGroupInCategory - </td>
                <td></td></tr></table></div></div></div>";
        } elseif ($num_of_groups > 0) {
            $tool_content .= "<div class='table-responsive'>
                <table class='table-default'>
                <tr class='list-header'>
                  <th>$langGroupTeam</th>
                  <th width='250'>$langGroupTutor</th>
                  <th width='50'>$langGroupMembersNum</th>
                  <th width='50'>$langMax</th>
                  <th class='text-center' style='width:45px;'>".icon('fa-gears', $langActions)."</th>
                </tr>";

        foreach ($groupSelect as $group) {
            initialize_group_info($group->id);
            $tool_content .= "<tr>";
            $tool_content .= "<td><a href='group_space.php?course=$course_code&amp;group_id=$group->id'>" . q($group_name) . "</a>
                    <br><p>$group_description</p>";
            if ($user_group_description && $student_desc) {
                $tool_content .= "<small><a href = 'javascirpt:void(0);' data-toggle = 'modal' data-content='".q($user_group_description)."' data-target = '#userFeedbacks' ><span class='fa fa-comments' ></span > $langCommentsUser</a ></small>";
            }
            $tool_content .= "</td><td class='center'>";
            foreach ($tutors as $t) {
                $tool_content .= display_user($t->user_id) . "<br>";
            }
            $tool_content .= "</td><td class='text-center'>$member_count</td>";
            if ($max_members == 0) {
                $tool_content .= "<td>-</td>";
            } else {
                $tool_content .= "<td class='text-center'>$max_members</td>";
            }
            $tool_content .= "<td class='option-btn-cell'>" .
                    action_button(array(
                        array('title' => $langConfig,
                            'url' => "group_properties.php?course=$course_code&amp;group_id=$group->id",
                            'icon' => 'fa-gear'),
                        array('title' => $langEditChange,
                            'url' => "group_edit.php?course=$course_code&amp;category=$group->category_id&amp;group_id=$group->id",
                            'icon' => 'fa-edit'),                        
                        array('title' => $langDelete,
                            'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;delete=$group->id",
                            'icon' => 'fa-times',
                            'class' => 'delete',
                            'confirm' => $langConfirmDelete))) .
                        "</td></tr>";
            $totalRegistered += $member_count;            
        }
        $tool_content .= "</table></div><br>";
        $tool_content .= "
            <div class='modal fade' id='userFeedbacks' tabindex='-1' role='dialog' aria-labelledby='myModalLabel'>
              <div class='modal-dialog' role='document'>
                <div class='modal-content'>
                  <div class='modal-header'>
                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                    <h4 class='modal-title' id='myModalLabel'>$langCommentsUser</h4>
                  </div>
                  <div class='modal-body'>
                  </div>
                </div>
              </div>
            </div>
        ";
    }
} else {
    // Begin student view
    $q = Database::get()->queryArray("SELECT id FROM `group` WHERE course_id = ?d AND (category_id = 0 OR category_id IS NULL) ORDER BY name", $course_id);
    if (count($q) == 0) {
        $tool_content .= "
            <div class='row'>
                <div class='col-sm-12'>
                <div class='table-responsive'>
                <table class='table-default nocategory-links'>
				<tr class='list-header'><th class='text-left list-header'>$langGroupTeam</th>
                </tr>
                <tr><td class='not_visible nocategory-link'> - $langNoGroupInCategory - </td>
                </tr></table></div></div></div>";
    } else {        
        $tool_content .= "<div class='table-responsive'>
            <table class='table-default'>
                <tr class='list-header'>
                  <th class='text-left'>$langGroupTeam</th>
                  <th width='250'>$langGroupTutor</th>
                  <th width='50'>$langGroupMembersNum</th>
                  <th width='50'>$langMax</th>
                  <th class='text-center' style='width:45px;'>".icon('fa-gears', $langActions)."</th>
                </tr>";        
        foreach ($q as $row) {
            $group_id = $row->id;
            
            initialize_group_info($group_id);
            
            $tool_content .= "<td class='text-left'>";
            // Allow student to enter group only if he's a member
            if ($is_member or $is_tutor) {
                $tool_content .= "<a href='group_space.php?course=$course_code&amp;group_id=$group_id'>" . q($group_name) .
                        "</a> <span style='color:#900; weight:bold;'>($langMyGroup)</span>";
            } else {
                $tool_content .= q($group_name);
            }
            $tool_content .= "<br><em>$group_description</em><br>";
            if ($student_desc) {
                if ($user_group_description) {
                    $tool_content .= "<br><span class='small'><i>$user_group_description</i></span>&nbsp;&nbsp;" .
                            icon('fa-edit', $langModify, "group_description.php?course=$course_code&amp;group_id=$group_id") . "&nbsp;" .
                            icon('fa-times', $langDelete, "group_description.php?course=$course_code&amp;group_id=$group_id&amp;delete=true", 'onClick="return confirmation();"');
                } elseif ($is_member) {
                    $tool_content .= "<br><a href='group_description.php?course=$course_code&amp;group_id=$group_id'><i>$langAddDescription</i></a>";
                }
            }
            $tool_content .= "</td>";
            $tool_content .= "<td class='text-center'>";
            foreach ($tutors as $t) {
                $tool_content .= display_user($t->user_id) . "<br>";
            }
            $tool_content .= "</td>";           
            $tool_content .= "<td class='text-center'>$member_count</td><td class='text-center'>" .
                    ($max_members ? $max_members : '-') . "</td>";
            // If self-registration and multi registration allowed by admin and group is not full
            $tool_content .= "<td class='text-center'>";            
            if ($uid and $self_reg and (!$user_groups or $multi_reg) and ! $is_member and ( !$max_members or $member_count < $max_members)) {
                $tool_content .= icon('fa-sign-in', $langRegister, "group_space.php?course=$course_code&amp;selfReg=1&amp;group_id=$group_id");
            } elseif (!$self_reg) {
                $tool_content .= ' - ';
            } else {
                if (!$allow_unreg) {
                    $tool_content .= ' - ';
                } else {
                   $tool_content .= icon('fa-sign-out', $langUnRegister, "group_space.php?course=$course_code&amp;selfUnReg=1&amp;group_id=$group_id", " style='color:#d9534f;'");
                }
            }
            $tool_content .= "</td></tr>";
            $totalRegistered += $member_count;
        }
        $tool_content .= "</table></div>";
    }
}
            
    if (!in_array($action, array('addcategory', 'editcategory'))) {
	$numberofzerocategory = count(Database::get()->queryArray("SELECT * FROM `group` WHERE course_id = ?d AND (category_id = 0 OR category_id IS NULL)", $course_id));
	$cat = Database::get()->queryArray("SELECT * FROM `group_category` WHERE course_id = ?d ORDER BY `name`", $course_id);
	$aantalcategories = count($cat);
	$tool_content .= "<br><br><div class='row'>
            <div class='col-sm-12'>
            <div class='margin-bottom-thin' style='font-weight: bold;'>";
        if ($aantalcategories > 0) {
            $tool_content .= "$langCategorisedGroups&nbsp;";
            if (isset($urlview) and abs($urlview) == 0) {
                $tool_content .= "&nbsp;&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=" . str_repeat('1', $aantalcategories) . $socialview_param . "'>" . icon('fa-plus-square', $showall)."</a>";
            } else {
                $tool_content .= "&nbsp;&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=" . str_repeat('0', $aantalcategories) . $socialview_param . "'>" .icon('fa-minus-square', $shownone)."</a>";
            }
        }
        $tool_content .= "</div>
            <div class='table-responsive'>
            <table class='table-default category-links'>";

	if ($urlview === '') {
            $urlview = str_repeat('0', $aantalcategories);
        }
        $i = 0;        
        foreach ($cat as $myrow) {
            if (empty($urlview)) {
                // No $view set in the url, thus for each category link it should be all zeros except it's own
                $view = makedefaultviewcode($i);
            } else {
                $view = $urlview;
                $view[$i] = '1';
            }
            // if the $urlview has a 1 for this category, this means it is expanded and should be displayed as a
            // - instead of a +, the category is no longer clickable and all the links of this category are displayed
            $description = standard_text_escape($myrow->description);
            if ((isset($urlview[$i]) and $urlview[$i] == '1')) {
                $newurlview = $urlview;
                $newurlview[$i] = '0';
                $tool_content .= "<tr class='link-subcategory-title'>
                                    <th class = 'text-left category-link' colspan='4'>
                                        <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=$newurlview$socialview_param' class='open-category'>".icon('fa-minus-square-o', $shownone)."&nbsp;&nbsp;". q($myrow->name) . "</a>";
                if (!empty($description)) {
                    $tool_content .= "<br><span class='link-description'>$description</span></th>";
                } else {
                    $tool_content .= "</th>";
                }

                if ($is_editor) {
                    $tool_content .= "<td class='option-btn-cell'>";
                    showgroupcategoryadmintools($myrow->id);
                    $tool_content .= "</td>";
                } else {
                    $tool_content .= "<td>&nbsp;</td>";
                }

                $tool_content .= "</tr>";
                // display category groups
                showgroupsofcategory($myrow->id);
                
                if ($groups_num == 1) {
                    $tool_content .= "<tr><td class='text-left not_visible nocategory-link'> - $langNoGroupInCategory - </td>" .
                        ($is_editor? '<td></td>': '') . "<tr>";
                }

            } else {
                $tool_content .= "
                        <tr class='link-subcategory-title'>
                            <th class = 'text-left category-link' colspan='4'>&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;urlview=";
                $tool_content .= is_array($view) ? implode('', $view) : $view;
                $tool_content .= "' class='open-category'>".icon('fa-plus-square', $showall)
                    . "&nbsp;&nbsp;" . q($myrow->name) . "</a>";
                $description = standard_text_escape($myrow->description);
                if (!empty($description)) {
                    $tool_content .= "<br><span class='link-description'>$description</span</th>";
                } else {
                    $tool_content .= "</th>";
                }

                if ($is_editor) {
                    $tool_content .= "<td class='option-btn-cell'>";
                    showgroupcategoryadmintools($myrow->id);
                    $tool_content .= "</td>";
                }
                $tool_content .= "</tr>";
            }
            $i++;
        }
    $tool_content .= "</table></div></div></div>";
    add_units_navigation(TRUE);
}
draw($tool_content, 2, null, $head_content);
