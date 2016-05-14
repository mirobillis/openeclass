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

$require_current_course = true;
require_once '../../include/baseTheme.php';

// Include the main TCPDF library
require_once __DIR__.'/../../include/tcpdf/tcpdf_include.php';
require_once __DIR__.'/../../include/tcpdf/tcpdf.php';

require_once 'work_functions.php';
require_once 'modules/group/group_functions.php';

$nameTools = $langAutoJudgeDetailedReport;

if (isset($_GET['assignment']) && isset($_GET['submission'])) {
    global $tool_content, $course_code, $m, $langAutoJudgeNotEnabledForReport;
    $as_id = intval($_GET['assignment']);
    $sub_id = intval($_GET['submission']);
    $assign = get_assignment_details($as_id);
    $sub = get_assignment_submit_details($sub_id);

    if($sub==null || $assign==null)
    {
        redirect_to_home_page('modules/work/index.php?course='.$course_code);
    }

    $navigation[] = array("url" => "index.php?course=$course_code", "name" => $langWorks);
    $navigation[] = array("url" => "index.php?course=$course_code&amp;id=$as_id", "name" => q($assign->title));

    if (count($sub)>0) {
        if($assign->auto_judge){// auto_judge enable
            $auto_judge_scenarios = unserialize($assign->auto_judge_scenarios);
            $auto_judge_scenarios_output = unserialize($sub->auto_judge_scenarios_output);

            if(!isset($_GET['downloadpdf'])){
                show_report($as_id, $sub_id, $assign, $sub, $auto_judge_scenarios, $auto_judge_scenarios_output);
                draw($tool_content, 2);
            }else{
                download_pdf_file($assign, $sub, $auto_judge_scenarios, $auto_judge_scenarios_output);
            }
         }
         else{
               Session::Messages($langAutoJudgeNotEnabledForReport, 'alert-danger');
              draw($tool_content, 2);
             }
      } else {
            Session::Messages($m['WorkNoSubmission'], 'alert-danger');
            redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id);
       }

   } else {
        redirect_to_home_page('modules/work/index.php?course='.$course_code);
    }

// Returns an array of the details of assignment $id
function get_assignment_details($id) {
    global $course_id;
    return Database::get()->querySingle("SELECT * FROM assignment WHERE course_id = ?d AND id = ?d", $course_id, $id);
}

function get_assignment_submit_details($sid) {
    return Database::get()->querySingle("SELECT * FROM assignment_submit WHERE id = ?d",$sid);
}

function get_course_title() {
    global $course_id;
    $course = Database::get()->querySingle("SELECT title FROM course WHERE id = ?d",$course_id);
    return $course->title;
}

function get_submission_rank($assign_id,$grade, $submission_date) {
    return Database::get()->querySingle("SELECT COUNT(*) AS count FROM assignment_submit WHERE (grade > ?f OR (grade = ?f AND submission_date < ?t)) AND assignment_id = ?d",$grade,$grade, $submission_date,$assign_id)->count+1;
}

function show_report($id, $sid, $assign,$sub, $auto_judge_scenarios, $auto_judge_scenarios_output) {
         global $m, $course_code,$tool_content, $langAutoJudgeInput, $langAutoJudgeOutput,
                 $langAutoJudgeExpectedOutput, $langAutoJudgeOperator, $langAutoJudgeWeight,
                 $langAutoJudgeResult, $langAutoJudgeResultsFor, $langAutoJudgeRank,
                 $langAutoJudgeDownloadPdf, $langBack;
               $tool_content = "
                                <table  style=\"table-layout: fixed; width: 99%\" class='table-default'>
                                <tr> <td> <b>$langAutoJudgeResultsFor</b>: ".  q(uid_to_name($sub->uid))."</td> </tr>
                                <tr> <td> <b>".$m['grade']."</b>: $sub->grade /$assign->max_grade </td>
                                     <td><b> $langAutoJudgeRank</b>: ".get_submission_rank($assign->id,$sub->grade, $sub->submission_date)." </td>
                                </tr>
                                  <tr> <td> <b>$langAutoJudgeInput</b> </td>
                                       <td> <b>$langAutoJudgeOutput</b> </td>
                                       <td> <b>$langAutoJudgeExpectedOutput</b> </td>
                                       <td> <b>$langAutoJudgeWeight</b> </td>
                                       <td> <b>$langAutoJudgeResult</b> </td>
                                </tr>
                                ".get_table_content($auto_judge_scenarios, $auto_judge_scenarios_output, $assign->max_grade,$sub->grade_comment_error)."
                                </table>
                                <p align='left'><a href='work_result_rpt.php?course=".$course_code."&assignment=".$assign->id."&submission=".$sid."&downloadpdf=1'>$langAutoJudgeDownloadPdf</a></p>
                                <p align='right'><a href='index.php?course=".$course_code."'>$langBack</a></p>
                             <br>";
  }

