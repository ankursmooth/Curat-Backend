<?php
/*
	* db.php contains database connection information

*/
	//header("Access-Control-Allow-Origin: *");
	
	
	
require 'db.php'; //  getDB connection


require 'Slim/Slim.php'; // Slim micro framework

//use \Slim\Extras\Middleware\HttpBasicAuth
\Slim\Slim::registerAutoloader();

/**
 * MyAuthClass used for Basic Auth
 * saves all data of logged in user to $userdata
 *
*/
class MyAuthClass implements \Slim\Middleware\AuthCheckerInterface
{

    public $usertype;
    public $username;
    public $userId;
    public $userdata;
	public function __construct($usertype) {
		$this->usertype = $usertype;
	}
    // only function required by the interface
    public function checkCredentials($username, $password)  {
  //   	if($this->app->request->isOptions())
		// return true;

    	if($this->usertype=="patient"){
    		if($username=="patient"&& $password=="demouser")
    			return true;
    		return false;
    	}
    	$this->username=$username;
    	$password = sha1($password);
    	$sql = "SELECT * FROM ".$this->usertype." where `email` =? and `password`=?";
	    $dbdata=null;
	    try {
		    $db = getDB();
		    $stmt = $db->prepare($sql);
		    $stmt->execute(array($username, $password));
			$dbdata = $stmt->fetchObject();
			
			$db = null;
		}
		catch(PDOException $e) {
	        $response["status"]=501;
	        $response["message"]="database: ".$e->getMessage();
	        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
	        echo json_encode($response);
	        return false;
	        //echo '{"error":{"text":'. $e->getMessage() .'}}';
	    }
	   
    	if($dbdata!=null){
    		$this->userId= $dbdata->email;
    		$this->userdata= $dbdata;
    		return true;
    	}
    	return false;
        // interact with your own auth system
        // do some stuff and return true if authorised, false if not    
    }
}
// Instantiate Slim aplication
$app = new \Slim\Slim();
require ('Slim/Middleware/CorsSlim.php');

$authP = new MyAuthCLass('patient');
$authC = new MyAuthCLass('doctor');
$authA = new MyAuthCLass('organization');
$authP->userId=-1;
$authC->userId=-1;
$authA->userId=-1;
$patient=NULL;
$doctor=NULL;
$organization = NULL;
$app->add(new \CorsSlim\CorsSlim(array(
    "origin" => array("*","http://localhost"),
    "exposeHeaders" => array("X-My-Custom-Header", "X-Another-Custom-Header"),
    "maxAge" => 1728000,
    "allowCredentials" => True,
    "allowHeaders" => array("X-PINGOTHER,Authorization")
    )));
//modified HttpBasicAuth.php so that it skips verification of OPTIONS request
$app->add(new \Slim\Middleware\HttpBasicAuth($authP, array(
    'path' => '/Curat-Backend/patient', // optional, defaults to '/'
    'realm' => 'Protected API' // optional, defaults to 'Protected Area'
)));
$app->add(new \Slim\Middleware\HttpBasicAuth($authC, array(
    'path' => '/Curat-Backend/doctor', // optional, defaults to '/'
    'realm' => 'Protected API' // optional, defaults to 'Protected Area'
)));
$app->add(new \Slim\Middleware\HttpBasicAuth($authA, array(
    'path' => '/Curat-Backend/organization', // optional, defaults to '/'
    'realm' => 'Protected API' // optional, defaults to 'Protected Area'
)));

/**
 * Slim application routes
 */

