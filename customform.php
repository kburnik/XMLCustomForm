<?
include_once(dirname(__FILE__)."/errorHandler.php");
include_once(dirname(__FILE__)."/inputfield.php");

class CustomForm {

	// temporary directory for file storage
	public $TEMP_DIR = "temp/";	
	public $TEMP_DIR_URL = "temp/";
	public $captchaURL = "turing.php?turing_image=1";
	
	// the form's language
	private $language = "en";

	// can we display the form?
	private $show = true;

	// is the form posted at this moment?
	private $posted = false;
	
	// is all data posted valid?
	private $valid = true;
	
	// form's CSS class
	private $className = "custom-form";
	
	
	// the enc type for file upload
	private $enctype = "multipart/form-data";

	// the mandatory field indicator markup
	private $mandatoryIndicator = "*";
	
	// forms id
	private $id;
	
	// form action 
	private $action;
	
	// Form description in XML (Instance of SimpleXMLElement)
	private $xml;
	
	// Resources loaded from XML
	private $resources = array();
	
	// Defaults loaded from XML
	private $defaults = array();
	
	// Title loaded from XML
	private $title = "Form title";
	
	// Description loaded from XML
	private $description = "Form description";
	
	
	#### Messages
	
	// Success Message
	protected $successmessage = null;
	
	// show form on success?
	protected $showFormOnSuccess = true;
	
	// Error Message
	protected $errormessage = null;
	
	// Duplicate message
	protected $duplicatemessage = null;
	
	// Exception message
	protected $exceptionmessage = null;
	
	
	// list of files to delete after successful submission
	public $redundantFiles = array();
	
	// the post data
	public $postData = array();
	
	function __construct($formFile = "",$language = "en", $className = "custom-form", $temp_dir="temp" , $temp_dir_url = "temp") {
		
		
		$this->language = $language;		
		
		// set directories and URLs
		$this->TEMP_DIR = $temp_dir;
		$this->TEMP_DIR_URL = $temp_dir_url;
		
		$this->redundantFiles = $_SESSION['CustomFormSessionData']['redundantFiles'][$this->TEMP_DIR];
		if (!is_array($this->redundantFiles)) $this->redundantFiles = array();
		
		$this->className = $className;
		
		if (!file_exists($formFile)) {
			trigger_error("Form <strong>{$formFile}</strong> doesn't exist!",E_USER_ERROR);
		}		
		
		// load the XML
		$this->xml = simplexml_load_file($formFile);

		// Build the Form
		$this->build();
		
		// see if posted, respond with error/success function
		if ($this->dataReceived()) {
						
		
			if ($this->valid) {
				if ($this->isDuplicate($this->postData)) {
					$this->valid = false;
					$this->errormessage = $this->duplicatemessage;					
				} else if ($this->onSuccess($this->postData) !== false) {
					// show the form if it can be shown after successful post
					$this->show = $this->showFormOnSuccess;
				} else {
					$this->valid = false;
				}
			} else {
				$this->onError($this->postData);
				return;
			}
			
			// second pass to remove redundant files
			if ($this->valid) {
				foreach ($this->fields as $field) {
					if ($field->getType() == "file") {
						unset($this->redundantFiles[ $field->getFilename() ]);
					}
				}
				
				// unlink the files after success
				foreach ($this->redundantFiles as $redundant => $none) {
					unlink($this->TEMP_DIR."/".$redundant);
				}
				
				unset($this->redundantFiles);
			}
			
		}
		
	}
	
	// hook
	protected function isDuplicate($postData) {
		return false;
	}
	
	// hook
	protected function onSuccess($postData) {
		return true;
	}
	
	// hook
	protected function onError($postData) {
		return true;
	}
	
