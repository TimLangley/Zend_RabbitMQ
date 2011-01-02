<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class RABBIT_Message 												{
	protected static $_arrProperyTypes = array(	"content_type" 			=> "shortstr",
												"content_encoding" 		=> "shortstr",
												"application_headers" 	=> "table",
												"delivery_mode" 		=> "octet",
												"priority" 				=> "octet",
												"correlation_id" 		=> "shortstr",
												"reply_to"	 			=> "shortstr",
												"expiration" 			=> "shortstr",
												"message_id" 			=> "shortstr",
												"timestamp" 			=> "timestamp",
												"type"		 			=> "shortstr",
												"user_id" 				=> "shortstr",
												"app_id" 				=> "shortstr",
												"cluster_id" 			=> "shortst"
											);
	private $_body;
	private $_arrProperties;
	private $_arrDeliveryInfo;
	
	public function __construct($body = '', $props = null)			{
		$this->_body 			= $body;
		$this->_arrProperties	= is_null($props)?array():array_intersect_key($props, self::$_arrProperyTypes);
	}
	public function __get($name)									{
		/**
		* Look for additional properties in the 'properties' dictionary,
		* and if present - the 'delivery_info' dictionary.
		*/
		switch($name)												{
			case 'body':
				return $this->_body;
			case 'delivery_info':
				return $this->_arrDeliveryInfo;
			case 'delivery_tag':
				if(isset($this->_arrDeliveryInfo))
					if(array_key_exists('delivery_tag',$this->_arrDeliveryInfo))
						return $this->_arrDeliveryInfo['delivery_tag'];
				throw new RABBIT_Exception_Message(sprintf(RABBIT_Exception_Message::ERROR_NO_PROPERTY, $name));
			default:
				if(array_key_exists($name,$this->_arrProperties))
					return $this->_arrProperties[$name];
				if(isset($this->_arrDeliveryInfo))
					if(array_key_exists($name,$this->_arrDeliveryInfo))
						return $this->_arrDeliveryInfo[$name];
				throw new RABBIT_Exception_Message(sprintf(RABBIT_Exception_Message::ERROR_NO_PROPERTY, $name));
		}
	}
	public function __set($name, $value)							{
		switch($name)												{
			case 'body':
				return $this->_body 			= $value;
			case 'delivery_info':
				return $this->_arrDeliveryInfo	= $value;
			default:
				throw new RABBIT_Exception_Message(sprintf(RABBIT_Exception_Message::ERROR_NO_PROPERTY, $name));
		}
	}
	public function load_properties($raw_bytes)						{
		/**
		* Given the raw bytes containing the property-flags and
		* property-list from a content-frame-header, parse and insert
		* into a dictionary stored in this object as an attribute named
		* 'properties'.
		*/
		$r 						= new RABBIT_AMQP_Serialize_Read($raw_bytes);
		// Read 16-bit shorts until we get one with a low bit set to zero
		$flags 					= array();
		while(true)													{
			$flag_bits 			= $r->read_short();
			array_push($flags, $flag_bits);
			if(($flag_bits & 1) == 0)
				break;
		}
		$shift 					= 0;
		$d 						= array();
		foreach (self::$_arrProperyTypes as $key => $proptype)		{
			if(0 == $shift)		 									{
				if(!$flags)
					break;
				$flag_bits 		= array_shift($flags);
				$shift 			= 15;
			}
			if($flag_bits & (1 << $shift))
				$d[$key] 		= call_user_func(array($r,"read_".$proptype));
			$shift 				-= 1;
		}
		$this->_arrProperties 	= $d;
	}
	public function serialize_properties()							{
		/**
		* serialize the 'properties' attribute (a dictionary) into the
		* raw bytes making up a set of property flags and a property
		* list, suitable for putting into a content frame header.
		*/
		$shift 					= 15;
		$flag_bits 				= 0;
		$flags 					= array();
		$raw_bytes 				= new RABBIT_AMQP_Serialize_Write();
		foreach (self::$_arrProperyTypes as $key => $proptype)		{
			$val 				= (array_key_exists($key,$this->_arrProperties))?$this->_arrProperties[$key]:null;
			if(!is_null($val))										{
				if($shift 		== 0)								{
					array_push($flags, $flag_bits);
					$flag_bits 	= 0;
					$shift 		= 15;
				}
				$flag_bits 		|= (1 << $shift);
				if($proptype 	!= "bit")
					call_user_func(array($raw_bytes, "write_" . $proptype), $val);
			}
			$shift 				-= 1;
		}
		array_push($flags, $flag_bits);
		$result 				= new RABBIT_AMQP_Serialize_Write();
		foreach($flags as $flag_bits)
			$result->write_short($flag_bits);
		$result->write($raw_bytes->getvalue());
		return $result->getvalue();
	}
}