function get_table_content($auto_judge_scenarios, $auto_judge_scenarios_output, $max_grade,$comment_error) {
    global $themeimg, $langAutoJudgeAssertions;
    $table_content = "";
    $i=0;
    $span1 = "<span style=\"color:#ff0000;\">";  $span2 = "</span>";//background-color:#ffff66;
    foreach($auto_judge_scenarios as $cur_senarios){
           if(!isset($cur_senarios['output']))// expected output disable
               $cur_senarios['output'] = "-";
           $icon = ($auto_judge_scenarios_output[$i]['passed']==1) ? 'tick.png' : 'delete.png';
           $table_content.="
                           <tr>
                           <td style=\"word-break:break-all;\">".str_replace(' ', '&nbsp;', $cur_senarios['input'])."</td>                        
                           <td style=\"word-break:break-all;\">".(isset($auto_judge_scenarios_output[$i]['student_output'])? str_replace(' ', '&nbsp;',$auto_judge_scenarios_output[$i]['student_output']):" ")."</td>";
                           switch($cur_senarios['assertion'])
                           {
                               case 'integer':
                               case 'float':
                               case  'digit':
                               case 'boolean':
                               case  'notEmpty':
                               case 'notNull':
                               case 'string':
                               case 'numeric':
                               case 'isArray':
                               case 'true':
                               case 'false':
                               case 'isJsonString':
                               case 'isObject':
                               $table_content .="<td style=\"word-break:break-all;color:#0033cc;\">".str_replace(' ', '&nbsp;',$langAutoJudgeAssertions[$cur_senarios['assertion']])."</td>";
                               break;
                               default:                               
                               $table_content .="<td style=\"word-break:break-all;\">".str_replace(' ', '&nbsp;', $cur_senarios['output'])."</td>";
                               break;
                           }
                           $table_content .=" 
                           <td align=\"center\" style=\"word-break:break-all;\">".$cur_senarios['weight']."/".$max_grade."</td>
                           <td align=\"center\"><img src=\"http://".$_SERVER['HTTP_HOST'].$themeimg."/" .$icon."\"></td></tr>";
                           if($auto_judge_scenarios_output[$i]['passed']!=1 && isset($cur_senarios['feedback_check']) && (strlen($comment_error)<=0 || $comment_error==Null))
                           {
                               $feedback_text = ($cur_senarios['feedback_text'] != '') ? $span1.$cur_senarios['feedback_text'].$span2.'<br><br>' : "" ;
                               if($cur_senarios['feedback_scenario_count']>0)
                               {
                                   $j=0;
                                   $index=1;
                                   foreach( $cur_senarios['feedback_scenario'] as $feedback_scenario )
                                   {
                                    if($auto_judge_scenarios_output[$i]['feedback_scenario_passed'][$j]['matched']==1)
                                     {
                                         $feedback_text .=( $feedback_scenario['feedback_text']=='') ? '' : $span1.'#'.$index.' '.$feedback_scenario['feedback_text'].$span2."<br>" ; $index++;
                                         $feedback_text .= show_assertion_results($feedback_scenario['Assertion'],$cur_senarios['input'],$auto_judge_scenarios_output[$i]['student_output'],$cur_senarios['output'])."<br>";
                                     }
                                      $j++;                                    
                                   }
                               }     
                            $table_content.="<tr><td  colspan=\"5\" style=\"word-break:break-all;\">".$feedback_text."</td></tr>";   
                           }                          
                     $i++;//.str_replace(' ', '&nbsp;', )
                }
                if( strlen($comment_error)>0 )
                 $table_content.="<tr><td  colspan=\"5\" style=\"word-break:break-all;color:red\">".$comment_error."</td></tr>";   
       return $table_content;
  }

