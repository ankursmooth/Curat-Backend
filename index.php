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
    		$this->userId= $dbdata->id;
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
    'path' => '/eg-api/organization', // optional, defaults to '/'
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
$app->post('/loginOrganization', function(){ });



$app->group('/patient',function () use ($app){
	
	global $authP; 
	
	
	$app->post('/getPatient', 'getPatient');
	$app->post('/getHistory', 'getHistory');
	
});

$app->group('/doctor',function () use ($app){
	//authC contains the details of user whose authentication hass been done using basicauth
	global $authC; 
	
	$app->options('/(:name+)', function() use ($app) {
    //...return correct headers...
    
	});
	
	$app->post('/getHistory','getHistory');
//	$app->post('/detailHistory','detailHistory');
	$app->post('/getattachment',function(){
		});
	$app->post('/visit',function(){
			});
	$app->post('/attachment',function(){
			});
	});
$app->group('/organization',function () use ($app){
	$app->post('/getPatient', 'getPatient');
	$app->post('/getHistory', 'getHistory');
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
