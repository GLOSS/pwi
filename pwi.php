<?php

/**
 * An API for SASTRA's Parent Web Interface.
 * @author Vignesh Rajagopalan <vignesh@campuspry.com>
 *
 * A one day hack! (@date 07-07-2011)
 */

require('phpQuery.php');

class PWI
{
	/**
	 * Pre-Alpha Release
	 * @version 1.0
	 */
	 
	 const VERSION = 1.0;

	/**
	 * The 2 Params!
	 */
	protected $regno; //Register Number of the Student.
	
	protected $pass; //Password => Birthday(ddmmyyyy)
	
	/**
	 * Initialize the API.
	 */
	public function __construct($params) {
		$this->setRegNo($params['regno']);
		$this->setPass($params['pass']);
		$this->setCurlBehaviour();
		$this->loginToPWI();
	}
	
	/**
	 * Set the Params.
	 */
	public function setRegNo($regno){
		$this->regno = $regno;
		return $this;
	}
	
	public function setPass($pass){
		$this->pass = $pass;
		return $this;
	}
	
 
	/**
	 * Set the required CURL Behaviour.
	 */
	public function setCurlBehaviour(){
		$options = array(CURLOPT_POST => true,
										 CURLOPT_FOLLOWLOCATION => true,
										 CURLOPT_COOKIEJAR => "cookies.txt",
										 CURLOPT_COOKIEFILE => "cookies.txt",
										 CURLOPT_RETURNTRANSFER => true,
                 		 CURLOPT_HEADER => false
                	  );
     $this->ch = curl_init();
     curl_setopt_array($this->ch, $options);
     return $this;
	}

	/**
	 * Login to the PWI
	 */
	function loginToPWI(){
		if (isset($this->regno) && isset($this->pass)) {
			$ch = $this->ch;
			
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/usermanager/youLogin.jsp");
			curl_setopt($ch, CURLOPT_POSTFIELDS, "txtRegNumber=iamalsouser&txtPwd=thanksandregards&txtSN={$this->regno}&txtPD={$this->pass}&txtPA=1");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/youLogin.jsp");
			$this->home = curl_exec($ch);
		} else die("Register Number or Password not set.");
	}
	
	/**
	 * Fetch Student Info
	 */
	function getInfo(){
		phpQuery::newDocument($this->home);
		$list = pq('ul.leftnavlinks01');
		$details = array();
		$details["REGNO"] = $this->regno;
		$details["NAME"] =  trim(pq($list)->find('li:eq(0)')->text());
		$details["GROUP"] =  trim(pq($list)->find('li:eq(1)')->text());
		$details["SEM"] =  trim(pq($list)->find('li:eq(3)')->text());		
		return json_encode($details);
	} 
		
	/**
	 * Fetch Attendance.
	 */


        public function isLoggedIn() {
                phpQuery::newDocument($this->home);
                if (!trim(pq(pq("#masterdiv"))->text())) {
                    return FALSE;
                } else {
                   return TRUE;
                }
        }


	public function getAttendance() {
		if (isset($this->regno) && isset($this->pass)) {
		
			/**
			 * Fetch the Attendance from PWI
			 */
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=7");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$html = curl_exec($ch);
			
			/**
			 * Parse the content.
			 */
			phpQuery::newDocument($html);
			pq('table:not(:first)')->remove();
			pq('td:not(.tablecontent01,.tablecontent02,.tablecontent03,.tabletitle05)')->remove();
			pq('tr:empty')->remove();
			pq('tr:first')->remove();
			pq('tr:first')->remove();
			
			$rows = pq('table tr');
			$attendance = array();
			foreach ($rows as $key => $row) {
				if (pq($row)->find('td:eq(0)')->text() != ' TOTAL ') {
				 	$attendance[$key]['SUBCODE'] = trim(pq($row)->find('td:eq(0)')->text());
				 	$attendance[$key]['SUBNAME'] = trim(pq($row)->find('td:eq(1)')->text());
				 	$attendance[$key]['TOTAL'] = trim(pq($row)->find('td:eq(2)')->text());
				 	$attendance[$key]['PRESENT'] = trim(pq($row)->find('td:eq(3)')->text());
				 	$attendance[$key]['ABSENT'] = trim(pq($row)->find('td:eq(4)')->text());
				 	$attendance[$key]['%'] = trim(pq($row)->find('td:eq(5)')->text());
				} else {
					$attendance['%'] = trim(pq($row)->find('td:eq(4)')->text());	
				}
			}
			
			/**
			 * Encode into JSON and return. 
			 */
			return json_encode($attendance);
		} else die("Register Number or Password not set.");
	}