function download_pdf_file($assign, $sub, $auto_judge_scenarios, $auto_judge_scenarios_output) {
    global $langAutoJudgeInput, $langAutoJudgeOutput,
        $langAutoJudgeExpectedOutput, $langAutoJudgeOperator,
        $langAutoJudgeWeight, $langAutoJudgeResult,
        $langCourse, $langAssignment, $langStudent, $langAutoJudgeRank, $m;

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(PDF_AUTHOR);
    $pdf->SetTitle('Auto Judge Report');
    $pdf->SetSubject('Auto Judge Report');
    // set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // add a page
    $pdf->AddPage();

    $report_table = '
    <style>
    table.first{
        width: 100%;
        border-collapse: collapse;
    }

    td {
        font-size: 0.9em;
        border: 1px solid #95CAFF;
        padding: 3px 7px 2px 7px;
    }

     th {
        font-size: 0.9em;
        text-align: center;
        padding-top: 5px;
        padding-bottom: 4px;
        background-color: #3399FF;
        color: #ffffff;
    }

    </style>
    <table class="first">
        <tr>
            <th>' . $langAutoJudgeInput . '</th>
            <th>' . $langAutoJudgeOutput . '</th>
            <th>' . $langAutoJudgeExpectedOutput . '</th>
            <th>' . $langAutoJudgeWeight . '</th>
            <th>' . $langAutoJudgeResult . '</th>
        </tr>
     '. get_table_content($auto_judge_scenarios, $auto_judge_scenarios_output,$assign->max_grade,$sub->grade_comment_error).'
    </table>';


 $report_details ='
    <style>
    table.first{
        width: 100%;
        border-collapse: collapse;
        vertical-align: center;
    }

    td {
        font-size: 1em;
        border: 1px solid #000000;
        padding: 3px 7px 2px 7px;
        text-align: center;
    }

     th {
        font-size: 1.0em;
        text-align: left;
        padding-top: 5px;
        padding-bottom: 4px;
        background-color: #3399FF;
        color: #ffffff;
        width: 120px;
        border: 1px solid #000000;
    }
    </style>

    <table class="first">
      <tr>
        <th>' . $langCourse . '</th><td>' . q(get_course_title()) . '</td>
      </tr>
      <tr>
        <th>' . $langAssignment . '</th><td>' . q($assign->title) . '</td>
      </tr>
      <tr>
        <th>' . $langStudent . '</th><td> '.q(uid_to_name($sub->uid)).'</td>
      </tr>
      <tr>
        <th>' . $m['grade'] . '</th><td>' . $sub->grade . '/' . $assign->max_grade . '</td>
      </tr>
      <tr>
        <th>' . $langAutoJudgeRank . '</th><td>' . get_submission_rank($assign->id, $sub->grade, $sub->submission_date) . '</td>
      </tr>
    </table>';

    $pdf->writeHTML($report_details, true, false, true, false, '');
    $pdf->Ln();
    $pdf->writeHTML($report_table, true, false, true, false, '');
    $pdf->Output('auto_judge_report_'.q(uid_to_name($sub->uid)).'.pdf', 'D');
}

