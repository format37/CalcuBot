<html>
<head>
<meta charset="utf-8">
</head>
<?php

if (file_exists('error_log')) unlink('error_log');

/* Combinations */
function ncr($n, $r)
{
  if ($r > $n)
  {
    return NaN;
  }
  if (($n - $r) < $r)
  {
    return ncr($n, ($n - $r));
  }
  $return = 1;
  for ($i = 0; $i < $r; $i++)
  {
    $return *= ($n - $i) / ($i +1);
  }
  return $return;
}

/* Permutations */
function npr($n, $r)
{
  if ($r > $n)
  {
    return NaN;
  }
  if ($r)
  {
    return $n * (npr($n -1, $r -1));
  }
  else
  {
    return 1;
  }
}

/* number_format */
function nf( float $number , int $decimals = 0 , string $dec_point = "." , string $thousands_sep = "," )
{
	return number_format($number,$decimals,$dec_point,$thousands_sep);
}

function SqlQuery($query)
{
	$host	= "host";
	$user	= "user";
	$pwd	= "pass";
	$dbase	= "dbase";
	$answerLine	= "";

	$link = mysqli_connect($host, $user, $pwd, $dbase);

	/* check connection */
	if (mysqli_connect_errno()) {
	    $answerLine	= $answerLine."Unable to connect DB: %s\n".mysqli_connect_error();
	    exit();
	}

	/* run multiquery */
	if (mysqli_multi_query($link, $query)) {
	    //do {
	        /* get first result data */
	        if ($result = mysqli_store_result($link)) {
	            while ($row = mysqli_fetch_row($result)) {
	                $answerLine	= $answerLine.implode(' # ',$row)."\n";
	            }
	            mysqli_free_result($result);
	        }
	        /* print divider */
	        if (mysqli_more_results($link)) {
	        	$answerLine	= $answerLine."### ";
	        }

	    //} while (mysqli_next_result($link));
	}
	/* close connection */
	mysqli_close($link);

	return $answerLine;

}

class curl
{
    private $curl_obj;
    public function __construct()
    {
        if(!function_exists('curl_init'))
        {
            echo 'ERROR: Install CURL module for php';
            exit();
        }
        $this->init();
    }
    public function init()
    {
        $this->curl_obj = curl_init();
    }
    public function request($url, $method = 'GET', $params = array(), $opts = array())
    {
        $method = trim(strtoupper($method));
        // default opts
        $opts[CURLOPT_FOLLOWLOCATION] = true;
        $opts[CURLOPT_RETURNTRANSFER] = 1;
        $opts[CURLOPT_SSL_VERIFYPEER] = true;
        $opts[CURLOPT_CAINFO] = "curl-ca-bundle.crt";
        if($method==='GET')
	{
		$url .= "?".$params;
		$params = http_build_query($params);
	}
        elseif($method==='POST')
        {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
        }
        $opts[CURLOPT_URL] = $url;
		curl_setopt_array($this->curl_obj, $opts);
        $content = curl_exec($this->curl_obj);
        if ($content===false) echo 'Ошибка curl: ' . curl_error($this->curl_obj);
        return $content;
    }
    public function close()
    {
        if(gettype($this->curl_obj) === 'resource')
            curl_close($this->curl_obj);
    }
    public function __destruct()
    {
        $this->close();
    }
}

function exec_curl_request($handle) {
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($handle, CURLOPT_CAINFO, "curl-ca-bundle.crt");
  $response = curl_exec($handle);
  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;
  $moken=preg_replace('/\s+/', '', SqlQuery('SELECT matrix.mind FROM `matrix` as matrix where matrix.person="calcubot"'));
  $apiu="https://api.telegram.org/bot$moken/";
  $handle = curl_init($apiu);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}

function addInlineResult($resultId,$title,$message_text)
{
	global $results;
	$input_message_content=array("message_text"=>$message_text);
	$results[]=array(
            "type" => "article",
            "id" => $resultId,
            "title" => $title,
            //"message_text" => $message_text
            "input_message_content"=>$input_message_content
          );
    return $results;
}
//CALCUBOT
function strExists($value, $string)
{
    foreach ((array) $value as $v) {
        if (false !== strpos($string, $v)) return true;
    }
}