$app->post('/loginDoctor', function(){ 
	$app = \Slim\Slim::getInstance();
	$response=array();
	$allPostVars = $app->request->post();

	$check_array = array('email','password');
	$check_diff=array_diff($check_array, array_keys($allPostVars));
	if ($check_diff){
	    $response["status"]=400;
	    $notPresent= implode(", ", $check_diff);
	    $response["message"]= $notPresent." not set";
	    echo json_encode($response);
	    return;
	}
	$allPostVars['password'] = sha1($allPostVars['password']);
	array_walk_recursive($allPostVars, function (&$val) 
	{ 
	    $val = trim($val); 
	});
	$sql = "SELECT * from doctor where email=:email and password =:password";
    $dbdata=null;
    try {
	    $db = getDB();
	    $stmt = $db->prepare($sql);
	    $result=$stmt->execute($allPostVars);
		
		if($result){
			
			$response["doctor"]=array();
			$response["patients"]=array();
		    $response["Pnumber"]=0;
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$sql2 = "SELECT patientuserid as uid from doctorpatients where doctoremail=:email ";
				unset($row['password']);
	            array_push($response["doctor"], $row);
	            $db2 = getDB();
	    		$stmt2 = $db2->prepare($sql2);
	    		$result2=$stmt2->execute(array ('email' => $allPostVars['email']));
				if($result2){
					while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
						$sql3 = "SELECT * from patient where UserId =:patientuserid";
						$db3 = getDB();
			    		$stmt3 = $db3->prepare($sql3);
			    		$result3=$stmt3->execute(array ('patientuserid' => $row2['uid']));
			    		if($result3){
			    			if($row3 = $stmt3->fetch(PDO::FETCH_ASSOC)){
			    				unset($row3['Code']);
			    				array_push($response["patients"],$row3);
			    				$response["Pnumber"] = $response["Pnumber"] +1;
			    			}
			    		}
						
					}
				}
	            $response["status"]=200;
	        	$response["message"]="Success";
			}
	        else{
	        	$response["status"]=404;
	        	$response["message"]="Invalid Credentials";
	        }
	       	$db = null;
	        echo json_encode($response);
			
		}
		$db = null;
	}
	catch(PDOException $e) {
        $response["status"]=501;
        $response["message"]="Server Database Error ".$e->getMessage();
        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
        //$
        echo json_encode($response);
    }

});
$app->post('/loginOrganization', function(){  
	$app = \Slim\Slim::getInstance();
	$response=array();
	$allPostVars = $app->request->post();

	$check_array = array('email','password');
	$check_diff=array_diff($check_array, array_keys($allPostVars));
	if ($check_diff){
	    $response["status"]=400;
	    $notPresent= implode(", ", $check_diff);
	    $response["message"]= $notPresent." not set";
	    echo json_encode($response);
	    return;
	}
	$allPostVars['password'] = sha1($allPostVars['password']);
	array_walk_recursive($allPostVars, function (&$val) 
	{ 
	    $val = trim($val); 
	});
	$sql = "SELECT * from organization where email=:email and password =:password";
    $dbdata=null;
    try {
	    $db = getDB();
	    $stmt = $db->prepare($sql);
	    $result=$stmt->execute($allPostVars);
		
		if($result){
			
			$response["organization"]=array();
			$response["patients"]=array();
		    $response["Pnumber"]=0;
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$sql2 = "SELECT patientuserid as uid from organizationpatients where orgemail=:email ";
				unset($row['password']);
	            array_push($response["organization"], $row);
	            $db2 = getDB();
	    		$stmt2 = $db2->prepare($sql2);
	    		$result2=$stmt2->execute(array ('email' => $allPostVars['email']));
				if($result2){
					while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
						$sql3 = "SELECT * from patient where UserId =:patientuserid";
						$db3 = getDB();
			    		$stmt3 = $db3->prepare($sql3);
			    		$result3=$stmt3->execute(array ('patientuserid' => $row2['uid']));
			    		if($result3){
			    			if($row3 = $stmt3->fetch(PDO::FETCH_ASSOC)){
			    				unset($row3['Code']);
			    				array_push($response["patients"],$row3);
			    				$response["Pnumber"] = $response["Pnumber"] +1;
			    			}
			    		}
						
					}
				}
	            $response["status"]=200;
	        	$response["message"]="Success";
			}
	        else{
	        	$response["status"]=404;
	        	$response["message"]="Invalid Credentials";
	        }
	       	$db = null;
	        echo json_encode($response);
			
		}
		$db = null;
	}
	catch(PDOException $e) {
        $response["status"]=501;
        $response["message"]="Server Database Error ".$e->getMessage();
        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
        //$
        echo json_encode($response);
    }

});



