<?php
define('BOT_TOKEN', 'CHANGE_THIS');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

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
        $opts[CURLOPT_CAINFO] = "cacert.pem";
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

function collect_file($fileurl){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $fileurl);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.xcontest.org");
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$result = curl_exec($ch);
		curl_close($ch);
		return($result);
}

function write_to_file($text,$new_filename){
		$fp = fopen($new_filename, 'w');
		fwrite($fp, $text);
		fclose($fp);
}

function sendPhoto($fileurl,$c,$chat_id)
{
		$method = 'sendPhoto';
		$filename = "images/".uniqid().".png";
		$temp_file_contents = collect_file($fileurl);
		write_to_file($temp_file_contents,$filename);

		if(class_exists('CURLFile')) $cfile = new CURLFile($filename);
		else $cfile = "@".$filename;
		$params = array
		(
			'chat_id' => $chat_id,
			'photo' => $cfile,
			'reply_to_message_id' => null,
			'reply_markup' => null
		);
		$r = $c->request(API_URL.$method, 'POST', $params);
		$j = json_decode($r, true);
		if($j) print_r($j);
		else echo $r;
		unlink($filename);
}

function sendLocalPhoto($filename,$c,$chat_id)
{
		$method = 'sendPhoto';
		/*$filename = "images/".uniqid().".png";
		$temp_file_contents = collect_file($fileurl);
		write_to_file($temp_file_contents,$filename);*/

		if(class_exists('CURLFile')) $cfile = new CURLFile($filename);
		else $cfile = "@".$filename;
		$params = array
		(
			'chat_id' => $chat_id,
			'photo' => $cfile,
			'reply_to_message_id' => null,
			'reply_markup' => null
		);
		$r = $c->request(API_URL.$method, 'POST', $params);
		$j = json_decode($r, true);
		if($j) print_r($j);
		else echo $r;
		//unlink($filename);
}

//VkParser
function parseUrl($url,$usePrefix=true)
{
	if ($usePrefix)
	{
			$pos = strpos($url, 'vk.com/');
			if ($pos===false) return false;
	}
	$pos = strpos($url, 'wall');
	if ($pos===false) return false;
	else return	substr($url,$pos+4-strlen($url));
}
//HELLOBOT
function apiRequestWebhook($method, $parameters) {
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

  header("Content-Type: application/json");
  echo json_encode($parameters);
  return true;
}

function exec_curl_request($handle) {
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($handle, CURLOPT_CAINFO, "cacert.pem");
  $response = curl_exec($handle);
  //$content = curl_exec($this->curl_obj);
        //if ($content===false) echo 'Ошибка curl: ' . curl_error($this->curl_obj);
  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    //file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id=106129214&text='.curl_error($this->curl_obj));
    echo 'Ошибка curl: ' . curl_error($this->curl_obj);
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

function apiRequest($method, $parameters) {
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

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  return exec_curl_request($handle);
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

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}

function processMessage($message) {
  // process incoming message
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  if (isset($message['text'])) {
    // incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
      apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello', 'reply_markup' => array(
        'keyboard' => array(array('Hello', 'Hi')),
        'one_time_keyboard' => true,
        'resize_keyboard' => true)));
    } else if ($text === "Hello" || $text === "Hi") {
      apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Nice to meet you'));
    } else if (strpos($text, "/stop") === 0) {
      // stop now
    } else {
      apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'Cool'));
    }
  } else {
    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
  }
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
function prepareStringForReturn($value)
{
	$value	= str_replace('+','%2B',$value);
	$value	= str_replace(' ','%20',$value);
	return $value;
}

function strExists($value, $string)
{
    foreach ((array) $value as $v) {
        if (false !== strpos($string, $v)) return true;
    }
}

