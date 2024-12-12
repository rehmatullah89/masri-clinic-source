<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success, $status, $message, $extra = [])
{
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ], $extra);
}

function webURL()
{
    if (isset($_SERVER['HTTPS'])) {
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    } else {
        $protocol = 'http';
    }
    return $protocol . "://" . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
}

// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__.'/classes/Database.php';
require __DIR__.'/classes/JwtHandler.php';

$db_connection = new Database();
$conn = $db_connection->dbConnection();

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT POST
if ($_SERVER["REQUEST_METHOD"] != "POST") :
    $returnData = msg(0, 404, 'Page Not Found!');

//Login/Register Via Apple Id
elseif (isset($data->apple_id)
    && !empty(trim($data->apple_id))
    && isset($data->device_token)
    && !empty(trim($data->device_token))
    ) :
    try {
        $check_apple = "SELECT * FROM `users` WHERE `apple_id`=:apple_id AND device_token=:device_token";
        $check_apple_stmt = $conn->prepare($check_apple);
        $check_apple_stmt->bindValue(':apple_id', $data->apple_id, PDO::PARAM_STR);
        $check_apple_stmt->bindValue(':device_token', $data->device_token, PDO::PARAM_STR);
        $check_apple_stmt->execute();

        if ($check_apple_stmt->rowCount()) :
                //login now.......with apple id
                $jwt = new JwtHandler();
            $token = $jwt->_jwt_encode_data(
                webURL(),
                array("user_id"=> $id)
            );

            $returnData = [
                        'success' => 1,
                        'message' => 'You have successfully login.',
                        'token' => $token,
                        'user' => $check_apple_stmt->fetch(PDO::FETCH_ASSOC)
                ];
        else :
            if (!isset($data->first_name)
                    || !isset($data->last_name)
                    || !isset($data->email)
                    || !isset($data->device_token)
                    || empty(trim($data->first_name))
                    || empty(trim($data->last_name))
                    || empty(trim($data->email))
                    || empty(trim($data->device_token))
                    ) {
                $fields = ['fields' => ['first_name','last_name','email','device_token','apple_id']];
                $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);
            } else {
                $first_name = trim($data->first_name);
                $last_name = trim($data->last_name);
                $email = trim($data->email);
                $password = trim($data->password);
                $phone = trim($data->phone);
                $dob = trim($data->dob);
                $gender = trim($data->gender);
                $device_token = trim($data->device_token);
                $apple_id = trim($data->apple_id);

                $insert_query = "INSERT INTO `users`(`first_name`,`email`,`last_name`,`device_token`,`apple_id`,`admin`) VALUES(:first_name,:email,:last_name,:device_token,:apple_id,'N')";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':first_name', htmlspecialchars(strip_tags($first_name)), PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $insert_stmt->bindValue(':last_name', htmlspecialchars(strip_tags($last_name)), PDO::PARAM_STR);
                $insert_stmt->bindValue(':device_token', htmlspecialchars(strip_tags($device_token)), PDO::PARAM_STR);
                $insert_stmt->bindValue(':apple_id', htmlspecialchars(strip_tags($apple_id)), PDO::PARAM_STR);

                $insert_stmt->execute();

                $id = $conn->lastInsertId();

                $jwt = new JwtHandler();
                $token = $jwt->_jwt_encode_data(
                    webURL(),
                    array("user_id"=> $id)
                );

                $returnData = [
                'success' => 1,
                'message' => 'You have successfully registered.',
                'token' => $token,
                'user' => [
                    'first_name'=>$first_name,
                    'last_name'=>$last_name,
                    'email'=>$email,
                    'phone'=>$phone,
                    'dob'=>$dob,
                    'gender'=>$gender,
                    'device_token'=>$device_token,
                    'apple_id' => $apple_id
                ]
                ];
            }
        endif;
    } catch (PDOException $e) {
        $returnData = msg(0, 500, $e->getMessage());
    }
