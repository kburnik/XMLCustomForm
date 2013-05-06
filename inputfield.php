<?
class InputField {

	// parent object
	protected $customForm;
	
	// is field valid? true by default
	private $valid = true;
	
	
	// attributes
	private $type;
	private $id;
	private $label;
	private $mandatory;
	private $regex;
	private $store;
	private $validate;
	private $defaultvalue;
	private $errormessage;
	
	// checkbox
	private $checkedvalue;
	private $checked;
	
	// list of options for select/radio
	private $options;
	
	// for file upload
	private $accept;
	private $filename; // for already uploaded valid file
	
	// for image upload
	private $restrict;
		
	// for date and possibly other uses
	private $format;
	private $bounds; // date interval with ";" delimiter
	
	
	// list of redundant uploaded files
	public $redundantFiles = array();	
	
	function __construct($fieldDescription = array(),$customForm = null) {
		// store the parent object reference
		$this->customForm = $customForm;
		
				
		// merge description with defaults for field
		$defaults = $customForm->getDefaults("field");
		if (is_array($defaults)) {
			$fieldDescription = array_merge($defaults,$fieldDescription);
		}
		foreach ($fieldDescription as $attr => $value) {
			$this->$attr = $this->customForm->handleReference($value);
		}
		
		// exception
		if ($this->type == "checkbox") $this->mandatory = "false";
		
		// default for options
		if ($this->options == null) $this->options = array();
				
		// if data posted, see if field is valid, also process the request (e.g. upload file...);
		if ( $this->customForm->dataReceived() ) {
			$this->valid = $this->validate();
		}
		
	}
	
	
	
	public function getID() {
		return $this->id;
	} 
	
	public function getType() {
		return $this->type;
	}
	
		
	public function getValue(){
		if (isset($this->customForm->postData[$this->id])) {
			return $this->customForm->postData[$this->id];
		} else {
			//$default ="default";
			return $this->defaultvalue;
		}
	}
	
	private function attachLabel($control) {
			$label = $control->addChild("label",$this->label.":");
			$label->addAttribute("for",$this->id);
			$label->addAttribute("class","control-label");
			return $label;
	}

	private function attachMandatory($control) {
		if ($this->mandatory == "true") {
			$mandatory = $control->addChild("span",$this->customForm->getMandatoryIndicator());
			$mandatory->addAttribute("class","mandatory");
			return $mandatory;
		}
		return null;
	}
	
	private function attachErrorMessage($control) {
		$err = $control->addChild("span",$this->errormessage);
		$err->addAttribute("class","error-message");
		return $err;
	}
	
	
	// create the control SimpleXMLElement, attach Mandatory and label if required
	private function createControl($attachMandatory = true, $attachLabel = true) {
		$control = new SimpleXMLElement("<control />");
		if ($attachMandatory || $attachLabel) {
			$preInput = $control->addChild("span");
			$preInput->addAttribute("class","pre-input");
		}
		if ($attachMandatory) $this->attachMandatory($preInput);
		if ($attachLabel) $this->attachLabel($preInput);
		
		return $control;
	}
	
	
	// return the form html
	public function html() {
		$singleTag = true;
		$tagName="input";
		$attributes = array(
			"type" => $this->type,
			"name" => $this->id,
			"id"  => $this->id,
			"value" => $this->getValue(),
			"class" => "input-field"
		);
		$useMandatory = true;
		$useLabel = true;
		$children = null;
		$constructControl = true;
		
		switch($this->type) {
			case "select":
				$singleTag = false;
				
				$control = $this->createControl();
				
				// select
				$select = $control->addChild("select");
				$select->addAttribute("name",$this->id);
				$select->addAttribute("id",$this->id);
				$select->addAttribute("class","input-field");
				
				foreach ($this->options as $optionValue => $optionText) {
					$option = $select->addChild("option",$optionText);
					$option->addAttribute("value",$optionValue);
					if ($this->getValue() == $optionValue || $optionValue == $this->defaultvalue) {
						$option->addAttribute("selected","selected");
					}
				}
				
			break;
			case "radio":
				$singleTag = false;
				// get the radio options;
				
				$control = $this->createControl(true,true);
				$index = 0;
				
				foreach ($this->options as $optionValue => $optionText) {
					$id = $this->id."-{$index}";
					
					// radio button
					$o = $control->addChild("input");
					$o->addAttribute("type","radio");
					$o->addAttribute("id",$id);
					$o->addAttribute("name",$this->id);
					$o->addAttribute("value",$optionValue);
					
					if ($this->getValue() == $optionValue) {
						$o->addAttribute("checked","checked");
					}
					
					// radio instance label
					$l = $control->addChild("label",$optionText);
					$l->addAttribute("for",$id);
					$index++;
				}				
				
			break;
			case "checkbox":
				$attributes["value"] = $this->checkedvalue;
				if ($this->getValue() == $this->checkedvalue || (!$this->customForm->dataReceived()) && $this->checked == "true"){
					$attributes["checked"] = "checked";
				}
			break;
			case "date":
				$attributes["type"] = "text";
				$attributes["value"] = date($this->format, strtotime($this->getValue()));
			break;
			case "combo":
				
			break;
			case "file":				
				// handle when already uploaded a valid file ...
				if ($this->customForm->dataReceived() && $this->isValid()) {
					
					$control = $this->createControl(true,true);
					$constructControl = false; // do not construct again at bottom
					
					$surrogate = $control->addChild("input");
					$surrogate->addAttribute("type","hidden");
					$surrogate->addAttribute("name","@file-surrogate-".$this->id);
					$surrogate->addAttribute("value",$this->filename);
					
					$previewLink = $control->addChild("a",$this->filename);
					$previewLink->addAttribute("href",$this->customForm->TEMP_DIR_URL.'/'.$this->filename);
					$previewLink->addAttribute("target","_blank");
					$previewLink->addAttribute("class","file-surrogate-link");
				}
				// remove the surrogate from postData				
				
			break;
			case "submit":
				// no label for submit
				$useMandatory = false;
				$useLabel = false;
			break;
			case "reset":
				// no label for reset
				$useMandatory = false;
				$useLabel = false;
			break;
			case "hidden":
				$useMandatory = false;
				$useLabel = false;
				
			break;
			case "captcha":
				$constructControl = false;
								
				
				$control = $this->createControl(true,true);
				$img = $control->addChild("img");
				$img->addAttribute("src",$this->customForm->captchaURL);
				$img->addAttribute("class","captcha-image");
				$attributes["value"]="";
			break;
			default:
				
			break;
		}
		
		
		
		// generic input
		if ($singleTag) {
			
			if ($constructControl) {
				$control = $this->createControl($useMandatory,$useLabel);
			}
			
			$input = $control->addChild($tagName);
			
			foreach ($attributes as $attr=>$value) {
				if ($value!==null) {
					$input->addAttribute($attr,$value);
				}
			}
			
		} 
		
		// handle errors
		if ( ! $this->isValid() ) {
			$this->attachErrorMessage($control);
		}
		
		
		// output html
		foreach ($control->children() as $tag) {
				$out .= $tag->asXML();
		}
		
		$state_class = "";
		if ($this->customForm->dataReceived()) {
			$state_class  = " ". (($this->isValid()) ? "valid" : "invalid");
		}
		if ( !in_array($this->type , array("hidden","reset","submit") ) ) {
			$out = "<div class='control{$state_class} type-{$this->type}'>\n{$out}\n</div>";
		}
		return $out;
		
	}
	
