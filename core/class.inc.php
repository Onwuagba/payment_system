<?php

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  $data = strip_tags($data);
  return $data;
}

class Connect {
    /*
     * Register New Employee
     *
     * @param $id, $name, $email, $accountname, $accountnumber, $bank, $date
     * @return ID
     * */

    public function Register($id, $name, $email, $amount, $accountnumber, $bank, $date) { 
        try {
            $options = [
                'cost' => 12
            ];
            $conn = DB();
            $sql = "INSERT INTO ps_employee(`id`, `name`, `email`, `salary`, `account_number`, `bank`, `date`) VALUES (:id,:name,:email,:amount,:accountnumber,:bank,:date_added)"; 
            $query = $conn->prepare($sql);         
            $query->bindParam(":id", $id, \PDO::PARAM_INT);  
            $query->bindParam(":name", $name, \PDO::PARAM_STR);
            $query->bindParam(":email", $email, \PDO::PARAM_STR);
            $query->bindParam(":amount", $amount, \PDO::PARAM_INT);
            $query->bindParam(":accountnumber", $accountnumber, \PDO::PARAM_INT);
            $query->bindParam(":bank", $bank, \PDO::PARAM_STR);
            $query->bindParam(":date_added", $date, \PDO::PARAM_STR);
            $query->execute();
            return $conn->lastInsertId(); 

        } catch (PDOException $e) {
            file_put_contents('log.txt', $e->getMessage(), FILE_APPEND); 
            // exit($e->getMessage());
        return "Error!: " . $e->getMessage();
        die();
        }
    }


    /*
     * Check account number 
     *
     * @param $accountnumber
     * @return boolean
     * */

