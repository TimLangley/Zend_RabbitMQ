<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class RABBIT_AMQP_Connection extends RABBIT_AMQP_Abstract				{
    public static $AMQP_PROTOCOL_HEADER = "AMQP\x01\x01\x09\x01";
    public static $LIBRARY_PROPERTIES = array(
        "library" => array('S', "PHP Simple AMQP lib"),
        "library_version" => array('S', "0.1")
    );

    protected $method_map = array(
        "10,10" => "start",
        "10,20" => "secure",
        "10,30" => "tune",
        "10,41" => "open_ok",
        "10,50" => "redirect",
        "10,60" => "_close",
        "10,61" => "close_ok"
    );
    
    public function __construct ( $host
								, $port
								, $user
								, $password
								, $vhost				= "/"
								, $insist				= false
								, $login_method			= "AMQPLAIN"
								, $login_response		= null
								, $locale				= "en_US"
								, $connection_timeout 	= 3
								, $read_write_timeout 	= 3)
    {
        if($user && $password)
        {
            $login_response = new RABBIT_AMQP_Serialize_Write();
            $login_response->write_table(array("LOGIN" => array('S',$user),
                                               "PASSWORD" => array('S',$password)));
            $login_response = substr($login_response->getvalue(),4); //Skip the length
        } else
            $login_response = null;
        

        $d = RABBIT_AMQP_Connection::$LIBRARY_PROPERTIES;
        while(true)
        {
            $this->channels = array();
            // The connection object itself is treated as channel 0
            parent::__construct($this, 0);
            
            $this->channel_max = 65535;
            $this->frame_max = 131072;

            $errstr = $errno = null;
            $this->sock = null;
            if (!($this->sock = fsockopen($host,$port,$errno,$errstr,$connection_timeout)))
            {
                throw new Exception("Error Connecting to server($errno): $errstr ");
            }
            
            stream_set_timeout($this->sock, $read_write_timeout);
            stream_set_blocking($this->sock, 1);
            $this->input = new RABBIT_AMQP_Serialize_Read(null, $this->sock);

            $this->write(RABBIT_AMQP_Connection::$AMQP_PROTOCOL_HEADER);
            $this->wait(array("10,10"));        
            $this->x_start_ok($d, $login_method, $login_response, $locale);
        
            $this->wait_tune_ok = true;
            while($this->wait_tune_ok)
            {
                $this->wait(array(
                                "10,20", // secure
                                "10,30", // tune
                            ));
            }

            $host = $this->x_open($vhost,"", $insist);
            if(!$host)
                return; // we weren't redirected

            @fclose($this->sock); $this->sock=null;
        }
    }
   
    public function __destruct()
    {
        if(isset($this->input))
            if($this->input)
                $this->close();

        if($this->sock)
          @fclose($this->sock);
    }

    protected function write($data)
    {
        $len = strlen($data);
        while(true)
        {
            if(false == ($written = fwrite($this->sock, $data)))
            {
                throw new Exception("Error sending data");
            }
            $len = $len - $written;
            if($len>0)
                $data=substr($data,0-$len);
            else
                break;
        }
    }
    
    protected function do_close()
    {
        if(isset($this->input))
            if($this->input)
            {
                $this->input->close();
                $this->input = null;
            }
        
        if($this->sock)
        {
            @fclose($this->sock);
            $this->sock = null;
        }
    }

    public function get_free_channel_id()
    {
        for($i=1;$i<=$this->channel_max;$i++)
            if(!array_key_exists($i,$this->channels))
                return $i;
        throw new Exception("No free channel ids");
    }

    public function send_content($channel, $class_id, $weight, $body_size,
                        $packed_properties, $body)
    {
        $pkt = new RABBIT_AMQP_Serialize_Write();

        $pkt->write_octet(2);
        $pkt->write_short($channel);
        $pkt->write_long(strlen($packed_properties)+12);

        $pkt->write_short($class_id);
        $pkt->write_short($weight);
        $pkt->write_longlong($body_size);
        $pkt->write($packed_properties);

        $pkt->write_octet(0xCE);
        $pkt = $pkt->getvalue();
        $this->write($pkt);
        
        while($body)
        {
            $payload = substr($body,0, $this->frame_max-8);
            $body = substr($body,$this->frame_max-8);
            $pkt = new RABBIT_AMQP_Serialize_Write();

            $pkt->write_octet(3);
            $pkt->write_short($channel);
            $pkt->write_long(strlen($payload));
            
            $pkt->write($payload);
            
            $pkt->write_octet(0xCE);
            $pkt = $pkt->getvalue();
            $this->write($pkt);
        }
    }

    protected function send_channel_method_frame($channel, $method_sig, $args="")
    {
        if($args instanceof RABBIT_AMQP_Serialize_Write)
            $args = $args->getvalue();

        $pkt = new RABBIT_AMQP_Serialize_Write();

        $pkt->write_octet(1);
        $pkt->write_short($channel);
        $pkt->write_long(strlen($args)+4);  // 4 = length of class_id and method_id
        // in payload

        $pkt->write_short($method_sig[0]); // class_id
        $pkt->write_short($method_sig[1]); // method_id
        $pkt->write($args);

        $pkt->write_octet(0xCE);
        $pkt = $pkt->getvalue();
        $this->write($pkt);

    }

    /**
     * Wait for a frame from the server
     */
    protected function wait_frame()
    {
        $frame_type = $this->input->read_octet();
        $channel = $this->input->read_short();
        $size = $this->input->read_long();
        $payload = $this->input->read($size);
        
        $ch = $this->input->read_octet();
        if($ch != 0xCE)
            throw new Exception(sprintf("Framing error, unexpected byte: %x", $ch));
        
        return array($frame_type, $channel, $payload);
    }

    /**
     * Wait for a frame from the server destined for
     * a particular channel.
     */
    protected function wait_channel($channel_id)
    {
        while(true)
        {
            list($frame_type, $frame_channel, $payload) = $this->wait_frame();
            if($frame_channel == $channel_id)
                return array($frame_type, $payload);

            // Not the channel we were looking for.  Queue this frame
            //for later, when the other channel is looking for frames.
            array_push($this->channels[$frame_channel]->frame_queue,
                       array($frame_type, $payload));
            
            // If we just queued up a method for channel 0 (the Connection
            // itself) it's probably a close method in reaction to some
            // error, so deal with it right away.
            if(($frame_type == 1) && ($frame_channel == 0))
                $this->wait();
        }
    }

    /**
     * Fetch a Channel object identified by the numeric channel_id, or
     * create that object if it doesn't already exist.
     */
    public function channel($channel_id=null)
    {
        if(array_key_exists($channel_id,$this->channels))
            return $this->channels[$channel_id];
        
        return new RABBIT_AMQP_Channel($this->connection, $channel_id);
    }

    /**
     * request a connection close
     */
    public function close($reply_code=0, $reply_text="", $method_sig=array(0, 0))
    {
        $args = new RABBIT_AMQP_Serialize_Write();
        $args->write_short($reply_code);
        $args->write_shortstr($reply_text);
        $args->write_short($method_sig[0]); // class_id
        $args->write_short($method_sig[1]); // method_id
        $this->send_method_frame(array(10, 60), $args);
        return $this->wait(array("10,61"));
    }

    protected function _close($args)
    {
        $reply_code = $args->read_short();
        $reply_text = $args->read_shortstr();
        $class_id = $args->read_short();
        $method_id = $args->read_short();

        $this->x_close_ok();
        
        throw new Exception($reply_text);
			//RABBIT_AMQP_ConnectionException($reply_code, $reply_text, array($class_id, $method_id));
    }


    /**
     * confirm a connection close
     */
    protected function x_close_ok()
    {
        $this->send_method_frame(array(10, 61));
        $this->do_close();
    }

    /**
     * confirm a connection close
     */
    protected function close_ok($args)
    {
        $this->do_close();
    }

    protected function x_open($virtual_host, $capabilities="", $insist=false)
    {
        $args = new RABBIT_AMQP_Serialize_Write();
        $args->write_shortstr($virtual_host);
        $args->write_shortstr($capabilities);
        $args->write_bit($insist);
        $this->send_method_frame(array(10, 40), $args);
        return $this->wait(array(
                               "10,41", // Connection.open_ok
                               "10,50"  // Connection.redirect
                           ));
    }


    /**
     * signal that the connection is ready
     */
    protected function open_ok($args)
    {
        $this->known_hosts = $args->read_shortstr();
        
        return null;
    }


    /**
     * asks the client to use a different server
     */
    protected function redirect($args)
    {
        $host = $args->read_shortstr();
        $this->known_hosts = $args->read_shortstr();
        
        return $host;
    }

    /**
     * security mechanism challenge
     */
    protected function secure($args)
    {
        $challenge = $args->read_longstr();
    }

    /**
     * security mechanism response
     */
    protected function x_secure_ok($response)
    {
        $args = new RABBIT_AMQP_Serialize_Write();
        $args->write_longstr($response);
        $this->send_method_frame(array(10, 21), $args);
    }

    /**
     * start connection negotiation
     */
    protected function start($args)
    {
        $this->version_major = $args->read_octet();
        $this->version_minor = $args->read_octet();
        $this->server_properties = $args->read_table();
        $this->mechanisms = explode(" ", $args->read_longstr());
        $this->locales = explode(" ", $args->read_longstr());        
    }
    
    
    protected function x_start_ok($client_properties, $mechanism, $response, $locale)
    {
        $args = new RABBIT_AMQP_Serialize_Write();
        $args->write_table($client_properties);
        $args->write_shortstr($mechanism);
        $args->write_longstr($response);
        $args->write_shortstr($locale);
        $this->send_method_frame(array(10, 11), $args);
    }

    /**
     * propose connection tuning parameters
     */
    protected function tune($args)
    {
        $v=$args->read_short();
        if($v)
            $this->channel_max = $v;
        $v=$args->read_long();
        if($v)
            $this->frame_max = $v;
        $this->heartbeat = $args->read_short();

        $this->x_tune_ok($this->channel_max, $this->frame_max, 0);
    }

    /**
     * negotiate connection tuning parameters
     */
    protected function x_tune_ok($channel_max, $frame_max, $heartbeat)
    {
        $args = new RABBIT_AMQP_Serialize_Write();
        $args->write_short($channel_max);
        $args->write_long($frame_max);
        $args->write_short($heartbeat);
        $this->send_method_frame(array(10, 31), $args);
        $this->wait_tune_ok = False;
    }

}
