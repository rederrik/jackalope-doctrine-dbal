<?php

namespace Jackalope\Transport\DoctrineDBAL\Util;

/**
 * Xpath utilities
 *
 */
class Xpath 
{

    /**
     * @param $query
     * @return string
     */
    public static function escapeBackslashes($query)
    {
        return str_replace('\\', '\\\\', $query);
        // Escape backslahes that aren't escape characters for quotes
        return preg_replace('/([\\\\]+)([^"|\\\']{1})?/', '\1\1\2', $query);
    }

    /**
     * Escapes a string to be used in an xpath query
     * There is a lot of double escaping here because we use single
     * quote in the EXTRACTVALUE functions
     *
     * The purpose of this method, is to escape a string quotes within a xpath expression
     * which can be kind-of hard.
     *
     * Example:
     *   query: Foo isn't bar
     *   result: concat("Foo isn", "'", "t bar")
     *
     * @param $query
     * @param string $enclosure
     * @return string
     */
    public static function escape($query, $enclosure = '"', $doubleEscapeSingleQuote = true)
    {
        $escapeSingleQuote = $doubleEscapeSingleQuote ? '"\'%s"' : '"%s"';
        $escapeDoubleQuote = $doubleEscapeSingleQuote ? "''%s''" : "'%s'";

        if ((strpos($query, '\'') !== false) ||
            (strpos($query, '"') !== false))
        {
            $quotechars = array('\'','"');
            $parts = array();
            $current = '';

            foreach (str_split($query) as $character) {

                if (in_array($character, $quotechars)) {
                    if ('' !== $current) {
                        $parts[] = $enclosure . $current . $enclosure;
                    }

                    if ($character == '\'') {
                        $parts[] = sprintf($escapeSingleQuote, $character);
                    } else {
                        $parts[] = sprintf($escapeDoubleQuote, $character);
                    }

                    $current = '';
                } else {
                    $current .= $character;
                }

            }

            if ($current) {
                $parts[] =  $enclosure . $current . $enclosure;
            }

            if (count($parts) > 2) {
                $part1 = array_shift($parts);
                $ret = 'concat(' . $part1 . ', ' . self::concatBy2($parts) . ')';
            } else {
                $ret = 'concat(' . join(', ', $parts) . ')';
            }
        } else {
            $ret = $enclosure . $query . $enclosure;
        }

        return $ret;
    }

    /**
     * Because not all concat() implementations support more then 2 arguments,
     * we need this recursive function
     *
     * @param array $parts
     * @return string
     */
    public static function concatBy2(array $parts)
    {
        if (2 === count($parts)) {
            return sprintf('concat(%s, %s)', $parts[0], $parts[1]);
        }

        $part1 = array_shift($parts);

        return 'concat(' . $part1 . ', ' . self::concatBy2($parts) . ')';
    }

}