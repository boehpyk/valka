<?php

namespace Util;

final class Validators{ 
	
	static function checkPassword($password, $min_length = 3)
	{
		return (preg_match('/^[a-z0-9_]{' . $min_length . ',}$/i', $password));
	}
    	
	
	static function validEmail($email)
   	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	
	static function checkNaturalNumber($number) //РїРѕР·РІРѕР»СЏРµС‚ РІРІРѕРґРёС‚СЊ С‚РѕР»СЊРєРѕ РЅР°С‚СѓСЂР°Р»СЊРЅС‹Рµ С‡РёСЃР»Р°
	{
		return (preg_match('/^[0-9_\.\,]{1,}$/i', $number));
	}

	static function checkLogin($login, $min_length = 6)
	{
		if (!preg_match('/^[a-z0-9_]{3,}$/i', $login)) {
		    return false;
		}
		return true;

	}
	static function checkInt($number)
	{
		if ((int)$number > 0) return true;

		return false;
	}
}
?>