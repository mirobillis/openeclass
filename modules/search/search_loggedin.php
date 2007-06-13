<?
/**=============================================================================
       	GUnet e-Class 2.0 
        E-learning and Course Management Program  
================================================================================
       	Copyright(c) 2003-2006  Greek Universities Network - GUnet
        A full copyright notice can be read in "/info/copyright.txt".
        
       	Authors:    Costas Tsibanis <k.tsibanis@noc.uoa.gr>
        	    	Yannis Exidaridis <jexi@noc.uoa.gr> 
      		    	Alexandros Diamantidis <adia@noc.uoa.gr> 

        For a full list of contributors, see "credits.txt".  
     
        This program is a free software under the terms of the GNU 
        (General Public License) as published by the Free Software 
        Foundation. See the GNU License for more details. 
        The full license can be read in "license.txt".
     
       	Contact address: GUnet Asynchronous Teleteaching Group, 
        Network Operations Center, University of Athens, 
        Panepistimiopolis Ilissia, 15784, Athens, Greece
        eMail: eclassadmin@gunet.gr
==============================================================================*/

/*===========================================================================
	search_loggedin.php
	@version $Id$
	@authors list: Agorastos Sakis <th_agorastos@hotmail.com>
==============================================================================        
    @Description: Search function that searches data within public access
    courses and courses to which the student enrolled. In case the user is
    an administrator the script shows all the courses the user manages
    This script is intened to be used by logged-in users (students/adminsitrators)
==============================================================================*/

$require_current_course = FALSE;
$langFiles = array('course_info', 'create_course', 'opencours', 'search');
//include '../../include/baseTheme.php';

$nameTools = $langSearch;
$tool_content = "";




