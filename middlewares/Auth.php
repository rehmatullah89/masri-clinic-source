<?php

require __DIR__.'/../classes/JwtHandler.php';
class Auth extends JwtHandler
{
    protected $db;
    protected $headers;
    protected $token;
    public function __construct($db, $headers)
    {
        parent::__construct();
        $this->db = $db;
        $this->headers = $headers;
    }

	public function webURL(){
		if(isset($_SERVER['HTTPS'])){
			$protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
		}
		else{
			$protocol = 'http';
		}
		return $protocol . "://" . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
	}

    public function isAuth()
    {
        if (array_key_exists('Authorization', $this->headers) && !empty(trim($this->headers['Authorization']))) :
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if (isset($this->token[1]) && !empty(trim($this->token[1]))) :
                $data = $this->_jwt_decode_data($this->token[1]);

                if (isset($data['auth']) && isset($data['data']->user_id) && $data['auth']) :
                    $user = $this->fetchUser($data['data']->user_id);
                    return $user;
                else :
                    return null;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else :
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))
        else :
            return null;
        endif;
    }

    protected function fetchUser($user_id)
    {
        try {
            $fetch_user_by_id = "SELECT `first_name`,`last_name`,`email`,`phone`,`dob`,`gender` FROM `users` WHERE `id`=:id";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'success' => 1,
                    'status' => 200,
                    'user' => $row
                ];
            else :
                    return null;
            endif;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function isAdmin($user_id)
    {
        try {
            $fetch_user_by_id = "SELECT `admin` FROM `users` WHERE `id`=:id AND `admin`='Y'";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                return true;
            else :
                    return false;
            endif;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getUserPromotions()
    {
        try {
            $user_id = 0;
            if (array_key_exists('Authorization', $this->headers) && !empty(trim($this->headers['Authorization']))) {
                $this->token = explode(" ", trim($this->headers['Authorization']));
                if (isset($this->token[1]) && !empty(trim($this->token[1]))) {
                    $data = $this->_jwt_decode_data($this->token[1]);

                    if (isset($data['auth']) && isset($data['data']->user_id) && $data['auth']) {
                        $user_id = $data['data']->user_id;
                    }
                }
            }

            $fetch_user_by_id = "SELECT promotions.* FROM `promotions` ORDER BY id ASC";
            /*,`promotion_recipients` WHERE promotions.id=promotion_recipients.promotion_id AND promotion_recipients.user_id=:id*/
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            //$query_stmt->bindValue(':id', $user_id,PDO::PARAM_INT);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                while ($row = $query_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $promotions[] = $row;
                }
                return [
                    'success' => 1,
                    'status' => 200,
                    'promotions' => $promotions
                ];
            else :
                    return [
                    'success' => 1,
                    'status' => 200,
                    'promotions' => []
                    ];
            endif;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getUserAppointments()
    {
        try {
            $user_id = 0;
            if (array_key_exists('Authorization', $this->headers) && !empty(trim($this->headers['Authorization']))) {
                $this->token = explode(" ", trim($this->headers['Authorization']));
                if (isset($this->token[1]) && !empty(trim($this->token[1]))) {
                    $data = $this->_jwt_decode_data($this->token[1]);

                    if (isset($data['auth']) && isset($data['data']->user_id) && $data['auth']) {
                        $user_id = $data['data']->user_id;
                    }
                }
            }

            if ($this->isAdmin($user_id)) {
                $fetch_user_by_id = "SELECT users.first_name, users.last_name, users.email, users.phone, users.dob, users.gender, appointments.*, TIME_FORMAT(appointments.appointment_time, '%H:%i %p') as appointment_time, concat(users.first_name,' ',users.last_name) as doctor_name,
                (case when users.address IS null then '17532 Park Street MELVINDALE MI 48122' ELSE users.address END) as location_id
             FROM `users`,`appointments` WHERE users.id=appointments.user_id order by appointments.appointment_date, appointments.appointment_time";
                $query_stmt = $this->db->prepare($fetch_user_by_id);
            } else {
                $fetch_user_by_id = "SELECT users.first_name, users.last_name, users.email, users.phone, users.dob, users.gender, appointments.*, TIME_FORMAT(appointments.appointment_time, '%H:%i %p') as appointment_time, (case when users.address IS null then '17532 Park Street MELVINDALE MI 48122' ELSE users.address END)  as location_id FROM `users`,`appointments` WHERE users.id=appointments.user_id AND  users.id=:id order by appointments.appointment_date, appointments.appointment_time";

                $query_stmt = $this->db->prepare($fetch_user_by_id);
                $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            }
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                while ($row = $query_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $promotions[] = $row;
                }
                return [
                    'success' => 1,
                    'status' => 200,
                    'appointments' => $promotions
                ];
            else :
                    return [
                    'success' => 1,
                    'status' => 200,
                    'appointments' => []
                    ];
            endif;
        } catch (PDOException $e) {
            return null;
        }
    }
	
	public function logout()
	{
		try {
			if (array_key_exists('Authorization', $this->headers) && !empty(trim($this->headers['Authorization']))) {
					$this->token = explode(" ", trim($this->headers['Authorization']));
					if (isset($this->token[1]) && !empty(trim($this->token[1]))) {
						$data = $this->_jwt_decode_data($this->token[1]);

						if (isset($data['auth']) && isset($data['data']->user_id) && $data['auth']) {
						
							$jwt = new JwtHandler();                    
							$token = $jwt->_jwt_encode_data(
								$this->webURL(),
								array("user_id"=> $data['data']->user_id)
							);

							return $returnData = [
								'success' => 1,
								'message' => 'You have successfully logged out.',
								'token' => $token
							];
						}
					}
			}
		} catch (PDOException $e) {
            return null;
        }
		
	}
}
