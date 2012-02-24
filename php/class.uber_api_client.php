<?php

class uber_api_client
{
	const VERSION = '1.0';
	
	protected $options = array(
		'debug'       => false,
		'timeout'     => 30,
		'server'      => 'http://localhost/',
		'userpwd'     => '',
		'useragent'   => 'Ubersmith API Client PHP/1.0',
		'certificate' => null,
		'certpass'    => null,
	);
	
	// value of the content type response header
	protected $content_type;
	
	// value of the content length response header
	protected $content_size;
	
	// value of the filename value in the content disposition response header
	protected $content_filename;
	
	
	public function __construct($url,$username,$api_token)
	{
		$this->options['userpwd'] = $username .':'. $api_token;
		$this->options['server'] = $url;
	}
	
	
	public function set_option($option,$value = null)
	{
		return $this->options[$option] = $value;
	}
	
	public function get_option($option = null,$default = null)
	{
		if (!isset($option)) {
			return $this->options;
		}
		
		if (isset($this->options[$option])) {
			return $this->options[$option];
		}
		
		if (isset($default)) {
			return $default;
		}
		
		return null;
	}
	
	public function get_content_type()
	{
		return $this->content_type;
	}
	public function get_content_size()
	{
		return $this->content_size;
	}
	public function get_content_filename()
	{
		return $this->content_filename;
	}
	
	
	public function call($method = 'uber.method_list',$params = array())
	{
		// curl library is required
		if (!extension_loaded('curl')) {
			return $this->raiseError('cURL support is required',1);
		}
		
		$this->debug('API:',$this);
		
		$headers = array(
			'Accept-Encoding: gzip',
			'Expect:',
		);
		
		$url = rtrim($this->options['server'],'/') .'/api/2.0/?method='. urlencode($method);
		
		if (is_array($params)) {
			$params = http_build_query($params);
		}
		
		$curl = curl_init($url);
		curl_setopt($curl,CURLOPT_POST,1);
		$this->debug('URL:',$url);
		$this->debug('Params:',$params);
		curl_setopt($curl,CURLOPT_POSTFIELDS,    $params);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl,CURLOPT_USERAGENT,     $this->options['useragent']);
		curl_setopt($curl,CURLOPT_HTTPHEADER,    $headers);
		curl_setopt($curl,CURLOPT_TIMEOUT,       $this->options['timeout']);
		curl_setopt($curl,CURLOPT_HEADERFUNCTION,array($this, 'read_header'));
		
		// set auth stuff
		$userpwd = $this->options['userpwd'];
		if (!empty($userpwd)) {
			curl_setopt($curl, CURLOPT_USERPWD, $userpwd);
		}
		
		// ssl options
		if (substr($url,0,5) === 'https') {
			// set CA file
			if (file_exists(dirname(__FILE__) .'/cacert.pem')) {
				curl_setopt($curl,CURLOPT_CAINFO,dirname(__FILE__) .'/cacert.pem');
			}
			
			// ssl client certificate and password
			$certificate = $this->options['certificate'];
			if (!empty($certificate)) {
				curl_setopt($curl,CURLOPT_SSLCERT,$certificate);
				
				$certpass = $this->options['certpass'];
				if (!empty($certpass)) {
					curl_setopt($curl,CURLOPT_SSLCERTPASSWD,$certpass);
				}
			}
		}
		
		if (!$response = curl_exec($curl)) {
			$errnum = curl_errno($curl);
			$errstr = curl_error($curl);
			curl_close($curl);
			
			$this->debug('cURL Error:', $errstr);
			return $this->raiseError('cURL Error: '. $errstr,$errnum);
		}
		
		$this->content_type = curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
		$this->content_size = curl_getinfo($curl,CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		
		curl_close($curl);
		
		// decompress if response is gzip encoded
		if (strcmp(substr($response,0,2),"\x1f\x8b") == 0) {
			$len1 = strlen($response);
			$response = gzdecode($response);
			$len2 = strlen($response);
			if (!empty($len2)) {
				$this->debug('Compression:','Raw:        '.$len2."\nCompressed: ".$len1."\nSaved:      ".round(($len2-$len1)/$len2*100,2).'%');
			} else {
				$this->debug('Compression:','Raw:        '.$len2."\nCompressed: ".$len1."\nSaved:      ".'N/A');
			}
			
			$this->content_size = $len2;
		}
		
		$this->debug('Response:',$response);
		
		switch ($this->content_type) {
			case 'application/json';
				$result = json_decode($response,true);
				if (!$result) {
					return $this->raiseError('json error encountered: '. json_last_error(),-1);
				}
				
				if (empty($result['status'])) {
					return $this->raiseError($result['error_message'],$result['error_code']);
				}
				
				return $result['data'];
			case 'application/xml':
				return $response;
				break;
			case 'application/pdf':
				// an example of header() calls to use for pdfs in your controller
				//header('Content-type: application/pdf');
				//header('Content-Length: '. $uber->get_content_size());
				//header('Content-Disposition: inline; filename='. $uber->get_content_filename());
				return $response;
				break;
			case 'image/png':
				// an examples of header() calls to use for pngs in your controller
				//header('Content-type: application/pdf');
				//header('Content-Length: '. $uber->get_content_size());
				//header('Content-Disposition: inline; filename='. $uber->get_content_filename());
				return $response;
				break;
			case 'text/html':
			default:
				return $response;
		}
	}
	