$app->group('/patient',function () use ($app){
	
	global $authP; 
	
	
	$app->post('/getPatient', 'getPatient');
	$app->post('/getHistory', 'getHistory');
	$app->post('/getHistoryDetail', 'getHistoryDetail');
	$app->post('/detailHistory','detailHistory');
	
});

$app->group('/doctor',function () use ($app){
	//authC contains the details of user whose authentication hass been done using basicauth
	global $authC; 
	
	$app->options('/(:name+)', function() use ($app) {
    //...return correct headers...
    
	});
	$app->post('/getPatient', 'getPatient');
	$app->post('/addPatient', function(){
		global $authC;
		$app = \Slim\Slim::getInstance();
		$response=array();
		$allPostVars = $app->request->post();
		array_walk_recursive($allPostVars, function (&$val) 
		{ 
		    $val = trim($val); 
		});
		$check_array = array('patientID');
		$check_diff=array_diff($check_array, array_keys($allPostVars));
		if ($check_diff){
		    $response["status"]=400;
		    $notPresent= implode(", ", $check_diff);
		    $response["message"]= $notPresent." not set";
		    echo json_encode($response);
		    return;
		}
		$sql = "SELECT * from patient where UserId =:patientID";
	    $dbdata=null;
	    try {
		    $db = getDB();
		    $stmt = $db->prepare($sql);
		    $result=$stmt->execute($allPostVars);
			
			if($result){
				$result = NULL;
				$db = getDB();
			    $stmt = $db->prepare("REPLACE into doctorpatients (`doctoremail`,`patientuserid`) VALUES (:email,:patientID) ");
			    $result=$stmt->execute(array('email' => $authC->userId, 'patientID' => $allPostVars['patientID']));
			    if($result){
			        $response["status"]=200;
			        $response["message"]="Done";
		       	}
		       	else{
		       		$response["status"]=501;
			        $response["message"]="Server error";
		       	}
		       
		       	$db = null;
		        echo json_encode($response);
				
			}
			else{
				$response["status"]=400;
			    $response["message"]="invalid patient ID";
			}
			$db = null;
		}
		catch(PDOException $e) {
	        $response["status"]=501;
	        $response["message"]="Server Database Error ".$e->getMessage();
	        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
	        //$
	        echo json_encode($response);
	    }
		});
	$app->get('/patientlist','patientlist');
	$app->post('/getHistory','getHistory');
	$app->post('/detailHistory','detailHistory');
	$app->post('/getHistoryDetail', 'getHistoryDetail');
	$app->post('/visit',function(){
		global $authC;
		$app = \Slim\Slim::getInstance();
		$response=array();
		$allPostVars = $app->request->post();
		array_walk_recursive($allPostVars, function (&$val) 
		{ 
		    $val = trim($val); 
		});
		$check_array = array('patientID','Keypoints', 'Details');
		$check_diff=array_diff($check_array, array_keys($allPostVars));
		if ($check_diff){
		    $response["status"]=400;
		    $notPresent= implode(", ", $check_diff);
		    $response["message"]= $notPresent." not set";
		    echo json_encode($response);
		    return;
		}
		$sql = "SELECT * from patient where UserId =:patientID";
	    $dbdata=null;
	    try {
		    $db = getDB();
		    $stmt = $db->prepare($sql);
		    $result=$stmt->execute(array('patientID' => $allPostVars['patientID']));
			
			if($result){
				$result = NULL;
				$db = getDB();
				$original = new DateTime("now", new DateTimeZone('UTC')	);
				$timezoneName = timezone_name_from_abbr("", 5.5*3600, false);
				$modified = $original->setTimezone(new DateTimezone($timezoneName));
				
				$dt = $original->format('Y-m-d H:i:s');
			    $stmt = $db->prepare("INSERT into patienthistory (`UserId`,`DoctorName`, `Keypoints`, `Details`, `DateTime`) VALUES (:patientID,:DoctorName, :Keypoints, :Details, :dt) ");
			    $result=$stmt->execute(array('DoctorName' => $authC->userdata->Name, 'patientID' => $allPostVars['patientID'], 'Keypoints' => $allPostVars['Keypoints'], 'Details' => $allPostVars['Details'], 'dt'=> $dt));
			    if($result){
			        $response["status"]=200;
			        $response["message"]="Done";
			        $response["DateTime"] = $dt;
		       	}
		       	else{
		       		$response["status"]=501;
			        $response["message"]="Server error";
		       	}
		       
		       	$db = null;
		        echo json_encode($response);
				
			}
			else{
				$response["status"]=400;
			    $response["message"]="invalid patient ID";
			}
			$db = null;
		}
		catch(PDOException $e) {
	        $response["status"]=501;
	        $response["message"]="Server Database Error ".$e->getMessage();
	        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
	        //$
	        echo json_encode($response);
	    }
		});
	$app->post('/attachment',function(){
		if (!isset($_FILES['image'])) {
	        $response['status']=400;
        	$response['message'] = "some parameter not set";
        	
        	echo json_encode($response);
	        return;
    	}
		$app = \Slim\Slim::getInstance();
		$response=array();
		$allPostVars = $app->request->post();
		foreach ($allPostVars as $key => $value) {
			# code...
			$response[$key] = $value;
		}
	    
	    $imgs = NULL;
	    //echo "something";
	    $files = $_FILES['image'];
	    $patientID = $_POST['patientID'];
	    $dt = $_POST['DateTime'];

	    $response=array();
        $uploaded =0;
        $name = md5($patientID.$dt);
        
        if (move_uploaded_file($files['tmp_name'], 'attachments/' . $name.".jpg") === true) {
            $imgs = array('url' => '/attachments/' . $name.".jpg");
            $uploaded=1;
        }
        if($uploaded==1){
        	$response['status']=200;
        	$response['url'] = $imgs['url'];
        	$response['message'] = "uploaded";
        	$sql = "UPDATE patienthistory SET `Attachments` =:urlimage , `HasAttachments` =:no where UserId= :patientID and DateTime = :dt";
        	try {
			    $db = getDB();
			    $stmt = $db->prepare($sql);
			    $result=$stmt->execute(array('patientID' => $patientID, 'dt' => $dt, 'urlimage' => $imgs['url'], 'no'=>'1'));
				
				if($result){
					
					
				}
				else{
					$response["status"]=400;
				    $response["message"]="invalid patient ID";
				    echo json_encode($response);
				    return;
				}
				$db = null;
			}
			catch(PDOException $e) {
		        $response["status"]=501;
		        $response["message"]="Server Database Error ".$e->getMessage();
		        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
		        //$
		        echo json_encode($response);
		        return;
		    }	
        }
        else{
        	$response['status']=400;
        	$response['message'] = " not uploaded";
        }

		echo json_encode($response);
		});
	});
