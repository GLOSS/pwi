<?php

/**
 * An API for SASTRA's Parent Web Interface.
 * @author Vignesh Rajagopalan <vignesh@campuspry.com>
 *
 * A one day hack! (@date 07-07-2011)
 */

require_once('phpQuery.php');

class PWI
{
	/**
	 * Alpha Release
	 * @version 1.1
	 *
	 * @contributor(s): Ashwanth Kumar <ashwanth.kumar@gmail.com>
	 */
	 
	const VERSION = 1.1;

	/**
	 * The 2 Params!
	 */
	protected $regno; //Register Number of the Student.
	protected $pass; //Password => Birthday(ddmmyyyy)
	
	private $isAuthenticated; // Parameter to find if the user has successfully been authenticated against the PWI
	private $isMainCampus;	// Parameter to find if the user is from Main Campus or from SRC Kumbakonam
	
	/**
	 * Initialize the API.
	 */
	public function __construct($params) {
		$this->isAuthenticated = false;
		$this->setRegNo($params['regno']);
		$this->setPass($params['pass']);
		$this->setCurlBehaviour();
		$this->loginToPWI();
	}
	
	/**
	 * Set the Params.
	 */
	private function setRegNo($regno){
		$this->regno = $regno;
		return $this;
	}
	
	private function setPass($pass){
		$this->pass = $pass;
		return $this;
	}
	