    public function isAccountNumber($accountnumber) {
        try {
            $conn = DB();
            $query = $conn->prepare("SELECT `Id` FROM `ps_employee` WHERE `account_number`=:num");
            $query->bindParam("num", $accountnumber, \PDO::PARAM_INT);
            $query->execute();
            if ($query->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    /*
     * Check Email
     *
     * @param $email
     * @return boolean
     * */

    public function isEmail($email) {
        try {
            $conn = DB();
            $query = $conn->prepare("SELECT `Id` FROM `ps_employee` WHERE `email`=:email");
            $query->bindParam("email", $email, PDO::PARAM_STR);
            $query->execute();
            if ($query->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    /*
     * Login
     *
     * @param $username, $password
     * @return id
     * */
    public function Login($username, $password) {
      try {
        $conn = DB();
        $sql = "SELECT * FROM ps_user WHERE Username=:username";
        $query = $conn->prepare($sql);
        $query->bindParam("username", $username, PDO::PARAM_STR);
        $query->execute();
        if ($query->rowCount() > 0) 
        {
          $result = $query->fetch();
          if (password_verify($password, $result['Password'])) { 
              return $result['Id'];
            }else{
              return false;
            }
          
        } else {
          return false;
        }
      } 
      catch (PDOException $e) {
        exit($e->getMessage());
      }
    }

    /*
     * get User Details
     *
     * 
     * @return $mixed
     * */

    public function get_employees() {
        try {
            $conn = DB();
            $query = $conn->prepare("SELECT * FROM `ps_employee` order by `id` desc");
            $query->execute();
            if ($query->rowCount() > 0) {
                return $query->fetchAll(PDO::FETCH_OBJ);
            }else{
                return false;
            }
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }


    /*
     * get User Details with ID
     *
     * 
     * @return $mixed
     * */

    public function getDetails($id) {
        try {
            $conn = DB();
            $query = $conn->prepare("SELECT * FROM `ps_employee` WHERE `id`=:id");
            $query->bindParam("id", $id, \PDO::PARAM_INT);
            $query->execute();
            if ($query->rowCount() > 0) {
                return $query->fetchAll(PDO::FETCH_OBJ);
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    
    public function getBankCode($bank){ 
      $cull = curl_init();
      curl_setopt_array($cull, array(
      CURLOPT_URL => "https://api.paystack.co/bank",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
      "authorization: Bearer sk_test_9e66427c528098ca9e91fe3d109a083f6aaf619e", 
      "content-type: application/json",
      "cache-control: no-cache"
      ],
      ));

      $response = curl_exec($cull); 
      $err = curl_error($cull);

      if($err){
      // there was an error contacting the Paystack API
        $_SESSION['failure'] = 'Curl returned error for getting bank code:'. $err;
        return $_SESSION['failure'];
      }

      $tranx = json_decode($response, true);  
      

      if(!$tranx['status']){ 
      // there was an error from the API
        return 'Error: ' . $tranx['message'];
      }else{
        if (is_array($tranx)) { 
          foreach ($tranx as $key1) {
            if (is_array($key1)) {
              foreach ($key1 as $key2) {
                if($key2['name'] == $bank){
                  return $key2['code'];
                }
              }
            }
          }
        }
      }
      curl_close($cull);
    }

    // Confirm that the account details entered are correct

    public function CheckAccount($accountnumber, $bankcode){ 

      $handler = curl_init();
      curl_setopt_array($handler, array(
      CURLOPT_URL => "https://api.paystack.co/bank/resolve?account_number=".$accountnumber."&bank_code=".$bankcode, 
      CURLOPT_RETURNTRANSFER => true, 
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
      "authorization: Bearer sk_test_9e66427c528098ca9e91fe3d109a083f6aaf619e", 
      "content-type: application/json",
      "cache-control: no-cache"
      ],
      ));

      $responseRec = curl_exec($handler); 
      $errRec = curl_error($handler);

      if($errRec){
      // there was an error contacting the Paystack API
      $_SESSION['failure'] = 'Curl returned error while confirming account details:'. $errRec;
      return $_SESSION['failure'];
      }

      $transactionReceiver = json_decode($responseRec, true);  
      if(!$transactionReceiver['status']){ 
      // there was an error from the API 
        // return $transactionReceiver['message'];
        return false;
      }else{ 
        return $transactionReceiver['data']['account_name'];
      }
      curl_close($handler);
    }

    public function getReceiver($name, $description, $accountnumber, $bankcode){

      $handle = curl_init();
      curl_setopt_array($handle, array(
      CURLOPT_URL => "https://api.paystack.co/transferrecipient",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        "type" => "nuban",
        "name" => $name,
        "description"  => $description,
        "account_number" => $accountnumber,
        "bank_code" => $bankcode,
        "currency" => "NGN",
      ]),
      CURLOPT_HTTPHEADER => [
      "authorization: Bearer sk_test_9e66427c528098ca9e91fe3d109a083f6aaf619e", 
      "content-type: application/json",
      "cache-control: no-cache"
      ],
      ));

      $responseRec = curl_exec($handle); 
      $errRec = curl_error($handle);

      if($errRec){
      // there was an error contacting the Paystack API
      $_SESSION['failure'] = 'Curl returned error for getting recipient:'. $errRec;
      return $_SESSION['failure'];
      }

      $transactionReceiver = json_decode($responseRec, true);  

      if(!$transactionReceiver['status']){ 
      // there was an error from the API
        return('Error: ' . $transactionReceiver['message']);
      }else{ 
        return $transactionReceiver['data']['recipient_code'];
      }
      curl_close($handle);
    }

    public function InitateTransfer($amount, $recipient_code){ 
      $handle2 = curl_init();
      curl_setopt_array($handle2, array(
      CURLOPT_URL => "https://api.paystack.co/transfer",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        "source" => "balance",
        // "reason" => $name, //optional
        "amount"  => $amount,
        "recipient" => $recipient_code,
      ]),
      CURLOPT_HTTPHEADER => [
      "authorization: Bearer sk_test_9e66427c528098ca9e91fe3d109a083f6aaf619e", 
      "content-type: application/json",
      "cache-control: no-cache"
      ],
      ));

      $responseRec = curl_exec($handle2); 
      $errRec = curl_error($handle2);

      if($errRec){
      // there was an error contacting the Paystack API
      $_SESSION['failure'] = 'Curl returned error for initiating transaction:'. $errRec;
      return $_SESSION['failure'];
      }

      $initiateTransaction = json_decode($responseRec, true);  

      if(!$initiateTransaction['status']){ 
      // there was an error from the API
        $_SESSION['failure'] = 'Error: ' . $initiateTransaction['message']; 
        header("Location: admin.php");
      }else{ 
        // return $initiateTransaction['data']['transfer_code'];
        $_SESSION['payment'] = "Transaction was successful"; 
        header("Location: admin.php");
      }
      curl_close($handle2);
    }

    public function BulkTransfer($transfers){
      $handle2 = curl_init();
      curl_setopt_array($handle2, array(
      CURLOPT_URL => "https://api.paystack.co/transfer/bulk",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        "currency" => "NGN",
        "source" => "balance",
        "transfers"  => $transfers
      ]),
      CURLOPT_HTTPHEADER => [
      "authorization: Bearer sk_test_9e66427c528098ca9e91fe3d109a083f6aaf619e", 
      "content-type: application/json",
      "cache-control: no-cache"
      ],
      ));

      $responseRec = curl_exec($handle2); 
      $errRec = curl_error($handle2);

      if($errRec){
      // there was an error contacting the Paystack API
      $_SESSION['failure'] = 'Curl returned error for making bulk transfer:'. $err;
      return $_SESSION['failure'];     
      }

      $bulkTransaction = json_decode($responseRec, true);  

      if(!$bulkTransaction['status']){ 
      // there was an error from the API
        $_SESSION['failure'] = 'Error: ' . $bulkTransaction['message']; 
        header("Location: admin.php");
      }else{ 
        // return $initiateTransaction['data']['transfer_code'];
        $_SESSION['payment'] = "Transaction was successful"; 
        header("Location: admin.php");
      }
      curl_close($handle2);
    }

    // Retrieve Balance
    public function checkBalance(){
      $curl = curl_init();
      curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/balance",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
      "authorization: Bearer sk_test_9e66427c528098ca9e91fe3d109a083f6aaf619e", 
      "content-type: application/json",
      "cache-control: no-cache"
      ],
      ));

      $responseRec = curl_exec($curl); 
      $errRec = curl_error($curl);

      if($errRec){
      // there was an error contacting the Paystack API
      $_SESSION['failure'] = 'Curl returned error for checking balance:'. $err;
      return $_SESSION['failure'];
      }

      $balance = json_decode($responseRec, true);  

      if(!$balance['status']){ 
      // there was an error from the API
        return('Error: ' . $balance['message']);
      }else{ 
        return $balance['data'][0]['balance'];
      }
      curl_close($curl);
    }
}
?>
