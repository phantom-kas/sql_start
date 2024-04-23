<?php 
require_once($sub.'classes/jwtkn.php');
class Db{
	public $table;
	public $id;
  protected $db;
  private $mysqli;
	private $stmtResult; //used to store get_result()
	private $stmt;
	private $defaultFetchType;
	public $qwhere = 'where 1 ';
	public $qVars = [];
	public $qLimVar = 25;
	public $qLim = "";
	public $qOrder = ' ';
	public $sql;
	// public  $moreColumns = [];

function __construct(){
	if(DEV){
		$this->db = new mysqli('localhost',DEV_DB_USER,DEV_DB_PASS,DB_NAME);//or die("Connection error");
	}
	else{
		$this->db = new mysqli('localhost',DB_USER,DB_PASS,DB_NAME);//or die("Connection error");
	}


  if($this->db->connect_error){
    outPut( servError("Failed to connect to MySQL: ". $this->db->connect_error));
    die();
    }
		$this->qLim = "LIMIT $this->qLimVar";
  }

// public function encodeString($str){
// 	return static::urlsafeB64Encode($str);
// }
	public function addQwhere($where, $qvar = null , $getParam = null){
		// if($getParam != null){
		// 	if(!isset($_GET[$getParam])){
		// 		return;
		// 	}
		// }
		$this->qwhere .= $where;
	
		if($qvar != null){
			if(is_array($qvar)){
				foreach ($qvar as $key => $value) {
				array_push($this->qVars, $value);
				}
				return;
			}
			array_push($this->qVars, $qvar);
		}
	
	}
	public function addQwhereGetParam($where, $qvar = null , $getParam = null){
		$error = false;
		$vars = null;
	
		if($getParam != null){
			if(!isset($_GET[$getParam])){
				return;
			}
		}
		
		//echo 'emk';
		if(is_array($qvar)){
			foreach ($qvar as $key => $value) {

				if(!isset($_GET[$value])){
					
					$error = true;
					break;
				}
				$vars[$key] = $_GET[$value];
				
				}
				if($error){
					return;
				}
			
		}
		
		else if(!isset($_GET[$qvar])){
			return;
		}
		else
		{
			$vars =  $_GET[$qvar];
			
		};
	
		$this->addQwhere($where, $vars);
		// echo $vars;
		// 	die();
	}


  public function query(string $sql, $values = [], string $types = ''): self {
		if(!is_array($values)) $values = [$values]; //Convert scalar to array
		if(!$types) $types = str_repeat('s', count($values)); //String type for all variables if not specified 
		if(!$values) {
			$this->stmtResult = $this->db->query($sql); //Use non-prepared query if no values to bind for efficiency
		} else {

			
			$values = array_map(fn($val)=>$val ?? null,$values);
			$stmt = $this->stmt = $this->db->prepare($sql);
			$stmt->bind_param($types, ...$values);
			//echo $sql;die();
			$stmt->execute();
			$this->stmtResult = $stmt->get_result();    
		}
		return $this;
	}