function checkWords($query)
{
	$query=strToLower($query);
	//get words list
	$wordSymbols='Aabcdefghijklmnopqrstuvwxyz_CP';	
	$data=str_split($query);
	$j=0;
	$badWords="";
	$words[$j]="";
	for ($i=0;$i<count($data);$i++)
	{
		if (!strpos($wordSymbols,$data[$i])===false)
		{
			$words[$j].=$data[$i];
		}
		elseif (strlen($words[$j])>0) 
		{
			$j++;
			$words[$j]="";
		}
	}
	$goodWords=
		[
		'cl',
		'help',
		'help@CalcuBot',
		'about',
		'example',
		'functions',
		'abs',
		'acos',
		'acosh',
		'asin',
		'asinh',
		'atan2',
		'atan',
		'atanh',
		'base_convert',
		'bindec',
		'ceil',
		'cos',
		'cosh',
		'decbin',
		'dechex',
		'decoct',
		'deg2rad',
		'exp',
		'floor',
		'fmod',
		'getrandmax',
		'hexdec',
		'hypot',
		'is_finite',
		'is_infinite',
		'is_nan',
		'lcg_value',
		'log10',
		'log',
		'max',
		'min',
		'mt_getrandmax',
		'mt_rand',
		'octdec',
		'pi',
		'pow',
		'rad2deg',
		'rand',
		'round',
		'sin',
		'sinh',
		'sqrt',
		'srand',
		'tan',
		'tanh',
		'ncr',
		'npr',
		'number_format',
		'nf'
		];
	$otherSymbols='Aabcdefghijklmnopqrstuvwxyz01234567890!%^:&?*/()[]{}_-+';
	$splittedSymbols=str_split($otherSymbols);
	for ($i=0;$i<count($words);$i++)
	{
		//check: is words are good?
		for ($i=0;$i<count($words);$i++)
		{	
			if (strlen($words[$i])&&array_search($words[$i],$goodWords)===false) $badWords=$badWords.$words[$i]." ";
		}
		//check: is symbols are good?
		for ($i=0;$i<count($query);$i++)
		{
			//select where only accepted symbols
			if (array_search($query[$i],$splittedSymbols)===false) $badWords=$badWords.$query[$i].".";
		}
	}
	return $badWords;
}

function myeval($mycode)
{
	$res='';
	$subCount	= substr_count($mycode,"**");
	if (substr_count($mycode,"**")>0)	//===== contains%%
	{
		if((substr_count($mycode,"**")%2)==1) return false;
		else
		{
			$isText	= true;
			$queryParts	= explode("**",$mycode);
			foreach ($queryParts as $part)
			{
				if ($isText) $res.=$part;
				else
				{
					$calculation=0;
					$wrongWords	= checkWords($part);
					if (strlen(str_replace(' ', '', $wrongWords))) $calculation.='Wrong words: '.$wrongWords;
					elseif (eval('$calculation='.str_replace(' ','',strtolower($part)).';')===false) return false;
					$res.=$calculation;
				}
				$isText=!$isText;
			}
		}
	}
	else								//===== not contains %%
	{
		$wrongWords	= checkWords($mycode);
		if (strlen(str_replace(' ', '', $wrongWords))) return 'Wrong words: '.$wrongWords;
		elseif (eval('$res='.$mycode.";")===false) return false;
	}
	return $res;
}

function sendMessage($chatID, $messaggio) {
	$moken=preg_replace('/\s+/', '', SqlQuery('SELECT matrix.mind FROM `matrix` as matrix where matrix.person="calcubot"'));
    $url = "https://api.telegram.org/bot" . $moken . "/sendMessage?chat_id=" . $chatID;
    $url = $url . "&text=" . urlencode($messaggio);
    $ch = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
    );
    curl_setopt_array($ch, $optArray);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

