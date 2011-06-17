<?php
/**
 * CakePHP 1.3.x component for easier Amazon S3 functionality
 *
 * PHP Version >= 5.2
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     (c) 2011, Rob McVey 
 * @link          http://www.robmcvey.com
 * @version		  1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

// Import the cake HttpSocket utility class
App::import('Core', 'HttpSocket');

// Import the cake File class
App::import('Core', 'File');

class AmazonS3Component extends Object {
	
	/**
	 * Amazon s3 endpoint
	 * @var string
	 */
	var $amazon_url = 's3.amazonaws.com';
	
	/**
	 * Your Amazon S3 access key
	 * @var string
	 */
	var $access_key = '';
	
	/**
	 * Your Amazon S3 secret key
	 * @var string
	 */
	var $secret_key = '';
	
	/**
	 * The current S3 bucket to perform an action on
	 * @var string
	 */
	var $bucket = 'cakecomponent';
	
	/**
	 *  This contains the body/raw data of the object being (PUT / GET)
	 * @var mixed
	 */
	var $body = null;
	
	/**
	 * This is the signature, derived from a overly complex NASA-grade hashing method  
	 * @var string
	 */
	var $signature = '';
	
	/**
	 * Hold the results array of httpsocket
	 * @var array
	 */
	var $results = null;
	
	/**
	 * The absolute path to the local DIR you want to use.
	 */
	var $local_dir = '';
	
	/**
	 * The remote path to the object inside your bucket e.g. folder/plain.txt (no bucket name, no preceeding slash)
	 * @var string
	 */
	var $remote_object = '';
	
	/**
	 * The local path inside $local_dir to your file i.e. subdir/image.jpg (no bucket name or preceeding slashes)
	 * @var string
	 */
	var $local_object = '/';
	
	/**
	 * This is the MIME type of the file
	 * @var string
	 */
	var $header_content_type = '';
	
	/**
	 * The MD5 check sum used in the header Content-MD5
	 * @var string
	 */
	var $header_check_sum = '';
	
	/**
	 * Holds a human readble error message
	 * @var string
	 */
	var $errors = '';
	
	
	/**
	 * GET a file from Amazon and save locally see http://docs.amazonwebservices.com/AmazonS3/latest/dev/
	 * @return boolean true on success 
	 */
	function get() {

		// Set the date for the header
		$date = gmdate('D, d M Y H:i:s \G\M\T');

		// Build the CanonicalizedResource Element 
		$canonicalized_resource = '/' .$this->bucket. '/'. $this->remote_object;
		
		// Build the CanonicalizedAmzHeaders Element	  
		$canonicalized_amz_headers = '';
 
 		// Build the string to sign 
    	$string_to_sign = 'GET'. "\n". // HTTP method
		''."\n" . //Content-MD5
		''."\n" . //Content-Type
		$date."\n" . // Date
		$canonicalized_amz_headers.
		$canonicalized_resource;
		
		// Create a signature 
		$this->create_signature($string_to_sign);	
		
		// Initialise the Httosocket class and build the correct headers for a GET 
		$http_socket = new HttpSocket();
		$http_socket->request['method'] = 'GET';
		$http_socket->request['uri']['host'] = $this->bucket.'.'.$this->amazon_url;
		$http_socket->request['uri']['path'] = $this->remote_object;
		$http_socket->request['body'] = $this->body;
		$http_socket->request['header']['Date'] = $date;
		$http_socket->request['header']['Authorization'] = 'AWS ' . $this->access_key.':'.$this->signature;
		$http_socket->request($http_socket->request);
		
		// Amazon returns a 200 on successful GET
		if($http_socket->response['status']['code'] == '200') {
			
			// Our file data is availble in $http_socket->response	
			$this->results = $http_socket->response;	
			
			// Save to the local path
			if($this->save_locally()) {
				return true;
			} else {
				return $this->errors;	
			}
		} else {
			$this->errors = $http_socket->response['raw']['status-line'];	
			return false;
		}
	}

	/**
	 * DELETE an object from the bucket see http://docs.amazonwebservices.com/AmazonS3/latest/dev/
	 * @return boolean true on success 
	 */
	function delete() {
		
		// Set the date for the header
		$date = gmdate('D, d M Y H:i:s \G\M\T');

		// Build the CanonicalizedResource Element 
		$canonicalized_resource = '/' .$this->bucket. '/'. $this->remote_object;
		
		// Build the CanonicalizedAmzHeaders Element	  
		$canonicalized_amz_headers = '';
 
 		// Build the string to sign 
    	$string_to_sign = 'DELETE'. "\n". // HTTP method
		''."\n" . //Content-MD5
		''."\n" . //Content-Type
		$date."\n" . // Date
		$canonicalized_amz_headers.
		$canonicalized_resource;
		
		// Create a signature 
		$this->create_signature($string_to_sign);	
		
		// Initialise the Httosocket class and build the correct headers for a DELETE 
		$http_socket = new HttpSocket();
		$http_socket->request['method'] = 'DELETE';
		$http_socket->request['uri']['host'] = $this->bucket.'.'.$this->amazon_url;
		$http_socket->request['uri']['path'] = $this->remote_object;
		$http_socket->request['body'] = $this->body;
		$http_socket->request['header']['Date'] = $date;
		$http_socket->request['header']['Authorization'] = 'AWS ' . $this->access_key.':'.$this->signature;
		$http_socket->request($http_socket->request);
		
		// Amazon returns a 204 on successful DELETE
		if($http_socket->response['status']['code'] == '204') {
			
			// Our file data is availble in $http_socket->response
			$this->results = $http_socket->response;	
			return true;
		} else {
			$this->errors = $http_socket->response['raw']['status-line'];	
			return false;
		}
	}
	
	
	/**
	 * PUT an object in a remote bucket see http://docs.amazonwebservices.com/AmazonS3/latest/API/index.html?RESTObjectPUTacl.html
	 * @param string $acl should the file Valid Values: private | public-read | public-read-write etc. 
	 * @return boolean true on success 
	 */
	function put($acl = 'private') {
		
		// Get the MIME type of the local object
		if(!$this->get_local_content_type()) {
			return false;
		}
		
		// Create a check sum MD5 for the header
		if(!$this->create_check_sum()) {
			return false;	
		}
		
		// Create a date for the heafer
		$date = gmdate('D, d M Y H:i:s \G\M\T');
		
		// Build the CanonicalizedResource Element 
		$canonicalized_resource = '/' .$this->bucket. '/'. $this->local_object;
			  
		// Build the CanonicalizedAmzHeaders Element - We'll set this to private	  
		$canonicalized_amz_headers = 'x-amz-acl:'.$acl;
 
 		// Build the string to sign 
    	$string_to_sign = 'PUT'. "\n". // HTTP method
		$this->header_check_sum."\n" . //Content-MD5
		$this->header_content_type."\n" . //Content-Type
		$date."\n" . // Date
		$canonicalized_amz_headers."\n".
		$canonicalized_resource;
		
		// Create a signature 
		$this->create_signature($string_to_sign);
		
		// Add the local file data to the body of the header..
		$this->body = $this->open_local_file();	
		
		// Initialise the Httosocket class and build the correct headers for a DELETE
		$http_socket = new HttpSocket();
		$http_socket->request['method'] = 'PUT';
		$http_socket->request['uri']['host'] = $this->bucket.'.'.$this->amazon_url;
		$http_socket->request['uri']['path'] = $this->local_object;
		$http_socket->request['body'] = $this->body;
		$http_socket->request['header']['Date'] = $date;
		$http_socket->request['header']['Content-Type'] = $this->header_content_type;
		$http_socket->request['header']['Content-MD5'] = $this->header_check_sum;
		$http_socket->request['header']['x-amz-acl'] = 'private';
		$http_socket->request['header']['Authorization'] = 'AWS ' . $this->access_key.':'.$this->signature;
		$http_socket->request($http_socket->request);

		// Amazon returns a 200 on successful PUT
		if($http_socket->response['status']['code'] == '200') {
			
			// Our file data is availble in $http_socket->response
			$this->results = $http_socket->response;
			return true;
		} else {
			$this->errors = $http_socket->response['raw']['status-line'];	
			return false;
		}
	}

	/**
	 * Creates the NASA style signature
	 * @param string the header string to sign
	 * @return void
	 */
	function create_signature($string_to_sign) {
		$this->signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->secret_key, true)); 
	}	

	/**
	 * Saves the body of the reponse to the local dir
	 * @return boolean
	 */
	function save_locally() {
		// Intialise the File class
		$file = new File($this->local_dir.DS.$this->remote_object,true);
		if($file->write($this->results['raw']['body'])) {
			return true;
		} else {		
			$this->errors = sprintf(__('Could not create %s in the following location: %s',true),
				$this->remote_object, $this->local_dir);
			return false;
		}
	} 
	
	/**
	 * Opens a local file ready to send in the body of our request
	 * @return mixed raw file contents 
	 */
	function open_local_file() {
		$file = new File($this->local_dir.DS.$this->local_object);
		return $file->read();
	}	
	
	/**
	 * Gets the MIME type of the local file we are PUT'ting
	 * @return boolean true if succefully detect MIME type
	 */
	function get_local_content_type() {
		$arg = escapeshellarg($this->local_dir.DS.$this->local_object);
        $this->header_content_type = exec('file -b --mime-type ' . $arg, $foo, $returnCode); // Better way of doing this? <--
		if(preg_match('#^[-\w]+/[-\w]+$#', $this->header_content_type)) {
		   return true;
		} else {
			$this->errors = sprintf(__('Could not detect content type of %s in the following location: %s',true),
				$this->local_object, $this->local_dir);
			return false;
		} 
	}
	
	/**
	 * Calculates the MD5 check sum of the file we are sending
	 * @return boolean true we have at least something
	 */
	function create_check_sum() {
		$this->header_check_sum = base64_encode(md5_file($this->local_dir.DS.$this->local_object , true));
		if(!empty($this->header_check_sum)){
			return true;
		} else {
			$this->errors = sprintf(__('Could not create MD5 check sum of %s in the following location: %s',true),
				$this->local_object, $this->local_dir);
			return false;
		}
	}
	
		
}
	