<?PHP
/**
 * patTemplate modfifier Dateformat
 *
 * $Id: Dateformat.php 440 2008-03-30 09:00:16Z fishbone $
 *
 * @package		patTemplate
 * @subpackage	Modifiers
 * @author		Stephan Schmidt <schst@php.net>
 */

/**
 * patTemplate modfifier Dateformat
 *
 * formats dates and times according to a format string.
 *
 * Possible attributes are:
 * - format (string)
 *
 * See the PHP documentation for strftime() for
 * more information.
 *
 * @package		patTemplate
 * @subpackage	Modifiers
 * @author		Stephan Schmidt <schst@php.net>
 * @link		http://www.php.net/manual/en/function.strftime.php
 */
class patTemplate_Modifier_Dateformat extends patTemplate_Modifier
{
   /**
	* modify the value
	*
	* @access	public
	* @param	string		value
	* @return	string		modified value
	*/
	function modify( $value, $params = array() )
	{
        if (!preg_match('/^[0-9]+$/', $value)) {
        	$value = strtotime($value);
        }
         
        if (isset($params['unix']) && $params['unix'] == 'yes') {
            return $value;
        }
   
        if (!isset($params['format'])) {
            return $value;
        }

		return strftime($params['format'], $value);
	}
}
?>