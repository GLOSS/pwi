{PWI}
=====
A fast and a lightweight php-sdk for SASTRA's Parent Web Interface (http://webstream.sastra.edu/sastrapwi/).

Synopsis
--------
Set the two parameters(regno and pass) and literally get anything that you need, in a neat JSON object.

Installation/Usage
------------------
    <?php
        include (pwi/pwi.php)
        ..
    	..
    ?>

Resources
---------
* View Source on GitHub (https://github.com/aarvay/pwi)
* Report Issues on GitHub (https://github.com/aarvay/pwi/issues)
* Read the API docs (http://aarvay.in/pwi/docs/)

A Small Example
---------------
1.Below is an example to fetch the Attendance of a student

    <?php
        include('pwi/pwi.php');			
	
        $student = new PWI(array(
	    'regno' => 'xxxxxxxxx',
	    'pass' => 'ddmmyyyy',
	));
		
	echo $student->getAttendance();
    ?>

Its as simple as that. And here is a sample output to expect from the above.
		
    {"0":{"SUBCODE":" BCSCCS501R01","SUBNAME":" DESIGN AND ANALYSIS OF ALGORITHMS","TOTAL":" 2","PRESENT":" 0","ABSENT":" 2","%":" 0"},"1":{"SUBCODE":" BCSCCS503R01","SUBNAME":" DATABASE MANAGEMENT SYSTEMS","TOTAL":" 6","PRESENT":" 0","ABSENT":" 6","%":" 0"},"2":{"SUBCODE":" BCSDCS502","SUBNAME":" PERVASIVE COMPUTING","TOTAL":" 3","PRESENT":" 2","ABSENT":" 1","%":" 67"},"%":" 18.18 "}		

Note on Patches/Pull Requests
-----------------------------
1. Fork the project.
2. Create a topic branch.
3. Implement your feature or bug fix.
4. Add documentation for your feature or bug fix.
5. Add specs for your feature or bug fix.
7. Commit and push your changes.

Copyright
---------
Copyright (c) 2011 {PWI}, Vignesh Rajagopalan.
See [LICENSE](http://aarvay.in/pwi/LICENSE) for details.