//START
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) exit;
//PERSONAL OR GROUP MESSAGE
if (isset($update["message"]))
{
		//processMessage($update["message"]);
		$chat	= $update["message"]['chat']['id'];
		$user	= $update["message"]['from']['id'];
		//$c = new curl();
		$message	= $update["message"]['text'];

		$message = strtolower($message);

if (substr($message, 0, 3)=="/--")
{

	$currentTime	= time()-3600;
	$pos = strpos($message, "+");
	$yy		= ($pos<17)?date('y',$currentTime):substr($message, 15, 2);
	$mmmm	= ($pos<14)?date('m',$currentTime):substr($message, 12, 2);
	$dd		= ($pos<11)?date('d',$currentTime):substr($message, 9, 2);
	$hh		= ($pos<8)?date('H',$currentTime):substr($message, 6, 2);
	$mm		= ($pos<5)?date('i',$currentTime):substr($message, 3, 2);

	$utcUser= mktime(0+$hh, 0+$mm, 0, 0+$mmmm, 0+$dd, 2000+$yy);
	$utc	= 3600+$utcUser;
	$AnswerText	= "/alert ".$utc.' '.substr($message, $pos+1);
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text='.$AnswerText);

}

if ($message=='/help@calcubot'||$message=='/help'||$message=='/start')
	{
	$AnswerText	= "Hi! im console calculator based on PHP and his eval function";
	$AnswerText	= str_replace('###','%0a',$AnswerText);
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text=Hi! im console calculator based on PHP and his eval function');
	}

if ($message=='/example@calcubot'||$message=='/example')
	{
	$AnswerText	= "/cl 2+2###4 = 2+2###/cl abs(-3)###3 = abs(-3)###/cl sin(4)###-0.75680249530793 = sin(4)###/cl atan2(3,4)###0.64350110879328 = atan2(3,4)###/cl base_convert(34,10,2)###100010 = base_convert(34,10,2)###/cl bindec(100101)###37 = bindec(100101)###/cl ceil(2.3)###3 = ceil(2.3)###/cl decbin(48)###110000 = decbin(48)###/cl dechex(250)###fa = dechex(250)###/cl decoct(9)###11 = decoct(9)###/cl deg2rad(3)###0.05235987755983 = deg2rad(3)###/cl exp(3)###20.085536923188 = exp(3)###/cl floor(2.7)###2 = floor(2.7)###/cl fmod(2,7)###2 = fmod(2,7)###/cl hexdec(fa)###250 = hexdec(fa)###/cl hypot(4,7)###8.0622577482985 = hypot(4,7)###/cl is_finite(1/0)###1 = is_finite(1/0)###/cl is_infinite(log(0))###1 = is_infinite(log(0))###/cl is_nan(acos(1.01))###1 = is_nan(acos(1.01))###/cl lcg_value()###0.38509916446214 = lcg_value()###/cl log10(7)###0.84509804001426 = log10(7)###/cl log(7,10)###0.84509804001426 = log(7,10)###/cl max(3,4,8,2,19,7)###19 = max(3,4,8,2,19,7)###/cl min(8,2,-9,17)###-9 = min(8,2,-9,17)###/cl mt_getrandmax()###2147483647 = mt_getrandmax()###/cl mt_rand()###1148623558 = mt_rand()###/cl octdec(11)###9 = octdec(11)###/cl pi()###3.1415926535898 = pi()###/cl pow(3,2)###9 = pow(3,2)###/cl rad2deg(3)###171.88733853925 = rad2deg(3)###/cl rand(50,100)###57 = rand(50,100)###/cl round(3.01234,3)###3.012 = round(3.01234,3)###/cl sin(1)###0.8414709848079 = sin(1)###/cl sqrt(9)###3 = sqrt(9)######And the middle mass of Mercury, Venus and Earth planets is:/cl (3.33022*pow(10,23)+4.8676*pow(10,24)+5.97219*pow(10,24))/3######3.7242706666667E 24 = (3.33022*pow(10,23)+4.8676*pow(10,24)+5.97219*pow(10,24))/3###By the way, maximum lenght of query is 255 symbols!";
	$AnswerText	= str_replace('###','%0a',$AnswerText);
	$AnswerText	= str_replace('/cl','%0a/cl',$AnswerText);
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));
}