	// is the received data valid?
	public function isValid() {
		return $this->valid;
	}
	
	// validate this input
	private function validate() {
		if ($this->validate=="true") {
			
			// first run the regex
			if ($this->regex != null) {
				return preg_match($this->regex,$this->getValue());
			}
			
			// for select/radio/other...
			if ($this->mandatory == "true" && count($this->options) > 0) {
				// posted value must be in available options
				return in_array($this->getValue(),array_keys($this->options));
			}
			
			// for fields which cannot be empty
			if ($this->mandatory == "true") {
				// for files
				if ($this->type == "file") {
					
					// read file info
					$file = $_FILES[$this->id];
					
					// check if already sent
					if (
						$file['error'] == UPLOAD_ERR_NO_FILE 
						&& isset($this->customForm->postData['@file-surrogate-'.$this->id])
					) {
						$this->filename = $this->customForm->postData['@file-surrogate-'.$this->id];
						$this->customForm->postData[$this->id] = $this->filename;
						unset($this->customForm->postData['@file-surrogate-'.$this->id]);
						return true;
					}
					
					unset($this->customForm->postData['@file-surrogate-'.$this->id]);
					// check acceptability
					$acceptable = true;
										
					// check if file acceptable by its type/group;
					$accept = strtolower(trim($this->accept));
					switch ($accept) {
						case "all":
							$acceptable = true;
						break;
						default:							
							$acceptable = ( reset(explode("/",$file["type"])) == $accept );
						break;
					}
				
				
					// store the file if accepted
					if ($acceptable) {						
						$filename = $this->uploadfile($file,$this->customForm->TEMP_DIR);
						$this->filename = $filename;
						$this->customForm->postData[$this->id] = $this->filename;											
					}
				
					return $acceptable;
					
				} else if ($this->type == "date") {

					$dateTime = DateTime::createFromFormat($this->format,$this->getValue());
					
					// check the date					
					if ($dateTime === false) {						
						return false;
					}
					
					// check date bounds
					if ($this->bounds != null) {
						list($lower_bound,$upper_bound) = explode(";",$this->bounds);
						
						if (!isset($upper_bound)) {
							$upper_bound = $lower_bound;
							unset($lower_bound);
						}
						
						if (isset($lower_bound)) {
							if (strtotime($dateTime->format('Y-m-d')) < strtotime($lower_bound)) return false;
						}
						if (isset($upper_bound)) {
							if (strtotime($dateTime->format('Y-m-d')) > strtotime($upper_bound)) return false;
						}
						
						return true;
						
					}
				} else if ($this->type == "captcha") { 
					return (turing()->passed($this->getValue()));						
				} else {
					return ($this->getValue() != null);
				}
			}
			
		}
		return true;
	}
	
	
	private function uploadfile($file,$dir='temp',$newname=false) {
		$uploaddir=$dir."/";	
		$basename=strtotime("now").rand(1000000000,9999999999).".". end(explode(".",basename($file["name"])));
		
		if (file_exists("{$uploaddir}{$basename}")) {				
			$d = dir("$uploaddir");		
			$k=0;
			while (false !== ($cf = $d->read())) $k++;			   
			$d->close();				
			$basename=$k."_".$basename;
		}
		$uploadfile = $uploaddir . $basename;
		if (move_uploaded_file($file['tmp_name'], $uploadfile)) {
			if ($newname) {
				$newname=$newname.".".end(explode(".",$basename));
				rename($uploadfile,$uploaddir.$newname); $basename=$newname;
			}
			return $basename;
		}
		
		return false;
	}
	
	
	public function getFilename() {
		return $this->filename;
	}
		
	
	
}



?>