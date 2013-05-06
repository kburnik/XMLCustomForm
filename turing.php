<?

class _turing {
	var $cookie_name='turingTest';
	var $post_variable='turing';
	
	private $code = null;
	
	function __construct() {	
	}
	
	function passed($var=false) {
		if (!$var) $var=$_POST[$this->post_variable];		
		return (md5($var)==$_COOKIE[$this->cookie_name] && $var!='');
	}
	

	private function generateCode() {
		$this->code = strtolower(rand(10006,99997)) ;
		setcookie($this->cookie_name,md5($this->code),time()+1200,"/");
		return $this->code;
	}
	
	function image() {
		$w=120; $h=40;

	    $code_string = $this->generateCode();
	   
	    $im = imagecreate($w,$h);
	    $color_back = imagecolorallocate($im, 255,255,255);
	    imagefill($im, 0, 0, $color_back);
	   
	    $g=rand(5,8);
		for($i=-40;$i<=$w+20;$i+=$g) {
				$color_line = imagecolorallocate($im, rand(120,170), rand(120,170), rand(20,70));
	        imageline ($im,0,$i+10,$w+20,$i,$color_line);
		}
	    
		$sx=rand(10,30);
		$sy=rand(5,20);
		for ($i=0, $x=2; $i < strlen($code_string); $i++, $x+=15) {		 
		  $text_color = imagecolorallocate($im, rand(20,40),rand(20,150),rand(0,50));
		  imagechar($im, 12, $x+$sx+rand(5,8), $sy+rand(1,3), $code_string[$i], $text_color);
	    }
	    
	    for($i=-40;$i<=$h+90;$i+=$g) {
			$color_line = imagecolorallocate($im, rand(80,170), rand(170,230), rand(20,70));
			imageline ($im,$i,0,$i+30,$h+90,$color_line);
		}
	    header("Content-type: image/jpeg");
	    imagejpeg($im);
	    imagedestroy($im);
	}
}

$_turing = new _turing();

function turing() {
	global $_turing;
	return $_turing;
}

if ($_GET['turing_image']) {
	die($_turing->image());
}
?>