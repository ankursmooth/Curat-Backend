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

    	if($this->usertype=="admin"){
    		if($username=="admin"&& $password=="admin")
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
			/*

			this dbdata may be simetimes used by other function.. handle with care
			*/
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

$app->post('/loginDoctor', function(){ });
$app->post('/loginOrganization', function(){ });
$app->post('/forgotDoctor', function(){ $login = new \Login(); $login->forgotPhotographer();});


$app->group('/patient',function () use ($app){
	
	global $authP; 
	global $patient;
	
	$app->post('/getPatient', function(){
			});
	
});

$app->group('/doctor',function () use ($app){
	//authC contains the details of user whose authentication hass been done using basicauth
	global $authC; 
	global $doctor;
	$app->options('/(:name+)', function() use ($app) {
    //...return correct headers...
    
	});
	
	$app->post('/history',function(){
			});
	$app->post('/detailhistory',function(){
		});
	$app->post('/getattachment',function(){
		});
	$app->post('/visit',function(){
			});
	$app->post('/attachment',function(){
			});
	});
$app->group('/organization',function () use ($app){
	
} );

$app->notFound(function () use ($app) {
    $app->redirect('http://www.lnmhacks.pe.hu/notfound.html');
});






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
