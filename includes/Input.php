<?php
/**
 *
 * Input Class
 *
 * This is to take data from POST/GET/Command . We can sanitizing to data here
 *
 */
class Input
{

  /**
   *
   * Get field value from POST/GET/Command. Pass field name.
   * Additional It will check for isset & not empty. only if both satisfied then returns value.
   * Else It will return false
   * eg : Input::getField('store_id')
   *
   * @param $key. String - Key whose which value we require
   * @return string|bool
   */
  public static function fetch($key = "", $def = ''){
  
  	global $argv;

    $return_value = "";
   
    if( isset($_POST[$key]) ) {
      $return_value = $_POST[$key];
    }
    else if( isset($_GET[$key]) ) {
      $return_value = $_GET[$key];
    }
    else if( isset($argv) ){
    	$clval = self::getArg($argv, $key);
    	
    	if($clval != ''){
				$return_value = $clval;
			}

    }
    
		if(!is_array($return_value)){
			$return_value = trim($return_value);
		}
		
		if($return_value == '' && $def != ''){
			$return_value = $def;
		}
		
    return $return_value;
  }

  /**
   * Standard of passing values: php abc.php key=value
   * Will return the value, if key found in command-line arguments
   *
   * @param $command_args. Array - Collection ofcommand line Strings
   * @param $item. String - Key whose which value we require
   * @return String
   */
  public static function getArg($command_args, $key){
    $return_value = "";
    if(!empty($command_args)){
      foreach($command_args as $s){
        if(preg_match("/^$key=(.*?)$/ims", $s, $matched)){
        	$value = trim($matched[1]);
          if($value != ""){
            $return_value = $value;
          }
        }
      }
    }
    return $return_value;
  }


  /**
   * Get All GET/POST values. similar to $_GET or $_POST.
   *
   * @return bool
   */
  public static function getAll()
  {
		if(!empty($_REQUEST)){
			return $_REQUEST;
		}
		return false;
  }

}