	public function smartInsertQuery($table,$colums , $dataArr, $values = [], string $types = ''): self {
		if(!is_array($values)) $values = [$values]; //Convert scalar to array
		if(!$types) $types = str_repeat('s', count($colums)); //String type for all variables if not specified
		$ques ='?'. str_repeat(',?', count($colums) - 1);
		$sql = "INSERT INTO $table ( ".implode(', ',$colums) . ") VALUES (".$ques .')';
		 if(true) {
			$values = [];
			foreach ($colums as $i => $c) {
				# code...
				$values[$i] =  $dataArr[$c] ?? null;
			}
			$stmt = $this->stmt = $this->db->prepare($sql);
			$stmt->bind_param($types, ...$values);
			$stmt->execute();
			$this->stmtResult = $stmt->get_result();
		}
	return $this;
}



public function getCountries(){
return $this->query("SELECT * FROM country")->getRows();
}


public function smartInsertQuery2($table,$colums , $dataArr, $values = [], string $types = ''): self {
	if($colums === null) {
		$colums = array_keys($dataArr);

		$tableColumns = $this->getColumns($table);
		foreach ($colums as $key => $value) {
			# code...
				if(!in_array($value,$tableColumns)){
					unset($colums[$key]);
				}
		}
 }
	
	if(!is_array($values)) $values = [$values]; //Convert scalar to array
	if(!$types) $types = str_repeat('s', count($colums)); //String type for all variables if not specified
	$ques ='?'. str_repeat(',?', count($colums) - 1);
	$sql = "INSERT INTO $table ( ".implode(', ',$colums) . ") VALUES (".$ques .')';
	 if(true) {
		$values = [];
		foreach ($colums as $i => $c) {
			# code...
			$values[$i] =  $dataArr[$c] ?? null;
		}
		$stmt = $this->stmt = $this->db->prepare($sql);
		$stmt->bind_param($types, ...$values);
		$stmt->execute();
		$this->stmtResult = $stmt->get_result();
	}
return $this;
}





  public function numRows(): int {
		return $this->stmtResult->num_rows;
	}

	public function numAffectedRows(): int {
		return mysqli_affected_rows($this->db);
	}

  public function getRows():array {
    $results = $this->stmtResult;
   return ($results->fetch_all(MYSQLI_ASSOC));
  }

  public function affectedRows(): int {
		return $this->db->affected_rows;
	}

  public function close(): void {
		$this->db->close();
	}
	public function getColumns($table){
				$result = $this->query("SHOW COLUMNS FROM $table");
		if ($result->numRows() < 1) {
				outPut(servError());
				die();
		}
		return array_map( fn($n) => $n['Field'],$result->getRows());
	}

	public function getAll($start = 0, $length = 50){
		$result = $this->query("select* from $this->table where id > $start limit $length")->getRows();
		return ($result);
	}

	public function lastId(){
		return mysqli_insert_id($this->db);
	}
	public function getBById($id){
		return $this->query("SELECT * from $this->table where id = ?",[$id])->getRows();
	}

	public function deleteById($id, $table = null , $colunnm = null) :self{
		$table = $table ?? $this->table ;
		$colunnm = $colunnm ?? 'id';
		$this->query("DELETE FROM $table WHERE $colunnm = ?",[$id]);
		return $this;
	}


	public function smartUpdate($table,$colums, $dataArr, $id, $values = [], string $types = ''): self {
		//if(!is_array($values)) $values = [$values]; //Convert scalar to array
		if($colums === null) {
			$colums = array_keys($dataArr);

			$tableColumns = $this->getColumns($table);
			foreach ($colums as $key => $value) {
				# code...
					if(!in_array($value,$tableColumns)){
						unset($colums[$key]);
					}
			}
	 }
		
		if(!$types) $types = str_repeat('s', count($colums)); //String type for all variables if not specified
		$sql = "UPDATE $table SET  ";
		if($table === null) {
			$table = $this->table;
	 }

	 	if(!$id) {
			$id = $this->id;
	 }
		 
			$values = [];
			$numCols = count($colums);
			$a = -1;
			foreach ($colums as $i => $c) {
				# code...
				$sql .=  $c .' = ?';
				$values[$i] =  $dataArr[$c] ?? null;
				$a++;
				if( $a  == $numCols -1 ){
					continue;
				}
				$sql .=  ', ';
			}
			$types .='d';
			array_push($values , $id);


			$sql .= " WHERE id = ?";

			 //echo $sql;
			// die();
			$stmt = $this->stmt = $this->db->prepare($sql);
			$stmt->bind_param($types, ...$values);
			$stmt->execute();
			$this->stmtResult = $stmt->get_result();
		
	return $this;
	}