if ($message=='/functions@calcubot'||$message=='/functions')
	{
	$AnswerText	= "Supported functions:###abs(number) вЂ” Absolute value. Returns the absolute value of number.###acos(arg) вЂ” Arc cosine. Returns the arc cosine of arg in radians.###acosh(arg) вЂ” Inverse hyperbolic cosine. Returns the inverse hyperbolic cosine of arg, i.e. the value whose hyperbolic cosine is arg.###asin(arg) вЂ” Arc sine. Returns the arc sine of arg in radians.###asinh(arg) вЂ” Inverse hyperbolic sine. Returns the inverse hyperbolic sine of arg, i.e. the value whose hyperbolic sine is arg.###atan2(y Dividend,x Divisor) вЂ” Arc tangent of two variables. calculates the arc tangent of the two variables x and y. It is similar to calculating the arc tangent of y / x, except that the signs of both arguments are used to determine the quadrant of the result. Returns the result in radians, which is between -PI and PI (inclusive).###atan(arg) вЂ” Arc tangent. Returns the arc tangent of arg in radians.###atanh(arg) вЂ” Inverse hyperbolic tangent. Returns the inverse hyperbolic tangent of arg, i.e. the value whose hyperbolic tangent is arg.###base_convert(number the number to convert,frombase the base number is in,tobase the base to convert number to) вЂ” Convert a number between arbitrary bases. Returns a string containing number represented in base tobase. The base in which number is given is specified in frombase. Both frombase and tobase have to be between 2 and 36, inclusive. Digits in numbers with a base higher than 10 will be represented with the letters a-z, with a meaning 10, b meaning 11 and z meaning 35.###bindec(binary_string) вЂ” Binary to decimal. Returns the decimal equivalent of the binary number represented by the binary_string argument.###ceil(value) вЂ” Round fractions up. Returns the next highest integer value by rounding up value if necessary.###cos(arg) вЂ” Cosine. Returns the cosine of the arg parameter.###cosh(arg) вЂ” Hyperbolic cosine. Returns the hyperbolic cosine of arg, defined as (exp(arg) + exp(-arg))/2.###decbin(number) вЂ” Decimal to binary. Returns a string containing a binary representation of the given number argument.###dechex(number) вЂ” Decimal to hexadecimal. Returns a string containing a hexadecimal representation of the given unsigned number argument.###decoct(number) вЂ” Decimal to octal. Returns a string containing an octal representation of the given number argument. The largest number that can be converted is 4294967295 in decimal resulting to 37777777777.###deg2rad(number) вЂ” Converts the number in degrees to the radian equivalent. converts number from degrees to the radian equivalent.###exp(arg) вЂ” Calculates the exponent of e. Returns e raised to the power of arg. 'e' is the base of the natural system of logarithms, or approximately 2.718282.###floor(value) вЂ” Round fractions down. Returns the next lowest integer value by rounding down value if necessary.###fmod(x divident, y divisor) вЂ” Returns the floating point remainder (modulo) of the division of the arguments. Returns the floating point remainder of dividing the dividend (x) by the divisor (y). The remainder (r) is defined as: x = i * y + r, for some integer i. If y is non-zero, r has the same sign as x and a magnitude less than the magnitude of y.";
	$AnswerText	= str_replace('###','%0a%0a',$AnswerText);
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));

	$AnswerText	= "###getrandmax() вЂ” Show largest possible random value. Returns the maximum value that can be returned by a call to rand().###hexdec(hex_string) вЂ” Hexadecimal to decimal. Returns the decimal equivalent of the hexadecimal number represented by the hex_string argument. hexdec() converts a hexadecimal string to a decimal number.###hypot(x length of first side,y length of second side) вЂ” Calculate the length of the hypotenuse of a right-angle triangle. Returns the length of the hypotenuse of a right-angle triangle with sides of length x and y, or the distance of the point (x, y) from the origin. This is equivalent to sqrt(x*x + y*y).###is_finite(val) вЂ” Finds whether a value is a legal finite number. Checks whether val is a legal finite on this platform.###is_infinite(val) вЂ” Finds whether a value is infinite. Returns TRUE if val is infinite (positive or negative), like the result of log(0) or any value too big to fit into a float on this platform.###is_nan(val) вЂ” Finds whether a value is not a number. Checks whether val is 'not a number', like the result of acos(1.01).###lcg_value() вЂ” Combined linear congruential generator. lcg_value() returns a pseudo random number in the range of (0, 1). The function combines two CGs with periods of 2^31 - 85 and 2^31 - 249. The period of this function is equal to the product of both primes.###log10(arg) вЂ” Base-10 logarithm. Returns the base-10 logarithm of arg.###log(arg,base) вЂ” Natural logarithm. If the optional base parameter is specified, log() returns log_base arg, otherwise log() returns the natural logarithm of arg.###max(value_1,value_2,value_n,..) вЂ” Find highest value###min(value_1,value_2,value_n,..) вЂ” Find lowest value###mt_getrandmax() вЂ” Show largest possible random value. Returns the maximum value that can be returned by a call to rand().###mt_rand() вЂ” Generate a better random value. Produce random numbers four times faster than what the average libc rand() provides.";
	$AnswerText	= str_replace('###','%0a%0a',$AnswerText);
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));

	$AnswerText	= "###octdec(octal_string) вЂ” Octal to decimal. Returns the decimal equivalent of the octal number represented by the octal_string argument.###pi() вЂ” Get value of pi. Returns an approximation of pi.###pow(base,exp) вЂ” Exponential expression. Returns base raised to the power of exp.###rad2deg(number) вЂ” Converts the radian number to the equivalent number in degrees.###rand(min,max) вЂ” Generate a random integer. If called without the optional min, max arguments rand() returns a pseudo-random integer between 0 and getrandmax(). If you want a random number between 5 and 15 (inclusive), for example, use rand(5, 15).###round(val the value to round,precision the optional number of decimal digits to round to) вЂ” Rounds a float. Returns the rounded value of val to specified precision (number of digits after the decimal point). precision can also be negative or zero (default).###sin(arg) вЂ” Sine. returns the sine of the arg parameter. The arg parameter is in radians.###sinh(arg) вЂ” Hyperbolic sine. Returns the hyperbolic sine of arg, defined as (exp(arg) - exp(-arg))/2.###sqrt(arg) вЂ” Square root. Returns the square root of arg.###srand(seed) вЂ” Seed the random number generator. Seeds the random number generator with seed or with a random value if no seed is given.###tan(arg) вЂ” Tangent. Returns the tangent of the arg parameter. The arg parameter is in radians.###tanh(arg) вЂ” Hyperbolic tangent. Returns the hyperbolic tangent of arg, defined as sinh(arg)/cosh(arg).###also u can use it in pair with @AlertBot (copypaste or forward) like that:###/--59+my test 01###or like that###/--48.14.27.11.15+my test 02###or like that###/--48.14.28+my test 03";
	$AnswerText	= str_replace('###','%0a%0a',$AnswerText);
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));
	}

