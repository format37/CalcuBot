<?php

function checkRequest($req)
{
	//todo: sin,cos,pow
	$grantedSymbols = array('0','1','2','3','4','5','6','7','8','9','+','-','/','*','.','(',')',' ');
	$querySymbols	= str_split($req);
	$passed = true;
	$len = mb_strlen($req);
    for ($i = 0; $i < $len; $i++) 
    {
    	if (array_search($querySymbols[$i],$grantedSymbols)===false) 
    	{
    		$passed	= false;
    		break;
    	}
    }
	
	return $passed;
}

function prepareStringForReturn($value)
{
	$value	= str_replace('+','%2B',$value);
	$value	= str_replace(' ','%20',$value);
	return $value;
}

$json = file_get_contents('php://input');
$action = json_decode($json, true);
//file_put_contents('log.txt',$action['message']['chat']['id']."#".$action['message']['text']."\n",FILE_APPEND);
$message	= $action['message']['text'];
$chat		= $action['message']['chat']['id'];
$token		= 'SECRET_TOKEN';

if ($message=='/help@CalcuBot'||$message=='/help') 
	{
	$AnswerText	= "Hi! im console calculator.%0aAsk me like this: /cl4-(3+2)/3%0aSupported symbols:%200 1 2 3 4 5 6 7 8 9 + - / * . , ( )%0afunctions sin,cos,pow is coming soon!";
	file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));
	}
	
if ($message=='/about@CalcuBot'||$message=='/about') 
	{
	$AnswerText	= "Developer Alexey Yurasov%0aformat37@gmail.com%0a@AlexMoscow";
	file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));
	}
	
$crop=0;
if (substr($message,0,12)=='/cl@CalcuBot') $crop=12;
if (substr($message,0,3)=='/cl') $crop=3;
$source = substr($message,$crop);
$source	= str_replace(',','.',$source);
$badRequest	= false;
if ($crop) 
	{
	if (checkRequest($source)) 
		{
		$result	= 0;
		if (eval('$result = '.$source.';')===false) $badRequest=true;
		else 
			{
			$source = ' = '.$source;
			file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text='.$result.prepareStringForReturn($source));
			}
		}
	else
		{
		$badRequest	= true;
		}
	}
if ($badRequest) file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text=Bad%20request:%20'.prepareStringForReturn($source).'%0atype%20/help@CalcuBot');
?>