	// reads all the response headers one by one from curl
	protected function read_header($ch, $header)
	{
		$pos = stripos($header,'filename=');
		if ($pos !== FALSE) {
			$this->content_filename = substr($header,$pos + 9);
		}
		
		return strlen($header);
	}
	
	// throw an Exception if an error occurs
	protected function raiseError($text,$code = 1)
	{
		// we can throw exceptions from the SPL as well
		throw new Exception($text,$code);
	}
	
	// print debug messages
	protected function debug($text,$info)
	{
		if ($this->options['debug']) {
			print $text ."\n\n";
			print_r($info);
			print "\n\n";
		}
	}
}

if (!function_exists('gzdecode')) {
	/**
	 * Function for decoding gzipped data
	 *
	 * @author Dan Cech <dan@ubersmith.com>
	 * @ignore
	 * @param string $data raw data to decode
	 * @return string decoded data
	 */
	function gzdecode($data)
	{
		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
			return null;  // Not GZIP format (See RFC 1952)
		}
		$method = ord(substr($data,2,1));  // Compression method
		$flags  = ord(substr($data,3,1));  // Flags
		if ($flags & 31 != $flags) {
			// Reserved bits are set -- NOT ALLOWED by RFC 1952
			return null;
		}
		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime = unpack("V", substr($data,4,4));
		$mtime = $mtime[1];
		$xfl  = substr($data,8,1);
		$os    = substr($data,8,1);
		$headerlen = 10;
		$extralen  = 0;
		$extra    = "";
		if ($flags & 4) {
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8) {
				return false;    // Invalid format
			}
			$extralen = unpack("v",substr($data,8,2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8) {
				return false;    // Invalid format
			}
			$extra = substr($data,10,$extralen);
			$headerlen += 2 + $extralen;
		}
		
		$filenamelen = 0;
		$filename = "";
		if ($flags & 8) {
			// C-style string file NAME data in header
			if ($len - $headerlen - 1 < 8) {
				return false;    // Invalid format
			}
			$filenamelen = strpos(substr($data,8+$extralen),chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
				return false;    // Invalid format
			}
			$filename = substr($data,$headerlen,$filenamelen);
			$headerlen += $filenamelen + 1;
		}
		
		$commentlen = 0;
		$comment = "";
		if ($flags & 16) {
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8) {
				return false;    // Invalid format
			}
			$commentlen = strpos(substr($data,8+$extralen+$filenamelen),chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
				return false;    // Invalid header format
			}
			$comment = substr($data,$headerlen,$commentlen);
			$headerlen += $commentlen + 1;
		}
		
		$headercrc = "";
		if ($flags & 1) {
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8) {
				return false;    // Invalid format
			}
			$calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
			$headercrc = unpack("v", substr($data,$headerlen,2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc) {
				return false;    // Bad header CRC
			}
			$headerlen += 2;
		}
		
		// GZIP FOOTER - These be negative due to PHP's limitations
		$datacrc = unpack("V",substr($data,-8,4));
		$datacrc = $datacrc[1];
		$isize = unpack("V",substr($data,-4));
		$isize = $isize[1];
		
		// Perform the decompression:
		$bodylen = $len-$headerlen-8;
		if ($bodylen < 1) {
			// This should never happen - IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data,$headerlen,$bodylen);
		$data = "";
		if ($bodylen > 0) {
			switch ($method) {
				case 8:
					// Currently the only supported compression method:
					$data = gzinflate($body);
					break;
				default:
					// Unknown compression method
					return false;
			}
		} else {
			// I'm not sure if zero-byte body content is allowed.
			// Allow it for now...  Do nothing...
		}
		
		// Verify decompressed size and CRC32:
		// NOTE: This may fail with large data sizes depending on how
		//      PHP's integer limitations affect strlen() since $isize
		//      may be negative for large sizes.
		
		$crc = crc32($data);
		if ($crc > $datacrc) {
			$crc -= 4294967296;
		}
		
		if ($isize != strlen($data) || $crc != $datacrc) {
			// Bad format!  Length or CRC doesn't match!
			return false;
		}
		
		return $data;
	}
}

// end of script