	/**
	 * Set the required CURL Behaviour.
	 */
	private function setCurlBehaviour(){
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
	private function loginToPWI() {
		if (isset($this->regno) && isset($this->pass)) {
			$ch = $this->ch;
			
			curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/usermanager/youLogin.jsp");
			curl_setopt($ch, CURLOPT_POSTFIELDS, "txtRegNumber=iamalsouser&txtPwd=thanksandregards&txtSN={$this->regno}&txtPD={$this->pass}&txtPA=1");
			curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/youLogin.jsp");
			$this->home = curl_exec($ch);
			$loginPage = $this->home;
			
			$matchCount = preg_match("/Login failed, Username and Password do not match/",$loginPage, $matches);
			// Change the auth mode for the user
			if($matchCount > 0) $this->isAuthenticated = false;
			else $this->isAuthenticated = true;
			
			$matchCount = preg_match("/SASTRA-Srinivasa Ramanujam Center/",$loginPage, $matches);
			if($matchCount > 0) $this->isMainCampus = false;
			else $this->isMainCampus = true;
		} else die("Register Number or Password not set.");
	}
	
	/**
	 *	Get the login status of the current user
	 **/
	public function getAuthStatus() {
		return $this->isAuthenticated;
	}
	
	/**
	 *	Get which campus does the student belong. Well useful, as certain operations are not available for SRC campus students on PWI.
	 **/
	public function getIsMainCampus() {
		return $this->isMainCampus;
	}
	
	/**
	 * Throw error when user is not authenticated properly
	 **/
	public function authError() {
		return json_encode(array("status" => "false", "error" => "User not authenticated"));
	}
	
	/**
	 * Fetch Student Info
	 */
	public function getInfo() {
		if($this->getAuthStatus()) {
			phpQuery::newDocument($this->home);
			$list = pq('ul.leftnavlinks01');
			$details = array();
			$details["REGNO"] = $this->regno;
			$details["NAME"] =  trim(pq($list)->find('li:eq(0)')->text());
			$details["GROUP"] =  trim(pq($list)->find('li:eq(1)')->text());
			$details["SEM"] =  trim(pq($list)->find('li:eq(3)')->text());		
			return json_encode($details);
		} else {
			return $this->authError();
		}
	} 
		
	/**
	 * Fetch Attendance.
	 */
	public function getAttendance() {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
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
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");
	}

	/**
	 * Fetch Attendace Details.
	 */
	public function getAttendanceDetails() {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
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
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");
	}

	/**
	 * Fetch Internal Marks.
	 */	
	public function getInternalMarks() {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
				/**
				 * Can be worked upon only when there is atleast one entry!
				 */
				$ch = $this->ch;
				curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=22");
				curl_setopt ($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
				$details = curl_exec($ch);
				echo $details;
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");
	}

	/**
	 * Fetch Grades.
	 *
	 *	Update	- The papers are grouped by Semester. 
	 *	Usage	-
	 *			$grades = json_decode($student->getGrades());
	 *			// To get the semester 2 paper details
	 *			foreach($grades[2] as $paper) {
	 *				echo $paper->SUBNAME;	// Get the subject name
	 *				echo $paper->GRADE;		// Get the grade in that paper
	 *			}
	 */	
	public function getGrades() {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
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
					// Storing the values by Semester for easy processing
					$sem = trim(pq($row)->find('td:eq(0)')->text());
					
					$semDetails = array();
					$semDetails['SEM'] = $sem;	// For historic reasons
					$semDetails['DATE'] = preg_replace("/ \/ /"," ",trim(pq($row)->find('td:eq(1)')->text()));
					$semDetails['SUBCODE'] = trim(pq($row)->find('td:eq(2)')->text());
					$semDetails['SUBNAME'] = preg_replace("/&/","and",trim(pq($row)->find('td:eq(3)')->text()));
					$semDetails['CREDIT'] = trim(pq($row)->find('td:eq(4)')->text());
					$semDetails['GRADE'] = trim(pq($row)->find('td:eq(5)')->text());
					
					// Add them to the main details
					$details[$sem][] = $semDetails;
				}
				$rows = pq('table tr:(.tablecontent03)');
				foreach ($rows as $row) {
					$details['CGPA'] = trim(pq($row)->find('td:eq(1)')->text());
				}

				/**
				 * Encode into JSON and return.
				 */
				return json_encode($details);
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");
	}
	

	/**
	 * Fetch CGPA
	 */	
	public function getCGPA() {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
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
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");
	}
	

	/**
	 * Fetch the Timetable.
	 */
	public function getTimeTable() {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
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
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");
	}
	
	/**
	 * Fetch Hostel Details.
	 */
	
	public function getHostelDetails() {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
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
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");		
	}


	/**
	 * Fetch All or Part of the Courses.
	 */
	
	public function getCourses($sem = NULL) {
		if (isset($this->regno) && isset($this->pass)) {
			if($this->getAuthStatus()) {
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
			} else {
				return $this->authError();
			}
		} else die("Register Number or Password not set.");		
	}

        public function getFeeDue() {
        if (isset($this->regno) && isset($this->pass)) {
            if ($this->getAuthStatus()) {
                /**
                 * Get the Fee Due details from PWI.
                 */
                $ch = $this->ch;
                curl_setopt($ch, CURLOPT_URL, "http://webstream.sastra.edu/sastrapwi/resource/StudentDetailsResources.jsp?resourceid=20");
                curl_setopt($ch, CURLOPT_REFERER, "http://webstream.sastra.edu/sastrapwi/usermanager/home.jsp");
                $html = curl_exec($ch);

                phpQuery::newDocument($html);
                pq('tr:first')->remove();
                pq('tr:first')->remove();                

                $rows = pq('table tr');
                $details = array();
                $key = 0;
                foreach ($rows as $row) {
                    $details[$key]["SEM"] = trim(pq($row)->find('td:eq(0)')->text());
                    $details[$key]["INSTITUTION"] = trim(pq($row)->find('td:eq(1)')->text());
                    $details[$key]["PARTICULARS"] = trim(pq($row)->find('td:eq(2)')->text());
                    $details[$key]["DUEAMOUNT"] = trim(pq($row)->find('td:eq(3)')->text());
                    $details[$key]["DUEDATE"] = trim(pq($row)->find('td:eq(4)')->text());
                    $key++;
                }
                /**
                 * Export as JSON.
                 */
                return json_encode($details);
            } else {
                return $this->authError();
            }
        } else
            die("Register Number or Password not set.");
    }

}

?>