if ($message=='/about@calcubot'||$message=='/about')
	{
	$AnswerText	= "v 2.0 Developer Alexey Yurasov%0aformat37@gmail.com%0a@AlexMoscow";
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));
	}

$crop=0;
if (substr($message,0,3)=='/cl') $crop=3;
if (substr($message,0,12)=='/cl@calcubot') $crop=12;
$source = substr($message,$crop);
//file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text=source:'.$crop);
$source	= str_replace(' ','',$source);
if (!strExists("round(", $source)&&!strExists("rand(", $source)&&!strExists("max(", $source)&&!strExists("min(", $source)&&!strExists("hypot(", $source)&&!strExists("fmod(", $source)&&!strExists("base_convert(", $source)&&!strExists("atan2(", $source)&&!strExists("pow(", $source)&&!strExists("log(", $source)) $source	= str_replace(',','.',$source);
$badRequest	= mb_strlen($source)>255;
if (strExists("^", $source))
{
	$badRequest	= true;
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.prepareStringForReturn('&text=^ is wrong symbol. Use pow(a,b)'));
}

if (strExists("sleep", $source))
{
	$badRequest	= true;
	file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text=please%20dont%20hack%20me.%20i%20just%20try%20to%20make%20world%20better)');
}
if (!$badRequest&&($crop||($chat==$user&&substr($message,0,1)!='/')))
	{
	if (!strExists("$", $source)&&!strExists("while", $source))
		{
		//saveToLog($source,$user);
		$result	= 0;
		if (eval('$result = '.$source.';')===false) $badRequest=true;
		else
			{
			$source = ' = '.$source;
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text='.$result.prepareStringForReturn($source));
			}
		}

else
		{
		$badRequest	= true;
		}

	}
