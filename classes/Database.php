<?php
class Database{
    
    private $db_host = 'localhost';
    private $db_name = 'masri_clinic';
    private $db_username = 'masri_user';
    private $db_password = '0262WU]cF}Oq';
    
    public function dbConnection(){
        
        try{
            $conn = new PDO('mysql:host='.$this->db_host.';dbname='.$this->db_name,$this->db_username,$this->db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch(PDOException $e){
            echo "Connection error ".$e->getMessage(); 
            exit;
        }
          
    }
}