// CHECKING EMPTY FIELDS
elseif (!isset($data->first_name)
    || !isset($data->last_name)
    || !isset($data->email)
    /*|| !isset($data->phone) */
    || !isset($data->password)
    /*|| !isset($data->dob)*/
    || !isset($data->gender)
    || !isset($data->device_token)
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    || empty(trim($data->email))
    || empty(trim($data->password))
    /*|| empty(trim($data->phone))*/
    /*|| empty(trim($data->dob))*/
    || empty(trim($data->gender))
    || empty(trim($data->device_token))
    ) :
//'phone','dob',
    $fields = ['fields' => ['first_name','last_name','email','password','gender','device_token']];
    $returnData = msg(0, 422, 'Please Fill in all Required Fields!', $fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else :
    $first_name = trim($data->first_name);
    $last_name = trim($data->last_name);
    $email = trim($data->email);
    $password = trim($data->password);
    $phone = trim($data->phone);
    $dob = trim($data->dob);
    $gender = trim($data->gender);
    $device_token = trim($data->device_token);
    $apple_id = trim($data->apple_id);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) :
        $returnData = msg(0, 422, 'Invalid Email Address!');
    elseif (strlen($password) < 8) :
        $returnData = msg(0, 422, 'Your password must be at least 8 characters long!');
    elseif (strlen($first_name) < 3) :
        $returnData = msg(0, 422, 'Your first name must be at least 3 characters long!');
    elseif (strlen($last_name) < 3) :
        $returnData = msg(0, 422, 'Your last name must be at least 3 characters long!');

    /*elseif(strlen($phone) < 10):
        $returnData = msg(0,422,'Your phone must be at least 10 characters long!'); */
    else :
        try {
            if ($phone != "") {
                $check_email = "SELECT `id` FROM `users` WHERE `email`=:email OR `phone`=:phone";
            } else {
                $check_email = "SELECT `id` FROM `users` WHERE `email`=:email";
            }

            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email, PDO::PARAM_STR);
            if ($phone != "") {
                $check_email_stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
            }
            $check_email_stmt->execute();

            /*$check_phone = "SELECT `phone` FROM `users` WHERE `phone`=:phone";
            $check_phone_stmt = $conn->prepare($check_phone);
            $check_phone_stmt->bindValue(':phone', $phone,PDO::PARAM_STR);
            $check_phone_stmt->execute();*/

            if ($check_email_stmt->rowCount()) :
                $returnData = msg(0, 422, 'This E-mail or Phone already in use!');
            else :
                    $insert_query = "INSERT INTO `users`(`first_name`,`email`,`password`,`last_name`,`phone`,`dob`,`gender`,`device_token`,`apple_id`,`admin`) VALUES(:first_name,:email,:password,:last_name,:phone,:dob,:gender,:device_token,:apple_id,'N')";

                    $insert_stmt = $conn->prepare($insert_query);

            // DATA BINDING
                    $insert_stmt->bindValue(':first_name', htmlspecialchars(strip_tags($first_name)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':last_name', htmlspecialchars(strip_tags($last_name)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':phone', htmlspecialchars(strip_tags($phone)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':dob', $dob, PDO::PARAM_STR);
                    $insert_stmt->bindValue(':gender', htmlspecialchars(strip_tags($gender)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':device_token', htmlspecialchars(strip_tags($device_token)), PDO::PARAM_STR);
                    $insert_stmt->bindValue(':apple_id', htmlspecialchars(strip_tags($apple_id)), PDO::PARAM_STR);

                    $insert_stmt->execute();

                    $id = $conn->lastInsertId();

                    $jwt = new JwtHandler();
                    $token = $jwt->_jwt_encode_data(
                        webURL(),
                        array("user_id"=> $id)
                    );

                    $returnData = [
                        'success' => 1,
                        'message' => 'You have successfully registered.',
                        'token' => $token,
                        'user' => [
                                    'first_name'=>$first_name,
                                    'last_name'=>$last_name,
                                    'email'=>$email,
                                    'phone'=>$phone,
                                    'dob'=>$dob,
                                    'gender'=>$gender,
                                    'device_token'=>$device_token,
                                    'apple_id' => $apple_id
                        ]
                    ];
            //$returnData = msg(1,201,'You have successfully registered.');
            endif;
        } catch (PDOException $e) {
            $returnData = msg(0, 500, $e->getMessage());
        }
    endif;
endif;

echo json_encode($returnData);