//elegxos ean *yparxoun* oroi anazhthshs
if(empty($search_terms_title) && empty($search_terms_keywords) && empty($search_terms_instructor) && empty($search_terms_coursecode)) {
/**********************************************************************************************
				emfanish formas anahzthshs ean oi oroi anazhthshs einai kenoi 
***********************************************************************************************/

		$tool_content .= "
		
			<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">
			<FIELDSET>
			<LEGEND><p>$langSearchWith</p></LEGEND>
			<table width=\"99%\">

			    <tr>
				<td colspan=3>
				   &nbsp;
				</td>
				</tr>
				<tr>
				<td width=\"90\" valign=top>
					<p><b>$langTitle</b></p>
				</td>
				<td valign=top width=\"250\">
					<p><input name=\"search_terms_title\" type=\"text\" size=\"50\" /></p>
				</td>
				<td valign=top>
					<p>$langTitle_Descr</p>
				</td>	
				</tr>
				<tr>
				<td valign=top>
					<p><b>$langKeywords</b></p>
				</td>				
				<td valign=top>
					<p><input name=\"search_terms_keywords\" type=\"text\" size=\"50\" /></p>
				</td>
				<td valign=top>
					<p>$langKeywords_Descr</p>
				</td>	
				</tr>
				<tr>
				<td valign=top>
					<p><b>$langInstructor</b></p>
				</td>					
				<td valign=top>
					<p><input name=\"search_terms_instructor\" type=\"text\" size=\"50\" /></p>
				</td>
				<td valign=top>
					<p>$langInstructor_Descr</p>
				</td>
				</tr>
				<tr>
				<td valign=top>
					<p><b>$langCourseCode</b></p>
				</td>					
				<td>
					<p><input name=\"search_terms_coursecode\" type=\"text\" size=\"50\" /></p>
				</td>
				<td valign=top>
					<p>$langCourseCode_Descr</p>
				</td>
				</tr>
				<tr>
				<td>
					&nbsp;
				</td>	
				<td colspan=2>
					<p><input type=\"Submit\" name=\"submit\" value=\"$langDoSearch\" />&nbsp;
					&nbsp;<input type=\"Reset\" name=\"reset\" value=\"$langNewSearch\" /></p>
				</td>
			    </tr>
											
			</table>
			<br/>
				
			</FIELDSET>
			</form>
		";
	
}else 
{
/**********************************************************************************************
					ektelesh anazhthshs afou yparxoun oroi anazhthshs
						 emfanish arikown mhnymatwn anazhthshs
***********************************************************************************************/
	
	//ektypwsh mhnymatos anazhthshs
	$tool_content .= "<h2>$langSearchingFor</h2>";

	
	//ektelesh erwthmatos gia to se poia mathimata einai eggegramenos o xrhsths. sta apotelesmata perilamvanontai
	//kai ola ta anoixta kai anoixta me eggrafh mathimata.
	$result = mysql_query(" SELECT DISTINCT cours.code, cours.intitule, cours.course_keywords, cours.titulaires
							FROM `cours` , `cours_user` 
							WHERE (
							cours.code = cours_user.code_cours
							AND cours_user.user_id = '".$uid."'
							)
							OR cours.visible = '2'
							OR cours.visible = '1'");
	
	
	
	$results_found = 0; //arithmos apotelesmatwn pou exoun emfanistei (ena gia kathe grammh tou $mycours)
	
	
	//*****************************************************************************************************
	//vrogxos gia na diatreksoume ola ta mathimata sta opoia enai anoixta (public OR open for registration)
    while ($mycours = mysql_fetch_array($result)) 
    {
    	
		$show_entry = FALSE; //flag gia emfanish apotelesmatwn se mia grammh tou array efoson entopistoun apotelesmata				
				
		if (!empty($search_terms_title)) $show_entry = match_arrays($search_terms_title, $mycours['intitule']);
		if (!empty($search_terms_keywords)) if($show_entry == FALSE) $show_entry = match_arrays($search_terms_keywords, $mycours['course_keywords']);
		if (!empty($search_terms_instructor)) if($show_entry == FALSE) $show_entry = match_arrays($search_terms_instructor, $mycours['titulaires']);
		if (!empty($search_terms_coursecode)) if($show_entry == FALSE) $show_entry = match_arrays($search_terms_coursecode, $mycours['code']);
		
		
		//EMFANISH APOTELESMATOS:
		//ean to flag $show_entry exei allaxtei se TRUE (ara kapoios apo tous orous anazhthshs entopistike sto
		//$mycours, emfanise thn eggrafh
		if($show_entry)
		{			
			
			$tool_content .= "
			<div id=\"marginForm\">
			<fieldset>
				<legend>
					
						".$langLesson.": ".$mycours['intitule']."
				</legend>
				<label>
				<ul class=\"listBullet\">
				<li>
					$langLessonCode : ".$mycours['code']."</li>
					
				<li>
					$langInstructor : ".$mycours['titulaires']."
				</li><li>
					$langKeywords : ".$mycours['course_keywords']."
				</li>
				<li><a href=\"../../courses/".$mycours['code']."/\"> ".$langEnter."</a></li>
				</label>
				<div class=\"clearer\"></div>
			</fieldset>
			</div>
			";
			
			//afkhsh tou arithmou apotelesmatwn
			$results_found++;			
		}
		
    }
    
    //elegxos tou arithmou twn apotelesmatwn pou exoun emfanistei. ean den emfanistike kanena apotelesma, ektypwsh analogou mhnymatos
   if($results_found == 0) $tool_content .= "<p>$langNoResult</p>";
    
    //ektypwsh syndesmou gia nea anazhthsh
    $tool_content .= "<p align=\"center\"><a href=\"search.php\">$langNewSearch</a></p>";
    
}

draw($tool_content, 1,'search');


//katharisma twn orwn anazhthshs gia apofygh lathwn
$search_terms_title = "";
$search_terms_keywords = "";
$search_terms_instructor = "";
$search_terms_coursecode ="";





//voithitiki function gia ton entopismo strings se array mesa se string
function match_arrays($search_terms_array, $mycours_string)
{
	//elegxos gia to an yparxoun apotelesmata sthn trexousa grammh toy $mycours_array
		if(!empty($search_terms_array) || $search_terms_array != "" || !empty($mycours_string) || $mycours_string != "")
		{
			//echo "compare: ".$search_terms_array." == ".$mycours_string;
			$ret = my_stripos($mycours_string, $search_terms_array);
			//if($ret == 0) echo " MATCH!<br>";
			//echo "<br> RET: ".$ret;
			if($ret !== FALSE) return TRUE;
			
			//echo "<br>";
		}
		
	return FALSE;
}


function my_stripos($string, $word)
{
      $source = array('�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�');
      $target = array('�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�');

     return strpos(
       str_replace($source, $target, mb_strtolower($string, 'ISO-8859-7')),
       str_replace($source, $target, mb_strtolower($word, 'ISO-8859-7')));
}

?>
