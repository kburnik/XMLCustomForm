<? 
include_once(dirname(__FILE__)."/../../customform.php");

chdir(dirname(__FILE__));
sql();

class AccreditationForm extends CustomForm {
	
	public $captchaURL = "";
	public $TEMP_DIR_URL = "";
	
	function __construct($lang = "hr") {
	
		$this->captchaURL = __URL__."/customform/turing.php?turing_image=1";
		$this->TEMP_DIR_URL = __URL__."/customform/userforms/accreditation/temp";
		
		// construct extended class
		parent::__construct(
			"accreditation-form.xml",
			$lang,
			"custom-form accreditation-form", 
			dirname(__FILE__)."/temp" , 
			$this->TEMP_DIR_URL
		);
		
	}
	
	function isDuplicate($postdata) {
		$pd = array_map("mysql_real_escape_string",$postdata);
		$q = "
		select 
			count(*) c
		from 
			accreditations2 
		where 
				`name`=\"{$pd['name']}\" 
				and `surname`=\"{$pd['surname']}\"
				and `mail`=\"{$pd['mail']}\"
		;
		";
		echo mysql_error();
		return (sql($q)->cell() > 0);
	} 
	
	function onSuccess($postdata) {		
		
		$postdata["birth"] = realdate($postdata["birth"]);
		$postdata["regdate"] = now();		
		$postdata["portrait_photo"] = __URL__."/customform/userforms/accreditation/temp/".$postdata["portrait_photo"];
		$postdata["presscard_photo"] = __URL__."/customform/userforms/accreditation/temp/".$postdata["presscard_photo"];				
		$postdata["host"] = $_SERVER['REMOTE_ADDR']." (".secure(gethostbyaddr($_SERVER['REMOTE_ADDR'])).")";
				
		sql()->insert("accreditations2",$postdata);
		$request_num = mysql_insert_id();
		
		if ($err = mysql_error()) {				
			$errno = mysql_errno();
			$this->errormessage = $this->exceptionmessage."(MYSQL ErrNo: $errno)<br/>$err";			
			return false;
		}
		
		
		//
		$_headers  = 'MIME-Version: 1.0' . "\r\n";
		$_headers .= "From: ----- <user@example.com>". "\r\n";
				
		
		mail($postdata["mail"],"Accreditation","{$this->successmessage}",$_headers);
		
		$admin_mail = "admin@example.com";
		
		$maildata = "Accreditation info. #{$request_num}\n\n";	
		$data = sql("select * from accreditations2 where id_accreditaion = '{$request_num}';")->row();
		foreach ($data as $var =>  $val) {
			$maildata .= "$var:\n$val\n\n";
		}		
		mail($admin_mail,"Media Accreditation request - num. {$request_num} - {$postdata['surname']} {$postdata['name']}",$maildata,$_headers);
		
		
		return true;
	}
	
}

$cl = $_GET["lang"];
$langs = array("en","hr");
$lang = reset($langs);
if (in_array($cl,$langs)) $lang = $cl;

$accreditationForm = new AccreditationForm($lang);

?>