	public function validateParameter($fieldName, $value, $dataType = null, $required = true) {
		if($required == true && empty($value) == true) {
			servError( $fieldName . " parameter is required.");
		}
if($dataType != null){
		switch ($dataType) {
			case 'bool':
				if(!is_bool($value)) {
					servError( "Datatype is not valid for " . $fieldName . '. It should be boolean.');
				}
				break;
			case 'number':
				if(!is_numeric($value)) {
					servError( "Datatype is not valid for " . $fieldName . '. It should be numeric.');
				}
				break;

			case 'string':
				if(!is_string($value)) {
					servError( "Datatype is not valid for " . $fieldName . '. It should be string.');
				}
				break;
			
			default:
				servError("Datatype is not valid for " . $fieldName);
				break;
		}
	}

		return $value;

	}

  public function getAuthorizationHeader()
  {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
      $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
      $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
      $requestHeaders = apache_request_headers();
      // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      if (isset($requestHeaders['Authorization'])) {
        $headers = trim($requestHeaders['Authorization']);
      }
    }
    return $headers;
  }

	public function getBearerToken()
  {
    $headers = $this->getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
      if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
      }
    }
    unAuth(true, 'Access Token Not found');
  }

	public function getUserName($uid){
		$data = $this->query("SELECT lname ,img_filename, fname ,aflt_link_param,crypto_wallet, email FROM users where id = ?",[$uid])->getRows();
		return $data[0];
	}

	public function getProgramName($pid){
		$data = $this->query("SELECT title from program where id = ?",[$pid])->getRows();
		return $data[0];
	}



	public function getUserIdFromAtoken():self{
    $token = $this->getBearerToken();
    try {
      //code...
     
     
      $payload =  JWT::decode( $token , ACCESS_SECRETE_KEY, ['HS256'], 'a');
    } catch (\Throwable $th) {
      //throw $th;
      unAuth(true, $th->getMessage());
    }
   
    // echo $payload->exp . ' ' .time() .'/n \n';
    // echo $payload->exp -  time();
    $this->id  =    $payload ->userId;
    return $this;
  }






	

	

	public function checkPremission($premission,$uid){
		if(is_array($premission)){$premission = implode(', ',$premission);}
		return $this->query("SELECT $premission from user_premissions WHERE user_id = ? ",[$uid])->getRows();
	}

	public function checkPremission2($premission,$uid){
		return $this->query("SELECT $premission from premissions WHERE user_id = ? ",[$uid])->getRows();
	}

	


	public function checkProgramPremission($uid , $id){
		return $this->query("SELECT premission from user_programs_premissions WHERE user_id = ? && program_id = ?",[$uid, $id])->getRows();
	}

	public function checkProgramPremission2($uid , $id){
		$data =  $this->query("SELECT premission from user_programs_premissions WHERE user_id = ? && program_id = ?",[$uid, $id])->getRows();
		if(count($data) < 1){
			forbid();
		}
		return $data[0];
	}

	public function CanAddToProgram($uid , $id):bool{
		if(count($this->checkProgramPremission($uid, $id)) > 0){
return true;
		}
		return false;
	}

	public function addProgramWeek($pid,$wk,$uid,$title,$die = false){
		$num = null;
    if($this->query("SELECT week from program_weeks where week = ? and program_id = ?",[$wk,$pid])->numRows() < 1){
    $num =  $this->smartInsertQuery2('program_weeks',null,['week'=>$wk,'program_id'=>$pid,'created_by'=>$uid,'title' =>$title])->lastId();
    }
		else{
			if($die){
				outPut(servError("Week $wk already exists under the program"));
				die();
			}
			return false;
		}
		if($num){
				$this->query("UPDATE program set num_weeks = num_weeks + 1 WHERE id = ?",$pid);
		}
    if($die){
      outPut(servSus('1 week added'));
      die();
    }
    return true;
  }
	public function markUser_tableAsViewed($uid,$id){
		if(count($this->query("SELECT id , view_status FROM user_{$this->table} WHERE uid = ? and {$this->table}_id = ?",[$uid, $id])->getRows()) > 0){
			$this->query("UPDATE user_{$this->table} SET view_status = 1 WHERE uid = ? and {$this->table}_id = ?",[$uid, $id]);
			return;
		}

		$this->smartInsertQuery2("user_{$this->table}",null,['user_id' => $uid, 'video_id' =>$id, "created_at" => getDateTime() ]);


		return;
	}

	public function checkCanEdithUsers($uid){
		$data = $this->checkPremission2('edit_other_profile' , $uid);

		if(count($data) < 1){
			return false;
		}
		if(!isset($data[0]['edit_other_profile'])){
				return false;
		}
		if($data[0]['edit_other_profile'] == 7 || $data[0]['edit_other_profile'] == 5){
			return true;
	}
	}

	public function canViewOtherPayments($uid){
		$data = $this->checkPremission2('payments' , $uid);

		if(count($data) < 1){
			return false;
		}
		if(!isset($data[0]['payments'])){
				return false;
		}
		if($data[0]['payments'] == 7 || $data[0]['payments'] == 5
			||  $data[0]['payments'] == 6
			|| $data[0]['payments'] == 4
		){
			return true;
	}
	return false;
}

