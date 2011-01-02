<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class RABBIT_AMQP_Abstract											{
	private static $CONTENT_METHODS = array(
		"60,60", // Basic.deliver
		"60,71", // Basic.get_ok
		);
	private static $CLOSE_METHODS = array(
		"10,60", // Connection.close
		"20,40", // Channel.close
		);
	// All the method names
	public static $GLOBAL_METHOD_NAMES = array(
		"10,10" => "Connection.start",
		"10,11" => "Connection.start_ok",
		"10,20" => "Connection.secure",
		"10,21" => "Connection.secure_ok",
		"10,30" => "Connection.tune",
		"10,31" => "Connection.tune_ok",
		"10,40" => "Connection.open",
		"10,41" => "Connection.open_ok",
		"10,50" => "Connection.redirect",
		"10,60" => "Connection.close",
		"10,61" => "Connection.close_ok",
		"20,10" => "Channel.open",
		"20,11" => "Channel.open_ok",
		"20,20" => "Channel.flow",
		"20,21" => "Channel.flow_ok",
		"20,30" => "Channel.alert",
		"20,40" => "Channel.close",
		"20,41" => "Channel.close_ok",
		"30,10" => "Channel.access_request",
		"30,11" => "Channel.access_request_ok",
		"40,10" => "Channel.exchange_declare",
		"40,11" => "Channel.exchange_declare_ok",
		"40,20" => "Channel.exchange_delete",
		"40,21" => "Channel.exchange_delete_ok",
		"50,10" => "Channel.queue_declare",
		"50,11" => "Channel.queue_declare_ok",
		"50,20" => "Channel.queue_bind",
		"50,21" => "Channel.queue_bind_ok",
		"50,30" => "Channel.queue_purge",
		"50,31" => "Channel.queue_purge_ok",
		"50,40" => "Channel.queue_delete",
		"50,41" => "Channel.queue_delete_ok",
		"60,10" => "Channel.basic_qos",
		"60,11" => "Channel.basic_qos_ok",
		"60,20" => "Channel.basic_consume",
		"60,21" => "Channel.basic_consume_ok",
		"60,30" => "Channel.basic_cancel",
		"60,31" => "Channel.basic_cancel_ok",
		"60,40" => "Channel.basic_publish",
		"60,50" => "Channel.basic_return",
		"60,60" => "Channel.basic_deliver",
		"60,70" => "Channel.basic_get",
		"60,71" => "Channel.basic_get_ok",
		"60,72" => "Channel.basic_get_empty",
		"60,80" => "Channel.basic_ack",
		"60,90" => "Channel.basic_reject",
		"60,100" => "Channel.basic_recover",
		"90,10" => "Channel.tx_select",
		"90,11" => "Channel.tx_select_ok",
		"90,20" => "Channel.tx_commit",
		"90,21" => "Channel.tx_commit_ok",
		"90,30" => "Channel.tx_rollback",
		"90,31" => "Channel.tx_rollback_ok"
		);

	public function __construct($connection, $channel_id)		{
		$this->connection = $connection;
		$this->channel_id = $channel_id;
		$connection->channels[$channel_id] = $this;
		$this->frame_queue = array();  // Lower level queue for frames
		$this->method_queue = array(); // Higher level queue for methods
		$this->auto_decode = false;
	}
	public function getChannelId()								{
		return $this->channel_id;
	}
	function dispatch($method_sig, $args, $content)				{
		if(!array_key_exists($method_sig, $this->method_map))
			throw new Exception("Unknown AMQP method $method_sig");
		$amqp_method = $this->method_map[$method_sig];
		if(is_null($content))
			return call_user_func(array($this,$amqp_method), $args);
		else
			return call_user_func(array($this,$amqp_method), $args, $content);
	}
	function next_frame()										{
		if($this->frame_queue != null)
			return array_pop($this->frame_queue);
		return $this->connection->wait_channel($this->channel_id);
	}
	protected function send_method_frame($method_sig, $args="")	{
		$this->connection->send_channel_method_frame($this->channel_id, $method_sig, $args);
	}
	function wait_content()										{
		$frm 				= $this->next_frame();
		$frame_type 		= $frm[0];
		$payload 			= $frm[1];
		if($frame_type 		!= 2)
			throw new Exception("Expecting Content header");
		$payload_reader 	= new RABBIT_AMQP_Serialize_Read(substr($payload,0,12));
		$class_id 			= $payload_reader->read_short();
		$weight 			= $payload_reader->read_short();
		$body_size 			= $payload_reader->read_longlong();
		$msg 				= new RABBIT_Message();
		$msg->load_properties(substr($payload,12));
		$body_parts 		= array();
		$body_received 		= 0;
		while(bccomp($body_size,$body_received)==1)				{
			$frm 			= $this->next_frame();
			$frame_type 	= $frm[0];
			$payload 		= $frm[1];
			if($frame_type 	!= 3)
				throw new Exception("Expecting Content body, received frame type $frame_type");
			$body_parts[] 	= $payload;
			$body_received 	= bcadd($body_received, strlen($payload));
		}
		$msg->body 			= implode("",$body_parts);
		if($this->auto_decode and isset($msg->content_encoding)){
			try 												{
				$msg->body 	= $msg->body->decode($msg->content_encoding);
			} catch (Exception $e) {}
		}
		return $msg;
	}
	public function wait($allowed_methods=null)					{
		/**
		* Wait for some expected AMQP methods and dispatch to them.
		* Unexpected methods are queued up for later calls to this Python
		* method.
		*/
		//Process deferred methods
		foreach($this->method_queue as $qk=>$queued_method)		{
			$method_sig = $queued_method[0];
			if($allowed_methods== null || in_array($method_sig, $allowed_methods)){
				unset($this->method_queue[$qk]);
				return $this->dispatch($queued_method[0],$queued_method[1],$queued_method[2]);
			}
		}
		while(true)												{
			$frm = $this->next_frame();
			$frame_type = $frm[0];
			$payload = $frm[1];
			if($frame_type != 1)
				throw new Exception("Expecting AMQP method, received frame type: $frame_type");
			if(strlen($payload) < 4)
				throw new Exception("Method frame too short");
			$method_sig_array = unpack("n2", substr($payload,0,4));
			$method_sig = "" . $method_sig_array[1] . "," . $method_sig_array[2]; 
			$args = new RABBIT_AMQP_Serialize_Read(substr($payload,4));
			if(in_array($method_sig, RABBIT_AMQP_Abstract::$CONTENT_METHODS))
				$content = $this->wait_content();
			else
				$content = null;
			if($allowed_methods==null ||
				in_array($method_sig,$allowed_methods) ||
				in_array($method_sig,RABBIT_AMQP_Abstract::$CLOSE_METHODS))
					return $this->dispatch($method_sig, $args, $content);
			array_push($this->method_queue,array($method_sig, $args, $content));
		}
	}
}