	/**
	 * Fetch Attendace Details.
	 */
	public function getAttendanceDetails() {
		if (isset($this->regno) && isset($this->pass)) {
		
			/**
			 * Get the Attendance Details from PWI
			 */
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=25");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$html = curl_exec($ch);

			/**
			 * Parse the content.
			 */
			phpQuery::newDocument($html);
			pq('table:not(:first)')->remove();
			pq('td:not(.tablecontent01,.tablecontent02,.tablecontent03,.tabletitle05)')->remove();
			pq('tr:empty')->remove();
			pq('tr:first')->remove();
			pq('tr:first')->remove();

			$rows = pq('table tr');
			$details = array();
			foreach ($rows as $key => $row) {
				$details[$key]['DATE'] = trim(pq($row)->find('td:eq(1)')->text());
			 	$details[$key]['SUBCODE'] = trim(pq($row)->find('td:eq(2)')->text());
			 	$details[$key]['SUBNAME'] = trim(pq($row)->find('td:eq(3)')->text());
			 	$details[$key]['HOUR'] = trim(pq($row)->find('td:eq(4)')->text());
			}
			
			/**
			 * Encode into a JSON object and return.
			 */			
			return json_encode($details);
		} else die("Register Number or Password not set.");
	}

	/**
	 * Fetch Internal Marks.
	 */	
	public function getInternalMarks() {
		if (isset($this->regno) && isset($this->pass)) {
			
			/**
			 * Can be worked upon only when there is atleast one entry!
			 */
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=22");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$details = curl_exec($ch);
			echo $details;
		} else die("Register Number or Password not set.");
	}

	/**
	 * Fetch Grades.
	 */	
	public function getGrades() {
		if (isset($this->regno) && isset($this->pass)) {
		
			/**
			 * Get the Grades from PWI
			 */
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=21");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$html = curl_exec($ch);
			
			/**
			 * Parse the content.
			 */			
			phpQuery::newDocument($html);
			pq('table tr:empty')->remove();
			pq('tr:first')->remove();
			pq('tr:first')->remove(); 

			$rows = pq('table tr:not(.tablecontent03)');
			$details = array();
			foreach ($rows as $key => $row) {
				$details[$key]['SEM'] = trim(pq($row)->find('td:eq(0)')->text());
				$details[$key]['DATE'] = trim(pq($row)->find('td:eq(1)')->text());
				$details[$key]['SUBCODE'] = trim(pq($row)->find('td:eq(2)')->text());
				$details[$key]['SUBNAME'] = trim(pq($row)->find('td:eq(3)')->text());
				$details[$key]['CREDIT'] = trim(pq($row)->find('td:eq(4)')->text());
				$details[$key]['GRADE'] = trim(pq($row)->find('td:eq(5)')->text());
			}
			$rows = pq('table tr:(.tablecontent03)');
			foreach ($rows as $row) {
				$details['CGPA'] = trim(pq($row)->find('td:eq(1)')->text());
			}

			/**
			 * Encode into JSON and return.
			 */
			return json_encode($details);
			 
		} else die("Register Number or Password not set.");
	}
	

