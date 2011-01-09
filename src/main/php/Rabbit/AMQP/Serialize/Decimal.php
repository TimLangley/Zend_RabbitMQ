<?php
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

class Rabbit_AMQP_Serialize_Decimal
{
    private $n;
    private $e;

    public function __construct($n, $e)
    {
        if ($e < 0) {
            throw new Rabbit_Exception(
                Rabbit_Exception::ERROR_SERIALIZE_EXPONENT
            );
        }
        
        $this->n = $n;
        $this->e = $e;
    }

    public function asBCvalue()
    {
        return bcdiv($this->n, bcpow(10, $this->e));
    }
}