function show_assertion_results($assertion,$input,$output,$xoutput)
{
  global $langAutoJudgeFeedBackResultMatch,$langAutoJudgeOutput,$langAutoJudgeFeedBackResultMatchTokens;
  $build = "";$wrong = "";
  $span1 = "<span style=\"color:#ff0000;background-color:#ffff66;\">";  $span2 = "</span>";
  $filler="<span style=\"color:#ff0000;\">==================================================================================</span><br>";  
  $indexes = " ";
  $indexStart = -1;
  $changed = false;
  $i=0;  
  switch($assertion)
  {   
      case 'anagram':  
      $len = strlen($output);
      { 
         $visited = " "; 
         $locations ="";
         $posar = [];
         $out = strtolower($output);
         $xout = strtolower($xoutput);         
         for($i=0;$i<$len;$i++){             
            if($out[$i]==$xout[$i]){
              if($indexStart!=(-1)){
                    $build .= $span1.str_replace(' ', '&nbsp;',$wrong).$span2;
                    $wrong = "";
                    if($indexStart!=($i-1))
                       $indexes .= "{".$indexStart."-".($i-1)."} ";
                    else 
                       $indexes .= $indexStart." ";
                    $indexStart=-1;
              }
              $build.=str_replace(' ', '&nbsp;',$xout[$i]);                    
              $posar[$i]='x';
              $visited .= $i." ";
            }
            else{
                if($indexStart==(-1))
                      $indexStart=$i;
                 $wrong .= $output[$i];
            }
         }
         if($indexStart!=(-1)){
                 $build .= $span1.str_replace(' ', '&nbsp;',$wrong).$span2;
                 if($indexStart!=($i-1))
                    $indexes .= "{".$indexStart."-".($i-1)."} ";
                 else 
                    $indexes .= $indexStart." ";
         }
         for($i=0;$i<$len;$i++){             
             if(isset($posar[$i]))
               continue;
            for($j=0;$j<$len;$j++){
               if(strpos($visited,' '.$j.' ')===false && $xout[$i]==$out[$j]){
                  $visited.= " ".$j." ";
                  $locations .= "Character [".$xoutput[$i]."] found on position {".$j."} instead of position {".$i."}<br>";
                  break;
               }                   
             }
         }
      }
      return $filler.$langAutoJudgeFeedBackResultMatch.":[".$indexes."]<br>".$locations."<br>".$langAutoJudgeOutput.":<br>".$build."<br>".$filler;             
      case 'missing': {
       for($i=0;$i<strlen($xoutput);$i++){
            if(strlen($output)==0) $matcher=null;
            else $matcher = $output[0];                
            if($xoutput[$i]==$matcher ){
              if($indexStart!=(-1)){
                    $build .= $span1.str_replace(' ', '&nbsp;',$wrong).$span2;
                    $wrong = "";
                    if($indexStart!=($i-1))
                       $indexes .= "{".$indexStart."-".($i-1)."} ";
                    else 
                       $indexes .= $indexStart." ";
                    $indexStart=-1;
              }
              $build.=str_replace(' ', '&nbsp;',$matcher);
              $output = substr($output, 1);              
            }
            else{
                if($indexStart==(-1))
                      $indexStart=$i;
                 $wrong .= $xoutput[$i];
            }
        }
         if($indexStart!=(-1)){
                 $build .= $span1.str_replace(' ', '&nbsp;',$wrong).$span2;
                 if($indexStart!=($i-1))
                    $indexes .= "{".$indexStart."-".($i-1)."} ";
                 else 
                    $indexes .= $indexStart." ";
         }
      }
      return $filler.$langAutoJudgeFeedBackResultMatch.":[".$indexes."]<br><br>".$langAutoJudgeOutput.":<br>".$build."<br>".$filler; 
      case 'redundant': {
          $xout = strtolower($xoutput);
          $out = strtolower($output);
          $prevpos=-1;
          for($i=0;$i<strlen($xout);$i++){
              if($prevpos==(-1))
               $pos =strpos($out,$xout[$i]);
              else
               $pos =strpos($out,$xout[$i],$prevpos+1);
              if($prevpos==(-1)){
                  if($pos!=0){                    
                      $build.= $span1.'<del>'.str_replace(' ', '&nbsp;',substr($output,0,$pos)).'</del>'.$span2;
                      if($pos==1)
                          $index.='0 ';
                      else
                          $indexes.='{0-'.($pos-1).'} ';
                  }
              }
              else{
                  if( ($pos-$prevpos)==2 ){
                      $build.= $span1.'<del>'.str_replace(' ', '&nbsp;',$output[$pos-1]).'</del>'.$span2;
                      $indexes .= ($pos-1)." ";
                  }
                  else if( ($pos-$prevpos)>2){
                      $build.= $span1.'<del>'.str_replace(' ', '&nbsp;',substr($output,$prevpos+1,$pos-($prevpos+1))).'</del>'.$span2;
                      $indexes .= "{".($prevpos+1)."-".($pos-1)."} ";
                  }
              }
              $prevpos=$pos;
              $build.= str_replace(' ', '&nbsp;',$output[$pos]);
          }
          if($pos!=(strlen($output)-1)){
              $temp = substr($output,$prevpos+1);
              $build .=$span1.'<del>'.str_replace(' ', '&nbsp;',$temp).'</del>'.$span2;
              if(strlen($temp)==1)
                  $indexes .= ($prevpos+1)." ";
              else
                  $indexes .= "{".($pos+1)."-".(strlen($output)-1)."} ";
          }
      }
      return  $filler.$langAutoJudgeFeedBackResultMatch.":[".$indexes."]<br><br>".$langAutoJudgeOutput.":<br>".$build."<br>".$filler."<br>";
      case 'CaseSensitive': {
            for($i;$i<strlen($output);$i++){
                if($output[$i]==$xoutput[$i]){
                    if($indexStart!=(-1)){
                        $build .= $span1.$wrong.$span2;
                        $wrong = "";
                        if($indexStart!=($i-1))
                            $indexes .= "{".$indexStart."-".($i-1)."} ";
                        else 
                            $indexes .= $indexStart." ";
                        $indexStart=-1;
                    }
                    $build.=str_replace(' ', '&nbsp;',$output[$i]);
                }
                else{
                    if($indexStart==(-1))
                        $indexStart=$i;
                    $wrong .= $output[$i];
                }
            }
            if($indexStart!=(-1)){
                 $build .= $span1.$wrong.$span2;
                 if($indexStart!=($i-1))
                    $indexes .= "{".$indexStart."-".($i-1)."} ";
                 else 
                    $indexes .= $indexStart." ";
             }
      }
            return  $filler.$langAutoJudgeFeedBackResultMatch.":[".$indexes."]<br><br>".$langAutoJudgeOutput.":<br>".$build."<br>".$filler;             
      case 'RepeatableLetters':return 'RepeatableLetters';
      case 'different': 
      $indexStart=-1;
      for($i=0;$i<strlen($output);$i++){
          if($output[$i]==$xoutput[$i]){
                    if($indexStart!=(-1)){
                        $build .= $span1.$wrong.$span2;
                        $wrong = "";
                        if($indexStart!=($i-1))
                            $indexes .= "{".$indexStart."-".($i-1)."} ";
                        else 
                            $indexes .= $indexStart." ";
                        $indexStart=-1;
                    }
                    $build.=str_replace(' ', '&nbsp;',$output[$i]);
                }
                else{
                    if($indexStart==(-1))
                        $indexStart=$i;
                    $wrong .= $output[$i];
                }
     }
            if($indexStart!=(-1)){
                 $build .= $span1.$wrong.$span2;
                 if($indexStart!=($i-1))
                    $indexes .= "{".$indexStart."-".($i-1)."} ";
                 else 
                    $indexes .= $indexStart." ";
             }
      return  $filler.$langAutoJudgeFeedBackResultMatch.":[".$indexes."]<br><br>".$langAutoJudgeOutput.":<br>".$build."<br>".$filler;   
      case 'order':
      $words = "";
      $replaces= "";     
      $xout = array_filter(preg_split ( '/[\t\n,;!. ]+/' , strtolower($xoutput)));
      $out = array_filter( preg_split ( '/[\t\n,;!. ]+/' , strtolower($output)));
      for($i=0;$i<count($out);$i++){
          if($out[$i]==$xout[$i])
          $pos[$i] = 1;
          else
          $pos[$i] = 0;
      }
      for($i=0;$i<count($out);$i++){
            if($out[$i]==$xout[$i]){     
                $words .= $out[$i]." "; 
                continue;
            }
            else for($j=0;$j<count($out);$j++){
                if($pos[$j]==0 && $xout[$j]==$out[$i]){
                    $pos[$j]=1;
                    $words.= $span1.$out[$i].$span2." ";
                    $replaces .= "Token [".$out[$i]."] found on position {".$i."} instead of position {".$j."}.<br>";
                    $indexes .= ($i+1)." ";
                    break;
                }
            }
      }
      return $filler.$langAutoJudgeFeedBackResultMatchTokens.':[ '.$indexes."]<br>".$replaces.$words;
      default: return "";
  }
}