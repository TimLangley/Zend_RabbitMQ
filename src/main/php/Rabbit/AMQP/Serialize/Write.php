<?
/**
 * Rabbit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://github.com/canddi/Zend_RabbitMQ/blob/master/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to hello@canddi.com so we can send you a copy immediately.
 *
 */

/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

/**
 * AMQP protocol serialization/deserialization to/from wire format.
 *
 * http://code.google.com/p/php-amqplib/
 * Vadim Zaliva <lord@crocodile.org>
 *
 *
 * To understand all signed/unsinged and 32/64 bit madness in this
 * code, please read first the following article:
 *
 * http://www.mysqlperformanceblog.com/2007/03/27/integers-in-php-running-with-scissors-and-portability/
 */

/**
 * AMQP protocol decimal value.
 *
 * Values are represented as (n,e) pairs. The actual value
 * is n * 10^(-e).
 *
 * From 0.8 spec: Decimal values are
 * not intended to support floating point values, but rather
 * business values such as currency rates and amounts. The
 * 'decimals' octet is not signed.
 */

class Rabbit_AMQP_Serialize_Write
{
    private $out = "";
    private $bits = array();
    private $bitcount = 0;

    private static function chrbytesplit($x, $bytes)
    {
        return array_map('chr',
            Rabbit_AMQP_Serialize_Write::bytesplit($x, $bytes));
    }

    private static function bytesplit($x, $bytes)
    {
        /**
         * Splits number (could be either int or string) into array of byte
         * values (represented as integers) in big-endian byte order.
         */
        if (is_int($x)) {
            if ($x < 0) {
                $x = sprintf("%u", $x);
            }
        }

        $res = array();
        for ($i = 0; $i < $bytes; $i++) {
            $b = bcmod($x, '256');
            array_unshift($res, (int) $b);
            $x = bcdiv($x, '256');
        }
        if ($x != 0) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_NOT_ZERO
            );
        }
        
        return $res;
    }

    private function flushbits()
    {
        if (count($this->bits)) {
            $this->out .= implode("", array_map('chr', $this->bits));
            $this->bits = array();
            $this->bitcount = 0;
        }
    }

    public function getvalue()
    {
        /**
         * Get what's been encoded so far.
         */
        $this->flushbits();
        
        return $this->out;
    }

    public function write($s)
    {
        /**
         * Write a plain Python string, with no special encoding.
         */
        $this->flushbits();
        $this->out .= $s;
    }

    public function write_bit($b)
    {
        /**
         * Write a boolean value.
         */
        $b = $b ? 1 : 0;
        
        $shift = $this->bitcount % 8;
        
        if ($shift == 0) {
            $last = 0;
        } else {
            $last = array_pop($this->bits);
        }
        
        $last |= ($b << $shift);
        array_push($this->bits, $last);
        
        $this->bitcount += 1;
    }

    public function write_octet($n)
    {
        /**
         * Write an integer as an unsigned 8-bit value.
         */
        if ($n < 0 || $n > 255) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_NOT_OCTAL
            );
        }
        
        $this->flushbits();
        $this->out .= chr($n);
    }

    public function write_short($n)
    {
        /**
         * Write an integer as an unsigned 16-bit value.
         */
        if ($n < 0 || $n > 65535) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_NOT_INTEGER
            );
        }
        
        $this->flushbits();
        $this->out .= pack('n', $n);
    }

    public function write_long($n)
    {
        /**
         * Write an integer as an unsigned 32-bit value.
         */
        $this->flushbits();
        $this->out .= implode("", self::chrbytesplit($n, 4));
    }

    private function write_signed_long($n)
    {
        $this->flushbits();
        // although format spec for 'N' mentions unsigned
        // it will deal with sinned integers as well. tested.
        $this->out .= pack('N', $n);
    }

    public function write_longlong($n)
    {
        /**
         * Write an integer as an unsigned 64-bit value.
         */
        $this->flushbits();
        $this->out .= implode("", self::chrbytesplit($n, 8));
    }

    public function write_shortstr($s)
    {
        /*
         * Write a string up to 255 bytes long after encoding.
         * Assume UTF-8 encoding.
         */
        $this->flushbits();
        
        if (strlen($s) > 255) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_STRING_TOO_LONG
            );
        }
    
        $this->write_octet(strlen($s));
        $this->out .= $s;
    }

    public function write_longstr($s)
    {
        /*
         * Write a string up to 2**32 bytes long.  Assume UTF-8 encoding.
         */
        $this->flushbits();
        $this->write_long(strlen($s));
        $this->out .= $s;
    }

    public function write_timestamp($v)
    {
        /**
         * Write unix time_t value as 64 bit timestamp.
         */
        $this->write_longlong($v);
    }

    public function write_table($d)
    {
        /**
         * Write PHP array, as table. Input array format: keys are strings,
         * values are (type,value) tuples.
         */
        $this->flushbits();
        $table_data = new Rabbit_AMQP_Serialize_Write();
        foreach ($d as $k => $va) {
            list($ftype, $v) = $va;
            $table_data->write_shortstr($k);
            if ($ftype == 'S') {
                $table_data->write('S');
                $table_data->write_longstr($v);
            } else if ($ftype == 'I') {
                $table_data->write('I');
                $table_data->write_signed_long($v);
            } else if ($ftype == 'D') {
                // 'D' type values are passed AMQPDecimal instances.
                $table_data->write('D');
                $table_data->write_octet($v->e);
                $table_data->write_signed_long($v->n);
            } else if ($ftype == 'T') {
                $table_data->write('T');
                $table_data->write_timestamp($v);
            } else if ($ftype = 'F') {
                $table_data->write('F');
                $table_data->write_table($v);
            }
        }
        $table_data = $table_data->getvalue();
        $this->write_long(strlen($table_data));
        $this->write($table_data);
    }
}