public function uR($uid ,$prem){
	$data = $this->checkPremission2($prem , $uid);

	if(count($data) < 1){
		return false;
	}
	if(!isset($data[0][$prem])){
			return false;
	}
	if($data[0][$prem] == 7 || $data[0][$prem] == 5
		||  $data[0][$prem] == 6
		|| $data[0][$prem] == 4
	){
		return true;
}
return false;
}

public function uE($uid ,$prem){
	$data = $this->checkPremission2($prem , $uid);

	if(count($data) < 1){
		return false;
	}
	if(!isset($data[0][$prem])){
			return false;
	}
	if($data[0][$prem] == 7 || $data[0][$prem] == 2
		||  $data[0][$prem] == 6
		|| $data[0][$prem] == 3
	){
		return true;
}
return false;
}

public function uAE($uid ,$prem){
	$data = $this->checkPremission($prem , $uid);

	if(count($data) < 1){
		return false;
	}
	if(!isset($data[0][$prem])){
			return false;
	}
	if($data[0][$prem] == 7 || $data[0][$prem] == 2
		||  $data[0][$prem] == 6
		|| $data[0][$prem] == 3
	){
		return true;
}
return false;
}
public function canAll($uid ,$prem){
	$data = $this->checkPremission($prem , $uid);

	if(count($data) < 1){
		return null;
	}
	
	if(!isset($data[0][$prem])){
			return false;
	}
	if($data[0][$prem] < 7){
		return null;
	}
	if($data[0][$prem] == 7){
		return false;
	}
	if($data[0][$prem] > 7){
		return true;
	}
return null;
}

public function recordActivity($uid,$activity ,$title,$item, $route = null){
	if($uid == null){
		$uid = $this->getUserIdFromAtoken()->id;
	}
	$this->smartInsertQuery2('activity',null,[
		'user_id'=>$uid,
		'dis' => $activity,
		'route' =>$route,
		'item' => $item,
		'created_at' => getDateTime(),
		'title' =>$title
	]);
}

public function checkCanView($uid , $prem){
	$data = $this->checkPremission($prem , $uid);

	if(count($data) < 1){
		forbid();
		die();
	}
	if(!isset($data[0][$prem])){
		forbid();
		die();
	}
	if($data[0][$prem] > 0
	){	return $data[0][$prem];}
			forbid();
			die();
			return false;
}

public function can($uid , $prem){
	$data = $this->checkPremission($prem , $uid);
	if(count($data) < 1){
		forbid(true);
		die();
	}
	if(!isset($data[0][$prem])){
		forbid(true);
		die();
	}
	if($data[0][$prem] > 0){	
		return $data[0][$prem];
	}
	else if($data[0][$prem] < 1){	
		return false;
	}
			forbid(true);
			die();
			return false;
}