	/**
	 * Fetch CGPA
	 */	
	public function getCGPA() {
		if (isset($this->regno) && isset($this->pass)) {
		
			/**
			 * Get the Grades Page from PWI
			 */
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=21");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$html = curl_exec($ch);
			
			/**
			 * Parse the content and only the cgpa.
			 */			
			phpQuery::newDocument($html);

			$rows = pq('table tr:(.tablecontent03)');
			foreach ($rows as $row) {
				$cgpa = trim(pq($row)->find('td:eq(1)')->text());
			}
			
			return $cgpa;
			 
		} else die("Register Number or Password not set.");
	}
	

	/**
	 * Fetch the Timetable.
	 */
	public function getTimeTable() {
		if (isset($this->regno) && isset($this->pass)) {
		
			/**
			 * Get the timetable from PWI.
			 */	
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/academy/frmStudentTimetable.jsp");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$html = curl_exec($ch);

			/**
			 * Parse and get the timetable.
			 */	
			phpQuery::newDocument($html);
			pq('table[bgcolor="#eeeeee"]')->remove();
			pq('table:not(:first) tr')->appendTo('table:first');
			pq('table:empty')->remove();
			pq('table tr:empty')->remove();
			pq('tr:first')->remove();
			pq('tr:first')->remove();
			pq('tr:first')->remove(); 
			pq('tr:first')->remove();
			pq('tr:first')->remove();
			pq('tr:first')->remove(); 

			$rows = pq('table tr');
			$details = array();
			foreach ($rows as $key => $row) {
				
				$week = pq($row)->find('td:eq(0)')->text();
				
				if($week == "Sat") break;
				
				$temp = trim(pq($row)->find('td:eq(1)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["1"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["1"]["SUBNAME"] = "";
						$details[$week]["1"]["SUBCODE2"] = "NIL";
						$details[$week]["1"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["1"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["1"]["SUBNAME"] = "";
						$details[$week]["1"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["1"]["SUBNAME2"] = "";
					}
				} else $details[$week]["1"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(2)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["2"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["2"]["SUBNAME"] = "";
						$details[$week]["2"]["SUBCODE2"] = "NIL";
						$details[$week]["2"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["2"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["2"]["SUBNAME"] = "";
						$details[$week]["2"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["2"]["SUBNAME2"] = "";
					}
				} else $details[$week]["2"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(3)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["3"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["3"]["SUBNAME"] = "";
						$details[$week]["3"]["SUBCODE2"] = "NIL";
						$details[$week]["3"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["3"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["3"]["SUBNAME"] = "";
						$details[$week]["3"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["3"]["SUBNAME2"] = "";
					}
				} else $details[$week]["3"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(4)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["4"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["4"]["SUBNAME"] = "";
						$details[$week]["4"]["SUBCODE2"] = "NIL";
						$details[$week]["4"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["4"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["4"]["SUBNAME"] = "";
						$details[$week]["4"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["4"]["SUBNAME2"] = "";
					}
				} else $details[$week]["4"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(5)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["5"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["5"]["SUBNAME"] = "";
						$details[$week]["5"]["SUBCODE2"] = "NIL";
						$details[$week]["5"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["5"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["5"]["SUBNAME"] = "";
						$details[$week]["5"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["5"]["SUBNAME2"] = "";
					}
				} else $details[$week]["5"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(6)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["6"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["6"]["SUBNAME"] = "";
						$details[$week]["6"]["SUBCODE2"] = "NIL";
						$details[$week]["6"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["6"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["6"]["SUBNAME"] = "";
						$details[$week]["6"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["6"]["SUBNAME2"] = "";
					}
				} else $details[$week]["6"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(7)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["7"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["7"]["SUBNAME"] = "";
						$details[$week]["7"]["SUBCODE2"] = "NIL";
						$details[$week]["7"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["7"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["7"]["SUBNAME"] = "";
						$details[$week]["7"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["7"]["SUBNAME2"] = "";
					}
				} else $details[$week]["7"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(8)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["8"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["8"]["SUBNAME"] = "";
						$details[$week]["8"]["SUBCODE2"] = "NIL";
						$details[$week]["8"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["8"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["8"]["SUBNAME"] = "";
						$details[$week]["8"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["8"]["SUBNAME2"] = "";
					}
				} else $details[$week]["8"] = "FREE";

				$temp = trim(pq($row)->find('td:eq(9)')->text());
				if ($temp != NULL) {
					if (strpos($temp, ",") === false) {
						$temp = explode("-",$temp);
						$details[$week]["9"]["SUBCODE"] = trim($temp[0]);
						$details[$week]["9"]["SUBNAME"] = "";
						$details[$week]["9"]["SUBCODE2"] = "NIL";
						$details[$week]["9"]["SUBNAME2"] = "NIL";
					} else {
						$temp = explode(",",$temp);
						$temp2 = explode("-",$temp[0]);
						$temp3 = explode("-", $temp[1]);
						$details[$week]["9"]["SUBCODE"] = trim($temp2[0]);
						$details[$week]["9"]["SUBNAME"] = "";
						$details[$week]["9"]["SUBCODE2"] = trim($temp3[0]);
						$details[$week]["9"]["SUBNAME2"] = "";
					}
				} else $details[$week]["9"] = "FREE";

			}
			
			/**
			 * Encode into a JSON object and return.
			 */	
			return json_encode($details);
			
		} else die("Register Number or Password not set.");
	}
	
	/**
	 * Fetch Hostel Details.
	 */
	
	function getHostelDetails() {
		if (isset($this->regno) && isset($this->pass)) {
		
			/**
			 * Get the Hostel Details from PWI.
			 */
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=3");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$html = curl_exec($ch);
			
			/**
			 * Parse the content to fetch the room name and number.
			 */
			phpQuery::newDocument($html);
			pq('tr:first')->remove();
			pq('tr:first')->remove(); 

			$rows = pq('table tr');
			$details = array();
			foreach($rows as $row) {
				$details["ROOMNAME"] = trim(pq($row)->find('td:eq(0)')->text());
				$details["ROOMNO"] = trim(pq($row)->find('td:eq(1)')->text());
			}

			/**
			 * Export as JSON.
			 */
			return json_encode($details);
			
		} else die("Register Number or Password not set.");		
	}


	/**
	 * Fetch All or Part of the Courses.
	 */
	
	function getCourses($sem = NULL) {
		if (isset($this->regno) && isset($this->pass)) {
		
			/**
			 * Get the Courses page from PWI.
			 */
			$ch = $this->ch;
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=4");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
			$html = curl_exec($ch);
			
			/**
			 * Parse the content to fetch the queried details.
			 */
			phpQuery::newDocument($html);
			pq('tr:first')->remove();
			pq('tr:first')->remove(); 

			$rows = pq('table tr');
			$details = array();
			$key = 0;
			foreach($rows as $row) {
				if ($sem == NULL) {
					$details[$key]["SUBCODE"] = trim(pq($row)->find('td:eq(0)')->text());
					$details[$key]["SUBNAME"] = trim(pq($row)->find('td:eq(1)')->text());
					$details[$key]["CREDIT"] = trim(pq($row)->find('td:eq(3)')->text());
					$details[$key]["SEM"] = trim(pq($row)->find('td:eq(2)')->text());
					$key++;
				} else {
					if (trim(pq($row)->find('td:eq(2)')->text()) != $sem) continue;
					else {
						$details[$key]["SUBCODE"] = trim(pq($row)->find('td:eq(0)')->text());
						$details[$key]["SUBNAME"] = trim(pq($row)->find('td:eq(1)')->text());
						$details[$key]["CREDIT"] = trim(pq($row)->find('td:eq(3)')->text());
						$details[$key]["SEM"] = trim(pq($row)->find('td:eq(2)')->text());
						$key++;
					}
				}
			}
			
			/**
			 * Export as JSON.
			 */
			return json_encode($details);
			
		} else die("Register Number or Password not set.");		
	}	 
}

?>
