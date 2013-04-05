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


/*===========================================================================
	search_incourse.php
	@version $Id$
	@authors list: Agorastos Sakis <th_agorastos@hotmail.com>
==============================================================================
        @Description: Search function that searches data within a course.
        Requires $dbname to point to the course DB

   	This is an example of the MySQL queries used for searching:
   	SELECT * FROM articles WHERE MATCH (title,body,more_fields) AGAINST ('database') OR ('Security') AND ('lala')
==============================================================================*/


$require_current_course = TRUE;
$guest_allowed = true;
require_once '../../include/baseTheme.php';
require_once 'include/lib/textLib.inc.php';
require_once 'indexer.class.php';
require_once 'announcementindexer.class.php';
require_once 'agendaindexer.class.php';
require_once 'linkindexer.class.php';
require_once 'videoindexer.class.php';
require_once 'videolinkindexer.class.php';
require_once 'exerciseindexer.class.php';
require_once 'forumindexer.class.php';
require_once 'forumtopicindexer.class.php';
require_once 'forumpostindexer.class.php';

$nameTools = $langSearch;

if (!get_config('enable_search')) {
        $tool_content .= "<div class='info'>$langSearchDisabled</div>";
        draw($tool_content, 2);
        exit;
}

$found = false;
register_posted_variables(array('announcements' => true,
				'agenda' => true,
			  	'course_units' => true,
				'documents' => true,
				'exercises' => true,
				'forums' => true,
				'links' => true,
				'video' => true),
			        'all');

if (isset($_GET['all'])) {
	$all = intval($_GET['all']);
	$announcements = $agenda = $course_units = $documents = $exercises = $forums = $links = $video = 1;
}

if(isset($_REQUEST['search_terms'])) {
	$search_terms = mysql_real_escape_string($_REQUEST['search_terms']);
	$query = " AGAINST ('".$search_terms."";
	$query .= "' IN BOOLEAN MODE)";
}

