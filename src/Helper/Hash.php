<?php
/**
 * EthereumNumber.php
 *
 * @author   Ilya Sinyakin <sinyakin.ilya@gmail.com>
 */

namespace SinyakinIlya\Ethereum\Helper;

final class Hash
{
    /**
     * ENCODE a HEX.
     *
     * See:
     * https://github.com/ethereum/wiki/wiki/JSON-RPC#hex-value-encoding
     *
     * @param String|Number - to encode.
     *
     * @return String - Encoded.
     *
     */
    public static function toHex($input)
    {

        if (is_numeric($input)) {
            $hex_str = self::largeDecHex($input);
        } elseif (is_string($input)) {
            $hex_str = self::strToHex($input);
        } else {
            throw new \InvalidArgumentException($input . ' is not a string or number.');
        }

        return '0x' . $hex_str;
    }
    /**
     * Decodes a HEX encoded number.
     *
     * See:
     * https://github.com/ethereum/wiki/wiki/JSON-RPC#hex-value-encoding
     *
     * @param string - String convert to number.
     *
     * @throws \InvalidArgumentException if the provided argument not a HEX number.
     *
     * @return Integer - Decoded number.
     *
     */
    public static function hexToNumber($input)
    {

        if (!self::hasHexPrefix($input)) {
            throw new \InvalidArgumentException($input . ' is not a prefixed hex string (0x).');
        }

        if (!self::isValidQuantity($input)) {
            throw new \InvalidArgumentException($input . ' is not a valid quantity.');
        }

        // Un-prefix.
        $hex_str = substr($input, 2);

        // Large Integer?
        $dec = 0;
        $len = strlen($hex_str);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex_str[$i - 1])), bcpow('16', strval($len - $i))));
        }

        return $dec;
    }

    /**
     * Tests if the given string HEX QUANTITY.
     *
     * See:
     * https://github.com/ethereum/wiki/wiki/JSON-RPC#hex-value-encoding
     *
     * @param string - String to test for Hex.
     *
     * @return bool - TRUE if string is a Valid Hex value or FALSE.
     */
    public static function isValidQuantity($str)
    {

        // Always ensure 0x prefix.
        if (!self::hasHexPrefix($str)) {
            return false;
        }

        // Should always have at least one digit - zero is "0x0"
        if (strlen($str) < 3) {
            return false;
        }

        return ctype_xdigit(substr($str, strlen('0x')));
    }

    /**
     * Tests if the given string is a HEX UNFORMATED DATA value.
     *
     * See:
     * https://github.com/ethereum/wiki/wiki/JSON-RPC#hex-value-encoding
     *
     * @param string - String to test for Hex.
     *
     * @return bool - TRUE if string is a Valid Hex value or FALSE.
     */
    public function isValidData($str)
    {

        // Always ensure 0x prefix.
        if (!self::hasHexPrefix($str)) {
            return false;
        }

        // Should always have at least one digit - zero is "0x0"
        if (strlen($str) <= 3) {
            return false;
        }

        // Ensure two hex digits per byte.
        if ((strlen($str) % 2 != 0)) {
            return false;
        }

        return ctype_xdigit(substr($str, strlen('0x')));
    }

    /**
     * isValidAddress().
     *
     * Tests if the given string qualifies as a Ethereum address.
     * (DATA, 20 Bytes - address)
     *
     * See:
     * https://github.com/ethereum/wiki/wiki/JSON-RPC
     *
     * @param string - String to test for Address.
     * @param Bool $throw - If TRUE we will throw en error.
     *
     * @return bool - TRUE if string is a Valid Address value or FALSE.
     */
    public static function isValidAddress($address, $throw = false)
    {

        // Always ensure 0x prefix.
        if (!self::hasHexPrefix($address)) {
            return false;
        }

        // Address should be 20bytes=40 HEX-chars + prefix.
        if (strlen($address) !== 42) {
            return false;
        }
        $return = ctype_xdigit(substr($address, strlen('0x')));

        if (!$return && $throw) {
            throw new \InvalidArgumentException($address . ' has invalid format.');
        }

        return $return;
    }

    /**
     * Test if a string is prefixed with "0x".
     *
     * @param string - String to test prefix.
     *
     * @return bool - TRUE if string has "0x" prefix or FALSE.
     */
    public static function hasHexPrefix($str)
    {
        $prefix = '0x';

        return substr($str, 0, strlen($prefix)) === $prefix;
    }

    /**
     * Converts a string to Hex.
     *
     * @param String - to be converted.
     *
     * @return string - HEX representation.
     */
    public static function strToHex($string)
    {
        $hex = unpack('H*', $string);

        return array_shift($hex);
    }

    /**
     * Converts Hex to string.
     *
     * @param String - Hex string to be converted.
     *
     * @return String - if address can't be decoded.
     * @throws \Exception
     */
    public static function hexToAddress($string)
    {
        $matches = array();

        if (!self::hasHexPrefix($string)) {
            $string = '0x' . $string;
        }
        // Remove leading zeros.
        // See: https://regex101.com/r/O2Rpei/1
        if (preg_match('/^0x[0]*([1-9,a-f][0-9,a-f]*)$/is', $string, $matches)) {
            $address = '0x' . $matches[1];
            if (self::isValidAddress($address)) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Converts float number to Hex.
     *
     * @param float - to be converted to HEX
     *
     * @return string -  String with hexadecimal representation.
     */
    public static function largeDecHex($dec)
    {
        $hex = '';
        do {
            $last = bcmod($dec, 16);
            $hex  = dechex($last) . $hex;
            $dec  = bcdiv(bcsub($dec, $last), 16);
        } while ($dec > 0);

        return $hex;
    }
}