$app->group('/organization',function () use ($app){
	$app->post('/getPatient', 'getPatient');
	$app->post('/getHistory', 'getHistory');
	$app->post('/getHistoryDetail', 'getHistoryDetail');
	$app->post('/addPatient', function(){
		global $authA;
		$app = \Slim\Slim::getInstance();
		$response=array();
		$allPostVars = $app->request->post();
		array_walk_recursive($allPostVars, function (&$val) 
		{ 
		    $val = trim($val); 
		});
		$check_array = array('patientID');
		$check_diff=array_diff($check_array, array_keys($allPostVars));
		if ($check_diff){
		    $response["status"]=400;
		    $notPresent= implode(", ", $check_diff);
		    $response["message"]= $notPresent." not set";
		    echo json_encode($response);
		    return;
		}
		$sql = "SELECT * from patient where UserId =:patientID";
	    $dbdata=null;
	    try {
		    $db = getDB();
		    $stmt = $db->prepare($sql);
		    $result=$stmt->execute($allPostVars);
		    
			if($result){
				$result = NULL;
				$db = getDB();
			    $stmt = $db->prepare("REPLACE into organizationpatients (`orgemail`,`patientuserid`) VALUES (:email,:patientID) ");
			    $result=$stmt->execute(array('email' => $authA->userId, 'patientID' => $allPostVars['patientID']));
			    if($result){
			        $response["status"]=200;
			        $response["message"]="Done";
		       	}
		       	else{
		       		$response["status"]=501;
			        $response["message"]="Server error";
		       	}
		       
		       	$db = null;
		        echo json_encode($response);
				
			}
			else{
				$response["status"]=400;
			    $response["message"]="invalid patient ID";
			}
			$db = null;
		}
		catch(PDOException $e) {
	        $response["status"]=501;
	        $response["message"]="Server Database Error ".$e->getMessage();
	        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
	        //$
	        echo json_encode($response);
	    }
	});
	$app->get('/patientlist','patientlist');
} );