if ($badRequest) file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat.'&text=Bad%20request:%20'.prepareStringForReturn($source).'%0atype%20/help@CalcuBot');

	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/*if (substr($text,0,14)=='/rp@vkReposter'||substr($text,0,3)=='/rp')
		{
			$postId	= parseUrl($text);
			if ($postId===false) file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=bad request');
			else //receive images from url
			{
				$json = file_get_contents('https://api.vk.com/method/wall.getById?posts='.$postId);
				$action = json_decode($json, true);
				$topic	= str_replace("<br>","%0A",$action['response'][0]['text']);
				file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$topic);
				$attachments	= $action['response'][0]['attachments'];
				for ($i=0;$i<count($attachments);$i++) {
					sendPhoto($attachments[$i]['photo']['src_big'],$c,$chat_id);
				}
			}
		}
		if ($text=='/example@vkReposter'||$text=="/example")
		{
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=The response sent in personal chat');
			$chat_id	= $update["message"]['from']['id'];
			sendLocalPhoto("images/vkReposterExample0.png",$c,$chat_id);
			sendLocalPhoto("images/vkReposterExample1.png",$c,$chat_id);
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=Then, @vkReposter sends the post title and a series of his images');
			sendLocalPhoto("images/vkReposterExample2.png",$c,$chat_id);
		}

		if ($text=='/help@vkReposterBot'||$text=='/help')
		{
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=The response sent in personal chat');
			$chat_id	= $update["message"]['from']['id'];
			$AnswerText	= "Привет! Моя работа - пересылать изображения записи стены вконтакте.%0a1. Скопируйте ссылку на запись вконтакте%0a2. Впишите в поле ввода Telegram:%0a/rp %0a3. Вставьте скопированную ранее ссылку%0aДолжно получиться что то вроде этого:%0a/rp https://vk.com/stolbn?w=wall-35294456_2916950%0a4. Отправьте получившееся сообщение.%0aБот ответит вам серией картинок из данной записи.";
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$AnswerText);
		}*/


		/*if (substr($text,0,17)=='/group@vkReposter'||$text=="/group") file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$chat_id);
		$vkpos	= strpos($text, 'vk.com/');
		$spacepos	= strpos($text, ' ');
		file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.strpos($url, 'vk.com/'));//.$vkpos.";".$spacepos.";".substr($test,$spacepos));
		//message from personal chat, for redirect to grou[
		if ($vkpos!=FALSE&&$spacepos!=FALSE&&$vkpos>$spacepos)
		{
			$chat_id	= substr($text,$spacepos);
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$text);
		}*/

		//else file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=cmd not found');
}
//QUERY FROM USER
if (isset($update["inline_query"])) {
    $inlineQuery = $update["inline_query"];
    $queryId = $inlineQuery["id"];
    $source	= $inlineQuery["query"];
    $source	= strtolower($source);
    $results = array();
    $badRequest	= FALSE;
		$result	= 0;
		if (mb_strlen($source)>255) 							{addInlineResult("11","query should be shortly than 255 symbols",				"wrong query");$badRequest	= TRUE;}
		if (strpos($source,"^")!==false)						{addInlineResult("12","^ is wrong symbol. Use pow(a,b)",						"wrong query");$badRequest	= TRUE;}
		if (strpos($source,"sleep")!==false||strpos($source,"while")!==false||strpos($source,"$")!==false)	{addInlineResult("13","please dont hack me. i just try to make world better",	"wrong query");$badRequest	= TRUE;}
		if ($badRequest||eval('$result = '.$source.';')===false) addInlineResult("10","waiting for complete query","wrong query");
		else
			{
			$resultString	= strval($result);
			addInlineResult("1",$resultString,$resultString);
			addInlineResult("2",$resultString.' = '.$source,$resultString.' = '.$source);
			addInlineResult("3",$source.' = '.$resultString,$source.' = '.$resultString);
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
//CHOSEN INLINE RESULT
/*if (isset($update["chosen_inline_result"])) {
	//now disabled, because chat_id still uavialable in chosen_inline_result
	$chosen_inline_result=$update["chosen_inline_result"];
	//file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id."&text=chat: ".$chosen_inline_result["result_id"]);
	//file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id."&text=link: ".$chosen_inline_result["query"]);
}*/
?>
