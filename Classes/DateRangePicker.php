<?php
namespace Ks\AdminLteThemeBundle\Classes;
use Ks\CoreBundle\Classes\DbAbs;

abstract class DateRangePicker
{
	public static function parseValue($value)
    {
		// Format is "From - To"
		$value = explode('-', $value);
		// From
		$value[0] = DbAbs::toDoctrineDT(trim($value[0]));
		// To
		$value[1] = DbAbs::toDoctrineDT(trim($value[1]));
		
		return $value;
	}
}