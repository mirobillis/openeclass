<?php

/* ========================================================================
 * Open eClass 2.4
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



/* ===========================================================================
  phpInfo.php
  @last update: 31-05-2006 by Pitsiougas Vagelis
  @authors list: Karatzidis Stratos <kstratos@uom.gr>
  Pitsiougas Vagelis <vagpits@uom.gr>
  ==============================================================================
  @Description: Show contents of phpinfo()

  This script allows the administrator to view the results of phpinfo()

  The user can : - View information generated by phpinfo()
  - Return to course list

  @Comments: The script is organised in two sections.

  1) Display results generated from phpinfo()
  2) Display all on an HTML page

  ============================================================================== */

/* * ***************************************************************************
  DEAL WITH BASETHEME, OTHER INCLUDES AND NAMETOOLS
 * **************************************************************************** */

$require_admin = true;
require_once '../../include/baseTheme.php';

$nameTools = $langPHPInfo;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);

/* * ***************************************************************************
  MAIN BODY
 * **************************************************************************** */

// Display phpinfo
$tool_content .= '<div>';
ob_start();
phpinfo();
$tool_content .= standard_text_escape(ob_get_contents());
ob_end_clean();
$tool_content .= '</div>';

// Display link to go back to index.php
$tool_content .= action_bar(array(
    array('title' => $langBack,
        'url' => "index.php",
        'icon' => 'fa-reply',
        'level' => 'primary-label')));

$local_head_contents = '<style type="text/css">
        pre {margin: 0px; font-family: monospace;}
        table {border-collapse: collapse;}
        .center {text-align: center;}
        .center table { margin-left: auto; margin-right: auto; text-align: left;}
        .center th { text-align: center !important; }
        td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
        h1 {font-size: 150%;}
        h2 {font-size: 125%;}
        .p {text-align: left;}
        .e {background-color: #ccccff; font-weight: bold; color: #000000;}
        .h {background-color: #9999cc; font-weight: bold; color: #000000;}
        .v {background-color: #cccccc; color: #000000;}
        .vr {background-color: #cccccc; text-align: right; color: #000000;}
        img {float: right; border: 0px;}
        hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
        </style>
        ';

/* * ***************************************************************************
  DISPLAY HTML
 * **************************************************************************** */
// Call draw function to display the HTML
// $tool_content: the content to display
// 3: display administrator menu
// admin: use tool.css from admin folder
draw($tool_content, 3, 'admin', $local_head_contents);
