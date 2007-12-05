<?php
// * @version $Id$
 //   @last update: 2006-12-27 by Evelthon Prodromou <eprodromou@upnet.gr>
$require_current_course=true;
$require_login = true;
$require_prof = true;

$start_cal = $jscalendar->make_input_field(
           array('showsTime'      => false,
                 'showOthers'     => true,
                 'ifFormat'       => '%Y-%m-%d',
                 'timeFormat'     => '24'),
           array('style'       => 'width: 15em; color: #840; background-color: #ff8; border: 1px solid #000; text-align: center',
                 'name'        => 'u_date_start',
                 'value'       => $u_date_start));

$end_cal = $jscalendar->make_input_field(
           array('showsTime'      => false,
                 'showOthers'     => true,
                 'ifFormat'       => '%Y-%m-%d',
                 'timeFormat'     => '24'),
           array('style' => 'width: 15em; color: #840; background-color: #ff8; border: 1px solid #000; text-align: center',
                 'name'  => 'u_date_end',
                 'value' => $u_date_end));


$qry = "SELECT id, rubrique AS name FROM accueil WHERE define_var != '' AND visible = 1 ORDER BY name ";
$mod_opts = '<option value="-1">'.$langAllModules."</option>\n";
$result = db_query($qry, $currentCourseID);
while ($row = mysql_fetch_assoc($result)) {
    if ($u_module_id == $row['id']) { $selected = 'selected'; } else { $selected = ''; }
    $mod_opts .= '<option '.$selected.' value="'.$row["id"].'">'.$row['name']."</option>\n";
}

$statsValueOptions =
    '<option value="visits" '.	 (($u_stats_value=='visits')?('selected'):(''))	  .'>'.$langVisits."</option>\n".
    '<option value="duration" '.(($u_stats_value=='duration')?('selected'):('')) .'>'.$langDuration."</option>\n";

$statsIntervalOptions =
    '<option value="daily"   '.(($u_interval=='daily')?('selected'):(''))  .' >'.$langDaily."</option>\n".
    '<option value="weekly"  '.(($u_interval=='weekly')?('selected'):('')) .'>'.$langWeekly."</option>\n".
    '<option value="monthly" '.(($u_interval=='monthly')?('selected'):('')).'>'.$langMonthly."</option>\n".
    '<option value="yearly"  '.(($u_interval=='yearly')?('selected'):('')) .'>'.$langYearly."</option>\n".
    '<option value="summary" '.(($u_interval=='summary')?('selected'):('')).'>'.$langSummary."</option>\n";

$tool_content .= '<form method="post">
&nbsp;&nbsp;
    <table>
        <thead>
        <tr>
            <th>'.$langValueType.'</th>
            <td><select name="u_stats_value">'.$statsValueOptions.'</select></td>
        </tr>
        <tr>
            <th>'.$langStartDate.'</th>
            <td>'."$start_cal".'</td>
        </tr>
        <tr>
            <th>'.$langEndDate.'</th>
            <td>'."$end_cal".'</td>
        </tr>
        <tr>
            <th>'.$langModule.'</th>
            <td><select name="u_module_id">'.$mod_opts.'</select></td>
        </tr>
        <tr>
            <th>'.$langInterval.'</th>
            <td><select name="u_interval">'.$statsIntervalOptions.'</select></td>
        </tr>
        </thead>
        </table>
       <br/>
           <input type="submit" name="btnUsage" value="'.$langSubmit.'">
           </form>';
?>