//START
$content = file_get_contents("php://input");
$update = json_decode($content, true);
//PERSONAL OR GROUP MESSAGE
$AnswerText	= "empty";
if (isset($update["message"]))
{
		$chat	= $update["message"]['chat']['id'];
		$user	= $update["message"]['from']['id'];
		$message	= $update["message"]['text'];

		if ($message=='/help@calcubot'||$message=='/help'||$message=='/start')
			{
			$AnswerText	= "Hi! im console calculator based on PHP and his eval function";
			sendMessage($chat,$AnswerText);
			}

		if ($message=='/example@calcubot'||$message=='/example')
			{
			$AnswerText	= "/cl 2+2###4 = 2+2###/cl abs(-3)###3 = abs(-3)###/cl sin(4)###-0.75680249530793 = sin(4)###/cl atan2(3,4)###0.64350110879328 = atan2(3,4)###/cl base_convert(34,10,2)###100010 = base_convert(34,10,2)###/cl bindec(100101)###37 = bindec(100101)###/cl ceil(2.3)###3 = ceil(2.3)###/cl decbin(48)###110000 = decbin(48)###/cl dechex(250)###fa = dechex(250)###/cl decoct(9)###11 = decoct(9)###/cl deg2rad(3)###0.05235987755983 = deg2rad(3)###/cl exp(3)###20.085536923188 = exp(3)###/cl floor(2.7)###2 = floor(2.7)###/cl fmod(2,7)###2 = fmod(2,7)###/cl hexdec(fa)###250 = hexdec(fa)###/cl hypot(4,7)###8.0622577482985 = hypot(4,7)###/cl is_finite(1/0)###1 = is_finite(1/0)###/cl is_infinite(log(0))###1 = is_infinite(log(0))###/cl is_nan(acos(1.01))###1 = is_nan(acos(1.01))###/cl lcg_value()###0.38509916446214 = lcg_value()###/cl log10(7)###0.84509804001426 = log10(7)###/cl log(7,10)###0.84509804001426 = log(7,10)###/cl max(3,4,8,2,19,7)###19 = max(3,4,8,2,19,7)###/cl min(8,2,-9,17)###-9 = min(8,2,-9,17)###/cl mt_getrandmax()###2147483647 = mt_getrandmax()###/cl mt_rand()###1148623558 = mt_rand()###/cl octdec(11)###9 = octdec(11)###/cl pi()###3.1415926535898 = pi()###/cl pow(3,2)###9 = pow(3,2)###/cl rad2deg(3)###171.88733853925 = rad2deg(3)###/cl rand(50,100)###57 = rand(50,100)###/cl round(3.01234,3)###3.012 = round(3.01234,3)###/cl sin(1)###0.8414709848079 = sin(1)###/cl sqrt(9)###3 = sqrt(9)######And the middle mass of Mercury, Venus and Earth planets is:/cl (3.33022*pow(10,23)+4.8676*pow(10,24)+5.97219*pow(10,24))/3######3.7242706666667E 24 = (3.33022*pow(10,23)+4.8676*pow(10,24)+5.97219*pow(10,24))/3###By the way, maximum lenght of query is 512 symbols!";
			$AnswerText	= str_replace('###','%0a',$AnswerText);
			$AnswerText	= str_replace('/cl','%0a/cl',$AnswerText);
			sendMessage($chat,$AnswerText);
		}

		if ($message=='/functions@calcubot'||$message=='/functions')
			{
			$AnswerText	= "Supported functions:
abs(number) - Absolute value. Returns the absolute value of number.
acos(arg) - Arc cosine. Returns the arc cosine of arg in radians.
acosh(arg) - Inverse hyperbolic cosine. Returns the inverse hyperbolic cosine of arg, i.e. the value whose hyperbolic cosine is arg.
asin(arg) - Arc sine. Returns the arc sine of arg in radians.
asinh(arg) - Inverse hyperbolic sine. Returns the inverse hyperbolic sine of arg, i.e. the value whose hyperbolic sine is arg.
atan2(y Dividend,x Divisor) - Arc tangent of two variables. calculates the arc tangent of the two variables x and y. It is similar to calculating the arc tangent of y / x, except that the signs of both arguments are used to determine the quadrant of the result. Returns the result in radians, which is between -PI and PI (inclusive).
atan(arg) - Arc tangent. Returns the arc tangent of arg in radians.
atanh(arg) - Inverse hyperbolic tangent. Returns the inverse hyperbolic tangent of arg, i.e. the value whose hyperbolic tangent is arg.
base_convert(number the number to convert,frombase the base number is in,tobase the base to convert number to) - Convert a number between arbitrary bases. Returns a string containing number represented in base tobase. The base in which number is given is specified in frombase. Both frombase and tobase have to be between 2 and 36, inclusive. Digits in numbers with a base higher than 10 will be represented with the letters a-z, with a meaning 10, b meaning 11 and z meaning 35.
bindec(binary_string) - Binary to decimal. Returns the decimal equivalent of the binary number represented by the binary_string argument.
ceil(value) - Round fractions up. Returns the next highest integer value by rounding up value if necessary.
cos(arg) - Cosine. Returns the cosine of the arg parameter.
cosh(arg) - Hyperbolic cosine. Returns the hyperbolic cosine of arg, defined as (exp(arg) + exp(-arg))/2.
decbin(number) - Decimal to binary. Returns a string containing a binary representation of the given number argument.
dechex(number) - Decimal to hexadecimal. Returns a string containing a hexadecimal representation of the given unsigned number argument.
decoct(number) - Decimal to octal. Returns a string containing an octal representation of the given number argument. The largest number that can be converted is 4294967295 in decimal resulting to 37777777777.
deg2rad(number) - Converts the number in degrees to the radian equivalent. converts number from degrees to the radian equivalent.
exp(arg) - Calculates the exponent of e. Returns e raised to the power of arg. 'e' is the base of the natural system of logarithms, or approximately 2.718282.
floor(value) - Round fractions down. Returns the next lowest integer value by rounding down value if necessary.
fmod(x divident, y divisor) - Returns the floating point remainder (modulo) of the division of the arguments. Returns the floating point remainder of dividing the dividend (x) by the divisor (y). The remainder (r) is defined as: x = i * y + r, for some integer i. If y is non-zero, r has the same sign as x and a magnitude less than the magnitude of y.";
			//$AnswerText	= str_replace('###','%0a%0a',$AnswerText);
			sendMessage($chat,$AnswerText);

			$AnswerText	= "
getrandmax() - Show largest possible random value. Returns the maximum value that can be returned by a call to rand().
hexdec(hex_string) - Hexadecimal to decimal. Returns the decimal equivalent of the hexadecimal number represented by the hex_string argument. hexdec() converts a hexadecimal string to a decimal number.
hypot(x length of first side,y length of second side) - Calculate the length of the hypotenuse of a right-angle triangle. Returns the length of the hypotenuse of a right-angle triangle with sides of length x and y, or the distance of the point (x, y) from the origin. This is equivalent to sqrt(x*x + y*y).
is_finite(val) - Finds whether a value is a legal finite number. Checks whether val is a legal finite on this platform.
is_infinite(val) - Finds whether a value is infinite. Returns TRUE if val is infinite (positive or negative), like the result of log(0) or any value too big to fit into a float on this platform.
is_nan(val) - Finds whether a value is not a number. Checks whether val is 'not a number', like the result of acos(1.01).
lcg_value() - Combined linear congruential generator. lcg_value() returns a pseudo random number in the range of (0, 1). The function combines two CGs with periods of 2^31 - 85 and 2^31 - 249. The period of this function is equal to the product of both primes.
log10(arg) - Base-10 logarithm. Returns the base-10 logarithm of arg.
log(arg,base) - Natural logarithm. If the optional base parameter is specified, log() returns log_base arg, otherwise log() returns the natural logarithm of arg.
max(value_1,value_2,value_n,..) - Find highest value
min(value_1,value_2,value_n,..) - Find lowest value
mt_getrandmax() - Show largest possible random value. Returns the maximum value that can be returned by a call to rand().
mt_rand() - Generate a better random value. Produce random numbers four times faster than what the average libc rand() provides.";
			//$AnswerText	= str_replace('###','%0a%0a',$AnswerText);
			sendMessage($chat,$AnswerText);

			$AnswerText	= "
octdec(octal_string) - Octal to decimal. Returns the decimal equivalent of the octal number represented by the octal_string argument.
pi() - Get value of pi. Returns an approximation of pi.
pow(base,exp) - Exponential expression. Returns base raised to the power of exp.
rad2deg(number) - Converts the radian number to the equivalent number in degrees.
rand(min,max) - Generate a random integer. If called without the optional min, max arguments rand() returns a pseudo-random integer between 0 and getrandmax(). If you want a random number between 5 and 15 (inclusive), for example, use rand(5, 15).
round(val the value to round,precision the optional number of decimal digits to round to) - Rounds a float. Returns the rounded value of val to specified precision (number of digits after the decimal point). precision can also be negative or zero (default).
sin(arg) - Sine. returns the sine of the arg parameter. The arg parameter is in radians.
sinh(arg) - Hyperbolic sine. Returns the hyperbolic sine of arg, defined as (exp(arg) - exp(-arg))/2.
sqrt(arg) - Square root. Returns the square root of arg.
srand(seed) - Seed the random number generator. Seeds the random number generator with seed or with a random value if no seed is given.
tan(arg) - Tangent. Returns the tangent of the arg parameter. The arg parameter is in radians.
tanh(arg) - Hyperbolic tangent. Returns the hyperbolic tangent of arg, defined as sinh(arg)/cosh(arg).
ncr(n,r) - Combinations.
npr(n,r) - Permutations.
number_format(number,decimals,dec_point,thousands_sep) - Number format. For example: number_format(123456.789,2)
nf(number) - short version of number_format";
			//$AnswerText	= str_replace('###','%0a%0a',$AnswerText);
			sendMessage($chat,$AnswerText);
			}

		if ($message=='/about@calcubot'||$message=='/about')
			{
			$AnswerText	= "v 3.2 Developer Alexey Yurasov%0aformat37@gmail.com%0a@AlexMoscow";
			sendMessage($chat,$AnswerText);
			}
		$crop=0;
		if (substr($message,0,3)=='/cl') $crop=3;
		if (substr($message,0,12)=='/cl@calcubot') $crop=12;
		$source = substr($message,$crop);
		//if (!strExists("round(", $source)&&!strExists("ncr(", $source)&&!strExists("npr(", $source)&&!strExists("rand(", $source)&&!strExists("max(", $source)&&!strExists("min(", $source)&&!strExists("hypot(", $source)&&!strExists("fmod(", $source)&&!strExists("base_convert(", $source)&&!strExists("atan2(", $source)&&!strExists("pow(", $source)&&!strExists("log(", $source)) $source	= str_replace(',','.',$source);
		if ($crop||($chat==$user&&substr($message,0,1)!='/'))
			{
				$result	= myeval($source);
				if ($result===false) $result='[wrong request "'.$source.'"]';
				elseif (substr_count($source,"**")==0) $result = "$result = $source";
				sendMessage($chat,$result);
			}
}
//QUERY FROM USER
if (isset($update["inline_query"])) {
    $inlineQuery = $update["inline_query"];
    $queryId = $inlineQuery["id"];
    $source	= $inlineQuery["query"];
    $results = array();
    $badRequest	= FALSE;
	$result	= 0;
	
	if (mb_strlen($source)>512)	addInlineResult("11","query should be shortly than 512 symbols",				"wrong query");		
	else
	{
		$result	= myeval($source);
		if ($result===false) addInlineResult("10","waiting for complete query","wrong query");
		else
			{
				
				
				$resultString	= strval($result);
				addInlineResult("1",$resultString,$resultString);
				if (substr_count($source,"**")==0)
				{
					addInlineResult("2",$resultString.' = '.$source,$resultString.' = '.$source);
					addInlineResult("3",$source.' = '.$resultString,$source.' = '.$resultString);
				}
			}
	}
	apiRequestJson
       (
       "answerInlineQuery", array(
        "inline_query_id" => $queryId,
        "results" => $results,
        "cache_time" => 1,
       )
      );
}
?>
</html>