$app->notFound(function () use ($app) {
    $app->redirect('http://www.lnmhacks.pe.hu/notfound.html');
});

function getHistory(){
	$app = \Slim\Slim::getInstance();
	$response=array();
	$allPostVars = $app->request->post();
	array_walk_recursive($allPostVars, function (&$val) 
	{ 
	    $val = trim($val); 
	});
	$check_array = array('qrstring');
	$check_diff=array_diff($check_array, array_keys($allPostVars));
	if ($check_diff){
	    $response["status"]=400;
	    $notPresent= implode(", ", $check_diff);
	    $response["message"]= $notPresent." not set";
	    echo json_encode($response);
	    return;
	}
	$sql = "SELECT * from patienthistory where UserId =:qrstring";
    $dbdata=null;
    try {
	    $db = getDB();
	    $stmt = $db->prepare($sql);
	    $result=$stmt->execute($allPostVars);
		
		if($result){
			
			$response["chat"]=array();
		    $response["number"]=0;
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$response["number"]=$response["number"]+1;
				unset($row['Attachments']);
				unset($row['Details']);
				unset($row['UserId']);
	            array_push($response["chat"], $row);
			}
	        $response["status"]=200;
	        $response["message"]="All Visits";
	       	
	       	if($response["number"]==0){
	       		$response["message"]="No Visits";
	       	}
	       	$db = null;
	        echo json_encode($response);
			
		}
		$db = null;
	}
	catch(PDOException $e) {
        $response["status"]=501;
        $response["message"]="Server Database Error ".$e->getMessage();
        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
        //$
        echo json_encode($response);
    }
}

function detailHistory(){

	$app = \Slim\Slim::getInstance();
	$response=array();
	$allPostVars = $app->request->post();
	array_walk_recursive($allPostVars, function (&$val) 
	{ 
	    $val = trim($val); 
	});
	$check_array = array('patientID','DateTime');
	$check_diff=array_diff($check_array, array_keys($allPostVars));
	if ($check_diff){
	    $response["status"]=400;
	    $notPresent= implode(", ", $check_diff);
	    $response["message"]= $notPresent." not set";
	    echo json_encode($response);
	    return;
	}
	$sql = "SELECT * from patienthistory where UserId =:patientID and DateTime =:DateTime";
    $dbdata=null;

    try {
	    $db = getDB();
	    $stmt = $db->prepare($sql);
	    $result=$stmt->execute($allPostVars);
		
		if($result){
			
			
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				unset($row['UserId']);
				
	            foreach ($row as $key => $value) {
	            	# code...
	            	$response[$key]=$value;
	            }
	            $response["status"]=200;
	        	$response["message"]="Specific Visits";
			}
			else{
				$response["status"]=400;
	        	$response["message"]="Not available";	
			}
	        
	       	
	       	
	       	$db = null;
	        echo json_encode($response);
			
		}
		$db = null;
	}
	catch(PDOException $e) {
        $response["status"]=501;
        $response["message"]="Server Database Error ".$e->getMessage();
        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
        //$
        echo json_encode($response);
    }

}

