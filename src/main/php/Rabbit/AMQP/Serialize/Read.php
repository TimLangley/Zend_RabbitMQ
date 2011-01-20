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

class Rabbit_AMQP_Serialize_Read
{
    private $offset = 0;
    private $bitcount = 0;
    private $bits = 0;
    private $is64bits = false;

    private $str;
    private $sock = null;

    public function __construct($str, $sock = NULL)
    {
        $this->str = $str;
        $this->sock = $sock;
        $this->offset = 0;

        if (((int) 4294967296) != 0)
            $this->is64bits = true;

        if (!function_exists('bcmul')) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_BC_MATH);
        }
    }

    public function close()
    {
        if ($this->sock) {
            fclose($this->sock);
        }
    }

    public function read($n)
    {
        $this->bitcount = $this->bits = 0;
        return $this->rawread($n);
    }

    private function rawread($n)
    {
        if ($this->sock) {
            $res = '';
            $read = 0;

            while ($read < $n
                && (false !== ($buf = fread($this->sock, $n - $read)))) {
                $read += strlen($buf);
                $res .= $buf;
            }

            if (strlen($res) != $n) {
                throw new Rabbit_Exception(
                    sprintf(
                        Rabbit_Exception::ERROR_SERIALIZE_READING,
                        strlen($res),
                        $n
                    )
                );
            }

            $this->offset += $n;

        } else {

            if (strlen($this->str) < $n) {
                throw new Rabbit_Exception(
                    sprintf(
                        Rabbit_Exception::ERROR_SERIALIZE_READING,
                        strlen($this->str),
                        $n
                    )
                );
            }

            $res = substr($this->str, 0, $n);
            $this->str = substr($this->str, $n);
            $this->offset += $n;
        }

        return $res;
    }

    public function read_bit()
    {
        if (!$this->bitcount) {
            $this->bits = ord($this->rawread(1));
            $this->bitcount = 8;
        }

        $result = ($this->bits & 1) == 1;
        $this->bits >>= 1;
        $this->bitcount -= 1;

        return $result;
    }

    public function read_octet()
    {
        $this->bitcount = $this->bits = 0;
        list(, $res) = unpack('C', $this->rawread(1));

        return $res;
    }

    public function read_short()
    {
        $this->bitcount = $this->bits = 0;
        list(, $res) = unpack('n', $this->rawread(2));

        return $res;
    }

    public function read_php_int()
    {
        /**
         * Reads 32 bit integer in big-endian byte order.
         *
         * On 64 bit systems it will return always usngined int
         * value in 0..2^32 range.
         *
         * On 32 bit systems it will return signed int value in
         * -2^31...+2^31 range.
         *
         * Use with caution!
         */
        list(, $res) = unpack('N', $this->rawread(4));

        if ($this->is64bits) {
            $sres = sprintf("%u", $res);
            return (int) $sres;
        } else {
            return $res;
        }

    }

    public function read_long()
    {
        // PHP does not have unsigned 32 bit int,
        // so we return it as a string
        $this->bitcount = $this->bits = 0;
        list(, $res) = unpack('N', $this->rawread(4));
        $sres = sprintf("%u", $res);

        return $sres;
    }

    private function read_signed_long()
    {
        $this->bitcount = $this->bits = 0;
        // In PHP unpack('N') always return signed value,
        // on both 32 and 64 bit systems!
        list(, $res) = unpack('N', $this->rawread(4));

        return $res;
    }

    public function read_longlong()
    {
        // Even on 64 bit systems PHP integers are singed.
        // Since we need an unsigned value here we return it
        // as a string.
        $this->bitcount = $this->bits = 0;
        $hi = unpack('N', $this->rawread(4));
        $lo = unpack('N', $this->rawread(4));
        // workaround signed/unsigned braindamage in php
        $hi = sprintf("%u", $hi[1]);
        $lo = sprintf("%u", $lo[1]);
        return bcadd(bcmul($hi, "4294967296"), $lo);
    }

    public function read_shortstr()
    {
        /**
         * Read a utf-8 encoded string that's stored in up to
         * 255 bytes.  Return it decoded as a Python unicode object.
         */
        $this->bitcount = $this->bits = 0;
        list(, $slen) = unpack('C', $this->rawread(1));

        return $this->rawread($slen);
    }

    public function read_longstr()
    {
        /**
         * Read a string that's up to 2**32 bytes, the encoding
         * isn't specified in the AMQP spec, so just return it as
         * a plain PHP string.
         */
        $this->bitcount = $this->bits = 0;
        $slen = $this->read_php_int();

        if ($slen < 0) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_STRING_TOO_LONG
            );
        }

        return $this->rawread($slen);
    }

    function read_timestamp()
    {
        /**
         * Read and AMQP timestamp, which is a 64-bit integer representing
         * seconds since the Unix epoch in 1-second resolution.
         */
        return $this->read_longlong();
    }

    public function read_table()
    {
        /**
         * Read an AMQP table, and return as a PHP array. keys are strings,
         * values are (type,value) tuples.
         */
        $this->bitcount = $this->bits = 0;
        $tlen = $this->read_php_int();

        if ($tlen < 0) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_TABLE
            );
        }

        $table_data = new Rabbit_AMQP_Serialize_Read($this->rawread($tlen));
        $result = array();

        while ($table_data->tell() < $tlen) {
            $name = $table_data->read_shortstr();
            $ftype = $table_data->rawread(1);
            if ($ftype == 'S') {
                $val = $table_data->read_longstr();
            } else if ($ftype == 'I') {
                $val = $table_data->read_signed_long();
            } else if ($ftype == 'D') {
                $e = $table_data->read_octet();
                $n = $table_data->read_signed_long();
                $val = new Rabbit_AMQP_Serialize_Decimal($n, $e);
            } else if ($ftype == 'T') {
                $val = $table_data->read_timestamp();
            } else if ($ftype == 'F') {
                $val = $table_data->read_table(); // recursion
            } else {
                $val = NULL;
            }
            $result[$name] = array(
                    $ftype, $val
                );
        }

        return $result;
    }

    protected function tell()
    {
        return $this->offset;
    }

}