	private function buildResources($resources) {
		foreach ( $resources->children() as $resource ) {			
			if ($resource["value"] !== null) {
				$value =(string)$resource["value"];
			} else {
				$value = array();
				foreach ($resource->children() as $option) {
					$value[ (string)$option["value"] ] = (string)$option[0][0];
				}
			}
	
			$id = (string)$resource["id"];
			$this->resources[ $id ] = $value;			
		}
	}
	
	
	private function build() {
		
		// build resources
		$this->resources['%'] = __URL__ ;
		foreach ($this->xml->resources as $resources) {
			
			// skip resource if not on chosen language
			if (isset($resources["language"]) && $resources["language"] != $this->language ) continue;
			
			// from external source
			if (isset($resources["src"])) {
				$src = $this->handleReference((string)$resources["src"],true);				
				if (@fopen($src,"r")) {
					$external_resources = simplexml_load_file($src);
					$this->buildResources($external_resources);
				} else {
					trigger_error("External resource <strong>{$src}</strong> doesn't exist!",E_USER_WARNING);
				}
			}
			// from children
			$this->buildResources($resources);
		}
		

		
		// store defaults for fields
		foreach ($this->xml->defaults->children() as $default ) {
			$tagName = $default->getName();
			switch ($tagName) {
				case "field":
					// field => array("attr1"=>"val1", "attr2"=>"val2");
					$this->defaults[$tagName] = reset($default->attributes());
				break;
			}
		}
		
		// get id,method,title and description, mandatoryIndicator ...
		$this->id = $this->xml["id"];
		$this->method = $this->xml["method"];
		$this->action = ($this->xml["action"] !== null) ? $this->xml["action"] : "";
		
		$this->title = (string)$this->xml->title[0][0];
		$this->description = (string)$this->xml->description[0][0];
		$this->mandatoryIndicator = (string) ($this->xml["mandatory-indicator"] !== null) ? $this->xml["mandatory-indicator"] : $this->mandatoryIndicator ;
		$this->errormessage = (string)$this->xml->errormessage[0][0];
		$this->exceptionmessage = (string)$this->xml->exceptionmessage[0][0];
		$this->duplicatemessage = (string)$this->xml->duplicatemessage[0][0];
		$this->successmessage = (string)$this->xml->successmessage[0][0];
		if (($sm = $this->xml->successmessage["showform"]) != null){
			$this->showFormOnSuccess = ($sm=="true") ? true : false;
		}
		
		// handle resource variable substitution 
		foreach ($this as $var => $value) {
			if (is_string($this->$var)) {
				$this->$var = $this->handleReference($value);
			}
		}
		
		// intercept the post data here before creating the fields
		$this->interceptPost();
	
		
		
		// generate the fields
		foreach ($this->xml->fields->children() as $field) {
			$attr = reset($field->attributes());
			
			// create new input field
			$field = new InputField($attr,$this);
			$fieldID = $field->getID();
			if ($fieldID !== null) {
				$this->fields[ $fieldID ] = $field; 
			} else {
				$this->fields[ ] = $field; 
			}
			
			// iterate too see if form is valid also gather list of redundant files
			if ($this->dataReceived())  {
				if (!$field->isValid()) $this->valid = false;
				if ($field->getType() == "file") {
					if ($field->getFilename() != null) {
						$this->redundantFiles[ $field->getFilename() ] = true;
					}
				}
			}
			
		}
	
	}
	
	
	
	public function getDefaults($tag = null) {
		if ($tag === null) {
			return $this->defaults;
		} else {
			return $this->defaults[$tag];
		}
	}
	
	public function getResource($resourceID) {
		return $this->resources[$resourceID];
	}
	
	public function getResources() {
		return $this->resources;
	}
	
	public function getTitle() {
		return $this->title;	
	}
	
	public function getDescription() {
		return $this->description;	
	}
		
	public function getFields() {
		return $this->fields;
	}

	public function getFieldByID($fieldID) {
		return $this->fields[$fieldID];
	}
	
	private function interceptPost(){
		if (strtolower(trim($this->method)) == "post") {
			$this->postData = $_POST;
		} else {
			$this->postData = $_GET;
		}
		if ($this->postData['@formID'] == $this->id) {
			$this->posted = true;
		} else {
			$this->posted = false;
		}
		unset($this->postData['@formID']);
	}
	
	public function dataReceived() {
		return $this->posted;
	}
	
	private function preambule() {
		$preambule = "";
		
		if (!empty($this->title)) {
			$preambule .= "<h1 class = 'title'>{$this->title}</h1>\n";
		}
		
		if (!empty($this->description) && $this->show) {
			$preambule .= "<div class ='description'>{$this->description}</div>\n";
		}
		
		if ($this->dataReceived()) {
		
			
			if ($this->valid) {
				$preambule .= "<div class='message success'>".$this->successmessage."</div>";
			} else {
				$preambule .= "<div class='message error'>".$this->errormessage."</div>";
			}
			
			// replace the variables in message such as "Thank you {$name}, {$surname}!"
			$postValues = array();
			foreach ($this->postData as $var=>$val) $postValues['{$'.$var.'}'] = $val;
			$preambule = strtr($preambule,$postValues);
			
		}
		return "<div class='custom-form-preambule'>".$preambule."</div>";
	}
	
	public function display() {
		$form = $this->preambule();
		if ($this->show) {
			$form .= "<form name=\"{$this->id}\" class=\"{$this->className}\" id=\"{$this->id}\" method=\"{$this->method}\"  action=\"{$this->action}\" enctype=\"{$this->enctype}\">\n";
			$identifier = new InputField(array("type"=>"hidden","id"=>"@formID","defaultvalue"=>$this->id),$this);
			$form .= $identifier->html();
			foreach ($this->fields as $field) {
				$form .= $field->html()."\n";
			}
			$form.="</form>\n";
		}
		return $form;
		
	}	
	
	public function getMandatoryIndicator() {
		return $this->mandatoryIndicator;
	}
	
	public function handleReference($value,$innerReplacement = false) {
		preg_match('/\{\$(.*)\}/',$value,$matches);
		if (isset($matches[1])) {
			if ($innerReplacement) {
				$value = str_replace($matches[0],$this->getResource($matches[1]),$value);
			} else {
				$value = $this->getResource($matches[1]);
			}
		}
		return $value;
	}
	
	function __destruct(){
		if (count($this->redundantFiles) > 0) {
			$_SESSION['CustomFormSessionData']['redundantFiles'][$this->TEMP_DIR] = $this->redundantFiles;
		} else {
			unset($_SESSION['CustomFormSessionData']['redundantFiles'][$this->TEMP_DIR]);
		}
	}
		

}

?>