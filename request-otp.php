<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

function sendOTPEmail($email){
    
    $subject = "Masri Clinic- Change Password OTP";
    
    $randomOTP = random_int(100000, 999999);	
     
    $message = $randomOTP.' is your Otp to change your masri clinic password, Please revert back if you did not make this request.';            

    $header = "From:rehmat@ssasoft.com \r\n";
    //$header .= 'Cc: rehmat@ssasoft.com' . "\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-type: text/html\r\n";
    mail ($email,$subject,$message,$header);              
 
}

require __DIR__.'/classes/Database.php';
require __DIR__.'/classes/JwtHandler.php';

$db_connection = new Database();
$conn = $db_connection->dbConnection();

$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT EQUAL TO POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($data->username) 
    || empty(trim($data->username))
    ):

    $fields = ['fields' => ['username']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $username = trim($data->username);

    // CHECKING THE EMAIL FORMAT (IF INVALID FORMAT)
    if(!filter_var($username, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,422,'Invalid Email Address!');

    // THE USER IS ABLE TO PERFORM THE LOGIN ACTION
    else:
        try{
            
            $fetch_user_by_email = "SELECT * FROM `users` WHERE `email`=:username";
            $query_stmt = $conn->prepare($fetch_user_by_email);
            $query_stmt->bindValue(':username', $username,PDO::PARAM_STR);
            $query_stmt->execute();

            // IF THE USER IS FOUNDED BY EMAIL
            if($query_stmt->rowCount()):                
                //send email          
                sendOTPEmail($username);

                $returnData = [
                    'success' => 1,
                    'response'=> 200,
                    'message' => 'A 6 digit OTP pin has been sent to your specified email.'
                ];
            // IF THE USER IS NOT FOUNDED BY EMAIL THEN SHOW THE FOLLOWING ERROR
            else:
                $returnData = msg(0,422,'Invalid User Name!');
            endif;
        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }

    endif;

endif;

echo json_encode($returnData);