public function canP($uid , $prem){
	$data = $this->checkPremission($prem , $uid);
// echo $data[0][$prem];
	if(count($data) < 1){
		forbid();
		die();
	}
	
	if(!isset($data[0][$prem])){
		forbid();
		die();
	}
	if($data[0][$prem] < 1){	
		forbid(true);
		die();
	}
	if($data[0][$prem] <= 7){	
		return false;
	}
	else if ($data[0][$prem]  > 7){
		return true;
	}
			forbid();
			die();
			return false;
}

public function getFromLastIdInDecOrder($wherePram = "p.id"  ,$getParam = "last_id" ){
	if(isset($_GET[$getParam])){ 
		$this->addQwhere(" && $wherePram  < ? ", $_GET[$getParam]);
	
	}

		$this->qOrder .= " ORDER BY $wherePram desc ";
		$this->qLimVar = 25;
}


public function getFromLastIdInSecOrderWhere($where, $order ,$params ,$checkparam = 'last_id' ,$lim  = 25){
	if(isset($_GET[$checkparam])){
		$this->addQwhere($where, $params);
		$this->qOrder .= $order;
		$this->qLimVar = $lim;
	}


	

	
}
public function checkProgramStaff($uid ,$pid){
	$data = $this->query("SELECT premission FROM user_programs_premissions where user_id =? && program_id = ?",[$uid , $pid])->getRows();
	if(count($data) < 1){
		forbid(true);
		die();
	}

	if($data[0]['premission'] < 1){
		forbid();
		die();
	}
	else if ($data[0]['premission'] < 7){
		return false;
	}

		return true;
	
}

public function staffCanRead($uid , $prem , $pid){

	if($this->canP($uid, $prem) == false){
		$this->checkProgramStaff($uid , $pid);
	}
}


public function staffCanReadWriteEdit($uid , $prem , $pid){
	// echo $uid , $prem , $pid;
	if($this->canP($uid, $prem) == true)
	{
		return;
	}
	
	if($this->canP($uid, $prem) == false){
		if($this->checkProgramStaff($uid , $pid) == false){
			forbid();
		}
	}
	else{
		forbid();
	}
}




public function getUpData($uid , $pid,$die = true){
	$data  = $this->query("SELECT status , payment_status  FROM user_programs where user_id = ?
	&&
	program_id =?" , [$uid , $pid])->getRows();
	if(count($data) < 1){
		if(die){
			forbid();
		}
		return false;
	}
	return $data[0];
}
public function invalidateAtknRtkn(){
	$payload =  JWT::decode( $this->getBearerToken() , ACCESS_SECRETE_KEY, ['HS256'], 'a');
	$RtknId = $payload->RtknId;

	$num = $this->query("DELETE FROM refresh_tokens where id = ?",[$RtknId])->numAffectedRows();
	return $num;
}
public function checkStudentUp($uid, $pid){
	$this->getUpData($uid , $pid);
	return true;
}

public  function studentCanView(){
	
}

public function staffCanWrite(){

}


public function checkRequired($reqd,$data){
//	$reqd = ['pass','number','lname','fname','email','pass','country','city'];
	// $reqd = ['email' ,'country'];
	foreach ($reqd as $key => $value) {
		if(!isset($data[$value])){
			outPut(['alerts' =>[ servError('All information must be provided')] , 'message' => 'All information must be provided' ,'status' => 'error']);
			die();
		}
		if(empty($data[$value])  ||  $data[$value] == ''){
			outPut(['alerts' =>[ servError('All information must be provided')] , 'message' => 'All information must be provided' ,'status' => 'error']);
			die();
		}
	}
}
	// public function getColumNames(){
	// 	return array_map(fn()=>{ $arr  },$this->query("SHOW COLUMNS FROM $this->table")->getRows()[0]);
	// }
}
?>