if(empty($search_terms)) {

	// display form
	$tool_content .= "
	    <form method='post' action='$_SERVER[SCRIPT_NAME]'>
	    <fieldset>
	    <legend>$langSearchCriteria</legend>
	    <table width='100%' class='tbl'>
	    <tr>
	      <th class='left' width='120'>$langOR</th>
	      <td colspan='2'><input name='search_terms' type='text' size='80'/></td>
	    </tr>
	    <tr>
	      <th width='30%' class='left' valign='top' rowspan='4'>$langSearchIn</th>
	      <td width='35%'><input type='checkbox' name='announcements' checked='checked' />$langAnnouncements</td>
	      <td width='35%'><input type='checkbox' name='agenda' checked='checked' />$langAgenda</td>
	    </tr>
	    <tr>
	      <td><input type='checkbox' name='course_units' checked='checked' />$langCourseUnits</td>
	      <td><input type='checkbox' name='documents' checked='checked' />$langDoc</td>
	    </tr>
	    <tr>
	      <td><input type='checkbox' name='forums' checked='checked' />$langForums</td>
	      <td><input type='checkbox' name='exercises' checked='checked' />$langExercices</td>
	    </tr>
	   <tr>
	      <td><input type='checkbox' name='video' checked='checked' />$langVideo</td>
	      <td><input type='checkbox' name='links' checked='checked' />$langLinks</td>
	   </tr>
	   <tr>
	     <th>&nbsp;</th>
	     <td colspan='2' class='right'><input type='submit' name='submit' value='$langDoSearch' /></td>
	   </tr>
	   </table>
	   </fieldset>
	   </form>";
} else {
    // ResourceIndexers require course_id inside the input data array (POST, but we do not want to pass it through the form)
    $_POST['course_id'] = $course_id;
    $idx = new Indexer();
    
    $tool_content .= "
        <div id=\"operations_container\">
	  <ul id='opslist'>
	    <li><a href='". $_SERVER['SCRIPT_NAME'] ."'>$langNewSearch</a></li>
	  </ul>
	</div>
        <p class='sub_title1'>$langResults</p>";

    // search in announcements
    if ($announcements) {
        $announceHits = $idx->searchRaw(AnnouncementIndexer::buildQuery($_POST));

        if (count($announceHits) > 0) {
            $tool_content .= "
              <script type='text/javascript' src='../auth/sorttable.js'></script>
              <table width='99%' class='sortable' id='t1' align='left'>
              <tr>
                <th colspan='2'>$langAnnouncements:</th>
              </tr>";

            $numLine = 0;
            foreach ($announceHits as $annHit) {
                $res = db_query("SELECT title, content, date FROM announcement WHERE id = " . intval($annHit->pkid));
                $announce = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "<tr class='$class'>
                                  <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                                  <td><b><a href='" . $annHit->url . "'>" . q($announce['title']) . "</a></b>&nbsp;&nbsp;
                                  <small>("
                        . nice_format(claro_format_locale_date($dateFormatLong, strtotime($announce['date']))) . "
                                  )</small><br />" . $announce['content'] . "</td></tr>";
                $numLine++;
            }

            $tool_content .= "</table>";
            $found = true;
        }
    }
    
    // search in agenda
    if ($agenda) {
        $agendaHits = $idx->searchRaw(AgendaIndexer::buildQuery($_POST));

        if (count($agendaHits) > 0) {
            $tool_content .= "
                  <script type='text/javascript' src='../auth/sorttable.js'></script>
                  <table width='99%' class='sortable' id='t2' align='left'>
		  <tr>
		    <th colspan='2' class=\"left\">$langAgenda:</th>
                  </tr>";

            $numLine = 0;
            foreach ($agendaHits as $agHit) {
                $res = db_query("SELECT title, content, day, hour, lasting FROM agenda WHERE id = " . intval($agHit->pkid));
                $agenda = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "
                  <tr clas='$class'>
                    <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                    <td>";
                $message = $langUnknown;
                if ($agenda["lasting"] != "") {
                    if ($agenda["lasting"] == 1)
                        $message = $langHour;
                    else
                        $message = $langHours;
                }
                $tool_content .= "<span class=day>" .
                        ucfirst(claro_format_locale_date($dateFormatLong, strtotime($agenda["day"]))) .
                        "</span> ($langHour: " . ucfirst(date("H:i", strtotime($agenda["hour"]))) . ")<br />"
                        . q($agenda['title']) . " (" . $langDuration . ": " . q($agenda["lasting"]) . " $message) " . $agenda['content'] . "
                    </td>
                  </tr>";

                $numLine++;
            }
            
            $tool_content .= "</table>";
            $found = true;
        }
    }
        
	// search in documents
	if ($documents) {
		$myquery = "SELECT * FROM document
				WHERE course_id = $course_id
				AND subsystem = 0
				AND visible = 1
				AND MATCH (filename, comment, title, creator, subject, description, author, language)".$query;
		$result = db_query($myquery);
		if(mysql_num_rows($result) > 0) {
			$tool_content .= "
                        <script type='text/javascript' src='../auth/sorttable.js'></script>
                        <table width='99%' class='sortable' id='t3' align='left'>
                        <tr>
                        <th colspan='2' class='left'>$langDoc:</th>
                        </tr>";
                        $numLine = 0;
                        while($res = mysql_fetch_array($result)) {
                                if ($numLine%2 == 0) {
                                        $class_view = 'class="even"';
                                } else {
                                        $class_view = 'class="odd"';
                                }
                                $tool_content .= "
                                <tr $class_view>
                                <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                                <td>";
                                if (empty($res['comment']))  {
                                        $add_comment = "";
                                } else {
                                        $add_comment = "<br /><span class='smaller'> ($res[comment])</span>";
                                }
                                $link_document = "{$urlServer}modules/document/index.php?action2=download&amp;id=$res[path]";
                                $tool_content .= "<a href='$link_document'>".$res['filename']."</a>$add_comment
                                </td>
                                </tr>";
                                $numLine++;
                        }
                        $tool_content .= "</table>";
                        $found = true;
		}
	}

    // search in exercises
    if ($exercises) {
        $exerciseHits = $idx->searchRaw(ExerciseIndexer::buildQuery($_POST));

        if (count($exerciseHits) > 0) {
            $tool_content .= "
                <script type='text/javascript' src='../auth/sorttable.js'></script>
                <table width=\"99%\" class='sortable' id='t4' align='left'>
		<tr>
		  <th colspan='2' class='left'>$langExercices:</th>
                </tr>";

            $numLine = 0;
            foreach ($exerciseHits as $exerciseHit) {
                $res = db_query("SELECT title, description FROM exercise WHERE id = " . intval($exerciseHit->pkid));
                $exercise = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "
                        <tr class='$class'>
                        <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                        <td>";
                $desc_text = (empty($exercise['description'])) ? "" : "<br /> <span class='smaller'>" . $exercise['description'] . "</span>";
                $tool_content .= "<a href='" . $exerciseHit->url . "'>" . $exercise['title'] . "</a>$desc_text </td></tr>";

                $numLine++;
            }

            $tool_content .= "</table>";
            $found = true;
        }
    }

    // search in forums
    if ($forums) {
        $forumHits = $idx->searchRaw(ForumIndexer::buildQuery($_POST));
        $forumTopicHits = $idx->searchRaw(ForumTopicIndexer::buildQuery($_POST));
        $forumPostHits = $idx->searchRaw(ForumPostIndexer::buildQuery($_POST));

        if (count($forumHits) > 0) {
            $tool_content .= "
                        <script type='text/javascript' src='../auth/sorttable.js'></script>
                        <table width='99%' class='sortable' id='t5' align='left'>
                        <tr>
                        <th colspan='2' class=\"left\">$langForum ($langCategories):</th>
                        </tr>";

            $numLine = 0;
            foreach ($forumHits as $forumHit) {
                $res = db_query("SELECT name, `desc` FROM forum WHERE id = " . intval($forumHit->pkid));
                $forum = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "
                        <tr class='$class'>
                        <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                        <td>";
                $desc_text = (empty($forum['desc'])) ? "" : "<br /><span class='smaller'>(" . $forum['desc'] . ")</span>";
                $tool_content .= "<a href='" . $forumHit->url . "'>" . $forum['name'] . "</a> $desc_text </td></tr>";

                $numLine++;
            }

            $tool_content .= "</table>";
            $found = true;
        }

        if (count($forumTopicHits) > 0) {
            $tool_content .= "
                <script type='text/javascript' src='../auth/sorttable.js'></script>
                <table width='99%' class='sortable' id='t6' align='left'>
		<tr>
		  <th colspan='2' class=\"left\">$langForum ($langSubjects - $langMessages):</th>
                </tr>";

            $numLine = 0;
            foreach ($forumTopicHits as $forumTopicHit) {
                $res = db_query("SELECT title FROM forum_topic WHERE id = " . intval($forumTopicHit->pkid));
                $ftopic = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "
                  <tr class='$class'>
                    <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                    <td>";
                $tool_content .= "<strong>$langSubject</strong>: <a href='" . $forumTopicHit->url . "'>" . $ftopic['title'] . "</a>";
                if (count($forumPostHits) > 0) {
                    foreach ($forumPostHits as $forumPostHit) {
                        $res2 = db_query("SELECT post_text FROM forum_post WHERE id = " . intval($forumPostHit->pkid));
                        $fpost = mysql_fetch_assoc($res2);

                        $tool_content .= "<br /><strong>$langMessage</strong> <a href='" . $forumPostHit->url . "'>" . $fpost['post_text'] . "</a>";
                    }
                }
                $tool_content .= "</td></tr>";

                $numLine++;
            }

            $tool_content .= "</table>";
            $found = true;
        }
    }
        
    // search in links
    if ($links) {
        $linkHits = $idx->searchRaw(LinkIndexer::buildQuery($_POST));

        if (count($linkHits) > 0) {
            $tool_content .= "
                <script type='text/javascript' src='../auth/sorttable.js'></script>
                <table width='99%' class='sortable' id='t7' align='left'>
		<tr>
                  <th colspan='2' class='left'>$langLinks:</th>
                </tr>";

            $numLine = 0;
            foreach ($linkHits as $linkHit) {
                $res = db_query("SELECT title, description FROM link WHERE id = " . intval($linkHit->pkid));
                $link = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "
                  <tr class='$class'>
                    <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                    <td>";
                $desc_text = (empty($link['description'])) ? "" : "<span class='smaller'>" . $link['description'] . "</span>";
                $tool_content .= "<a href='" . $linkHit->url . "' target=_blank> " . $link['title'] . "</a> $desc_text </td></tr>";
                
                $numLine++;
            }
            
            $tool_content .= "</table>";
            $found = true;
        }
    }

    // search in video and videolinks
    if ($video) {
        $videoHits = $idx->searchRaw(VideoIndexer::buildQuery($_POST));
        $vlinkHits = $idx->searchRaw(VideolinkIndexer::buildQuery($_POST));

        if (count($videoHits) > 0) {
            $tool_content .= "
                <script type='text/javascript' src='../auth/sorttable.js'></script>
                <table width='99%' class='sortable'  id='t8' align='left'>
		<tr>
                  <th colspan='2' class='left'>$langVideo:</th>
                </tr>";

            $numLine = 0;
            foreach ($videoHits as $videoHit) {
                $res = db_query("SELECT title, description FROM video WHERE id = " . intval($videoHit->pkid));
                $video = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "
                  <tr class='$class'>
                    <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                    <td>";
                $desc_text = (empty($video['description'])) ? "" : "<span class='smaller'>(" . $video['description'] . ")</span>";
                $tool_content .= "<a href='" . $videoHit->url . "' target=_blank>" . $video['title'] . "</a> $desc_text </td></tr>";

                $numLine++;
            }

            $tool_content .= "</table>";
            $found = true;
        }

        if (count($vlinkHits) > 0) {
            $tool_content .= "
                        <script type='text/javascript' src='../auth/sorttable.js'></script>
                        <table width='99%' class='sortable' id='t9' align='left'>
                        <tr>
                        <th colspan='2' class='left'>$langLinks:</th>
                        </tr>";

            $numLine = 0;
            foreach ($vlinkHits as $vlinkHit) {
                $res = db_query("SELECT title, description FROM videolinks WHERE id = " . intval($vlinkHit->pkid));
                $vlink = mysql_fetch_assoc($res);

                $class = ($numLine % 2) ? 'odd' : 'even';
                $tool_content .= "<tr $class_view>
                        <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                        <td>";
                $desc_text = (empty($vlink['description'])) ? "" : "<span class='smaller'>(" . $vlink['description'] . ")</span>";
                $tool_content .= "<a href='" . $vlinkHit->url . "' target=_blank>" . $vlink['title'] . "</a><br /> $desc_text </td></tr>";

                $numLine++;
            }

            $tool_content .= "</table>\n\n\n";
            $found = true;
        }
    }

	// search in cours_units and unit_resources
	if ($course_units)
	{
		$myquery = "SELECT id, title, comments FROM course_units
				WHERE course_id = $course_id
				AND visible = 1
				AND MATCH (title, comments)".$query;
		$result = db_query($myquery);
		if(mysql_num_rows($result) > 0) {
			$tool_content .= "
			<script type='text/javascript' src='../auth/sorttable.js'></script>
			<table width='99%' class='sortable' id='t11' align='left'>
			<tr>
			  <th colspan='2' class='left'>$langCourseUnits:</th>
			</tr>";
			$numLine = 0;
			while($res = mysql_fetch_array($result))
			{
                                if ($numLine%2 == 0) {
                                        $class_view = 'class="even"';
                                } else {
                                        $class_view = 'class="odd"';
                                }
                                $tool_content .= "
                                <tr $class_view>
                                <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                                <td>";
                                if (empty($res['comments'])) {
                                        $comments_text = "";
                                } else {
                                        $comments_text = " $res[comments]";
                                }
                                $link = "${urlServer}modules/units/?id=$res[id]";
                                $tool_content .= "<a href='$link'>".$res['title']."</a> $comments_text</td></tr>";
                                $numLine++;
			}
			$tool_content .= "</table>";
			$found = true;
		}
		$myquery2 = $myquery2 = "SELECT unit_resources.unit_id AS id,
				unit_resources.title AS title,
				unit_resources.comments AS comments
			FROM unit_resources, course_units
				WHERE unit_resources.unit_id = course_units.id
				AND course_units.course_id = $course_id
				AND course_units.visible = 1
			AND MATCH(unit_resources.title, unit_resources.comments)".$query;
		$result2 = db_query($myquery2);
		if (mysql_num_rows($result2) > 0) {
                        $tool_content .= "
			<script type='text/javascript' src='../auth/sorttable.js'></script>
			<table width='99%' class='sortable' id='t11' align='left'>
			<tr>
			  <th colspan='2' class='left'>$langCourseUnits:</th>
			</tr>";
			$numLine = 0;
			while ($res2 = mysql_fetch_array($result2)) {
				if ($numLine%2 == 0) {
				 $class_view = 'class="even"';
			      } else {
				 $class_view = 'class="odd"';
			      }
			      $tool_content .= "<tr $class_view>
                                <td width='1' valign='top'><img style='padding-top:3px;' src='$themeimg/arrow.png' title='bullet' /></td>
                                <td>";
				if (empty($res2['comments'])) {
					$comments_text = "";
				} else {
					$comments_text = "<span class='smaller'> $res2[comments]</span>";
				}
				$unitlink = "${urlServer}modules/units/?id=$res2[id]";
				$tool_content .= "$res2[title]<a href='$unitlink'>".$comments_text."</a></td></tr>";
				$numLine++;
			}
			$tool_content .= "</table>";
			$found = true;
		}
	}
        
    // else ... no results found
    if ($found == false) {
        $tool_content .= "<p class='alert1'>$langNoResult</p>";
    }
} // end of search
draw($tool_content, 2);
