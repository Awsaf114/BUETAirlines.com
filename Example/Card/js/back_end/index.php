<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();


$user_id = NULL;


function authenticate(\Slim\Route $route)
{
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}



$app->post('/uploadquestion', function() use ($app)  {
    
	
	$username=$app->request->post('username');
	$question=$app->request->post('question');
	$category=$app->request->post('category');
	$notifications=$app->request->post('notifications');
	$imagecount=$app->request->post('imagecount');
	$imagenames="";
	
	$image=$username;
	for($i=1;$i<=$imagecount;$i++)
	{
	  $image=$username.$i;
	  $imgmap= $app->request->post($image);
	  $path=$image.".png";
       
		while(file_exists($path))
           {
              $image=$image."1";
              $path=$image.".png";
              
           }
		   
		   $imagenames=$imagenames.$path;
		   $imagenames=$imagenames.",";
		   

    file_put_contents($path,base64_decode($imgmap));
	
	}
	
	
	

  $conn = new mysqli("localhost", "root", "aquarium201", "online_sohopathi");
  $strings="INSERT INTO questions(username,question,category,notification,image) VALUES (" . "'". $username . "'". "," . "'". $question . "'". "," . "'". $category. "'" ."," ."'" . $notifications . "'" . "," . "'". $imagenames. "'" . ")";
  $str= "INSERT INTO questions(username,question,category,notification,image) VALUES ( ";
  
  
  $result = $conn->query($strings);
  
  echoRespnse(201,$strings);  
	
	
   
  
     
});

$app->get('/viewallquestions', function() use ($app)  {
	
$conn = new mysqli("localhost", "root", "aquarium201", "online_sohopathi");
  $strings="SELECT * FROM questions order by id desc limit 10";
  $result = $conn->prepare($strings);
       
        
  $result->execute();
  $result->bind_result($id,$username,$question,$category,$notification,$anonymous,$image,$upvote,$downvote);
  $posts = array();
  
  while($result->fetch()) {
           
           $tmp = array();
           
           
           
           $tmp["id"] = $id;
           $tmp["username"] = $username;
           $tmp["question"] = $question;
           $tmp["category"] = $category;
		   $tmp["anonymous"] = $anonymous;
		   $tmp["image"] = $image;
		   $tmp["upvote"] = $upvote;
		   $tmp["downvote"] = $downvote;
		   
		   
           array_push($posts, $tmp);
       }
	   $result->close();
        
        
        
  echoRespnse(201,$posts);  
	
	
});




$app->get('/notesfull/:id', function ($id) {

    $response = array();
    $db = new DbHandler();

    // fetching all user tasks
    $result = $db->getAllUserNotsfull($id);

    $response["error"] = false;
    $response["notices"] = array();
    $response["notices"] = $result;

    // looping through result and preparing tasks array
   // while ($task = $result->fetch_assoc()) {
     //   $tmp = array();
      //  $tmp["name"] = $task["name"];
      //  $tmp["username"] = $task["username"];
      //  $tmp["location"] = $task["location"];
      //  $tmp["bigimage"] = $task["bigimage"];
      //  $tmp["age"] = $task["age"];
     //   $tmp["gender"] = $task["gender"];
      //  $tmp["id"] = $task["id"];
      //  $tmp["date of incident"] = $task["date"];

      //  $tmp["occupation"] = $task["occupation"];
      //  $tmp["appearance"] = $task["appearance"];
      //  $tmp["contact"] = $task["contact"];
       // $tmp["description"] = $task["addition"];
      //  array_push($response["notices"], $tmp);
  //   }

    echoRespnse(200, $response);
});




function verifyRequiredParams($required_fields)
{
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}


function echoRespnse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>