function getPatient(){
	$app = \Slim\Slim::getInstance();
	$response=array();
	$allPostVars = $app->request->post();
	array_walk_recursive($allPostVars, function (&$val) 
	{ 
	    $val = trim($val); 
	});
	$check_array = array('qrstring');
	$check_diff=array_diff($check_array, array_keys($allPostVars));
	if ($check_diff){
	    $response["status"]=400;
	    $notPresent= implode(", ", $check_diff);
	    $response["message"]= $notPresent." not set";
	    echo json_encode($response);
	    return;
	}
	$sql = "SELECT * from patient where UserId =:qrstring";
    $dbdata=null;
    try {
	    $db = getDB();
	    $stmt = $db->prepare($sql);
	    $result=$stmt->execute($allPostVars);
		
		if($result){
			$response["status"]=200;
			$response["message"]="correct qr string";
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				foreach ($row as $key => $value) {
	            	# code...
	            	$response[$key]=$value;
	            }
	            unset($response["UserId"]);
	            unset($response["Code"]);
			}
			else{
				$response["status"]=400;
				$response["message"]="incorrect qr string";
			}
			echo json_encode($response);
		}
		$db = null;
	}
	catch(PDOException $e) {
        $response["status"]=501;
        $response["message"]="Server Database Error ".$e->getMessage();
        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
        //$
        echo json_encode($response);
    }
}


function getHistoryDetail(){
	$app = \Slim\Slim::getInstance();
	$response=array();
	$allPostVars = $app->request->post();
	array_walk_recursive($allPostVars, function (&$val) 
	{ 
	    $val = trim($val); 
	});
	$check_array = array('qrstring');
	$check_diff=array_diff($check_array, array_keys($allPostVars));
	if ($check_diff){
	    $response["status"]=400;
	    $notPresent= implode(", ", $check_diff);
	    $response["message"]= $notPresent." not set";
	    echo json_encode($response);
	    return;
	}
	$sql = "SELECT * from patienthistory where UserId =:qrstring";
    $dbdata=null;
    try {
	    $db = getDB();
	    $stmt = $db->prepare($sql);
	    $result=$stmt->execute($allPostVars);
		
		if($result){
			
			$response["chat"]=array();
		    $response["number"]=0;
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$response["number"]=$response["number"]+1;
				unset($row['UserId']);
	            array_push($response["chat"], $row);
			}
	        $response["status"]=200;
	        $response["message"]="All Visits";
	       	
	       	if($response["number"]==0){
	       		$response["message"]="No Visits";
	       	}
	       	$db = null;
	        echo json_encode($response);
			
		}
		$db = null;
	}
	catch(PDOException $e) {
        $response["status"]=501;
        $response["message"]="Server Database Error ".$e->getMessage();
        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
        //$
        echo json_encode($response);
    }
}

function patientlist(){
	$app = \Slim\Slim::getInstance();
	$response=array();
	$authvar=null;
	global $authC;
	global $authA;
	if($authC->userId!=-1){
		$authvar=$authC;
		$sql = "SELECT patientuserid as uid FROM `doctorpatients` WHERE `doctoremail`=:email ";
	}
	else{
		$authvar=$authA;
		$sql = "SELECT patientuserid as uid FROM `organizationpatients` WHERE `orgemail`=:email ";
	}
	$allPostVars=array();

	$allPostVars['email']=$authvar->userId;
	try {
	    $db = getDB();
	    $stmt = $db->prepare($sql);
	    $result=$stmt->execute($allPostVars);
		
		$response["patients"]=array();
		$response["Pnumber"]=0; 
		
		if($result){
			while ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$sql3 = "SELECT * from patient where UserId =:patientuserid";
				$db3 = getDB();
	    		$stmt3 = $db3->prepare($sql3);
	    		$result3=$stmt3->execute(array ('patientuserid' => $row2['uid']));
	    		if($result3){
	    			if($row3 = $stmt3->fetch(PDO::FETCH_ASSOC)){
	    				unset($row3['Code']);
	    				array_push($response["patients"],$row3);
	    				$response["Pnumber"] = $response["Pnumber"] +1;
	    			}
	    		}
				
			}	
		}
		else{
			$response["status"]=400;
			$response["message"]="details not match with db";
			echo json_encode($response);
        	return;
		}
		$response["status"]=200;
		$response["message"]="done";
        
        
       	
       	$db = null;
        echo json_encode($response);
        return;
	}
	catch(PDOException $e) {
        $response["status"]=501;
        $response["message"]="Server Database Error ";
        $response["usermessage"]="Oops! Our elves are working to fix the issue.";
        //$e->getMessage()
        echo json_encode($response);
    }

}
/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
try {
   $app->run();
} catch (Exception $e) {
    echo 'Caught exception:  ',  $e->getMessage(), "\n";
}
