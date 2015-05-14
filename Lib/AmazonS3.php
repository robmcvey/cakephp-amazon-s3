<?php
/**
 * AmazonS3.php
 * Created by Rob Mcvey on 2013-09-22.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Rob Mcvey on 2013-09-22..
 * @link          www.copify.com
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('HttpSocket', 'Network/Http');
App::uses('File', 'Utility');
App::uses('Xml', 'Utility');

class AmazonS3Exception extends CakeException {}

class AmazonS3 {
		
/**
 * Your Amazon S3 access key
 * @var string
 */
	public $accessKey = '';
	
/**
 * Your Amazon S3 secret key
 * @var string
 */
	public $secretKey = '';
		
/**
 * The current S3 bucket to perform an action on
 * @var string
 */
	public $bucket = 'mybucket';
	
/**
 * Amazon S3 endpoint
 * @var string
 */
	public $endpoint = 's3.amazonaws.com';	

/**
 * Absolute path to ur local file
 * @var string
 */	
	public $localPath = '';

/**
 * Array of information about our local file
 * @var array
 */			
	public $info = array();

/**
 * Header Content-Type
 * @var string
 */
	public $contentType = '';

/**
 * MD5 checksum of our local file
 * @var string
 */	
	public $contentMd5 = '';

/**
 * Additional amazon specific headers e.g. x-amz-acl:public-read
 * @var string
 */	
	public $canonicalizedAmzHeaders = '';

/**
 * Array of additional amazon headers to pass
 * @var array
 */	
	public $amazonHeaders = array();

/**
 * Current date in format Tue, 27 Mar 2007 21:15:45 +0000
 * @var array
 */	
	public $date = null;

/**
 * HttpSocket class
 * @var HttpSocket
 */	
	public $HttpSocket = null;

/**
 * File class
 * @var File
 */	
	public $File;

/**
 * Constructor
 *
 * @param array Config array in format array({accessKey} , {secretKey}, {bucket})
 * @return void
 * @author Rob Mcvey
 **/
	public function __construct($config) {
		list($this->accessKey, $this->secretKey, $this->bucket) = $config;
		// Set current date 
		$this->setDate();
	}
	
/**
 * Put a local file in an S3 bucket
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function put($localPath , $remoteDir = null) {

		// Base filename
		$file = basename($localPath);

		// File remote/local files
		$this->checkLocalPath($localPath);
		$this->checkFile($file);
		$this->checkRemoteDir($remoteDir);
		
		// Signature
		$stringToSign = $this->stringToSign('put');

		// Build the HTTP request
		$request = array(
			'method' => 'PUT',
			'uri' => array(
				'scheme' => 'https',
				'host' => $this->bucket . '.' . $this->endpoint,
				'path' => $this->file,
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => $this->date,
				'Authorization' => 'AWS ' . $this->accessKey . ':' . $this->signature($stringToSign),
				'Content-MD5' => $this->contentMd5,
				'Content-Type' => $this->contentType,
				'Content-Length' => $this->File->size()
			),
			'body' => $this->File->read()
		);
		
		// Any addional Amazon headers to add?
		$request = $this->addAmazonHeadersToRequest($request);

		// Make the HTTP request
		$response = $this->handleRequest($request);

		// Handle response errors if any
		$this->handleResponse($response);
	}	

/**
 * Fetch a remote file from S3 and save locally
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function get($file , $localPath) {
		// File remote/local files
		$this->checkFile($file);
		$this->checkLocalPath($localPath);
		
		// Signature
		$stringToSign = $this->stringToSign('get');

		$request = array(
			'method' => 'GET',
			'uri' => array(
				'scheme' => 'https',
				'host' => $this->bucket . '.' . $this->endpoint,
				'path' => $this->file,
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => $this->date,
				'Authorization' => 'AWS ' . $this->accessKey . ':' . $this->signature($stringToSign)
			)
		);

		// Make the HTTP request
		$response = $this->handleRequest($request);
		
		// Handle response errors if any
		$this->handleResponse($response);

		// Write file locally
		$this->File = new File($this->localPath . DS . $this->file, true);	
		$this->File->write($response->body);
	}
	
/**
 * Delete a remote file from S3
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function delete($file) {
		// File remote/local files
		$this->checkFile($file);

		// Signature
		$stringToSign = $this->stringToSign('delete');

		$request = array(
			'method' => 'DELETE',
			'uri' => array(
				'scheme' => 'https',
				'host' => $this->bucket . '.' . $this->endpoint,
				'path' => $this->file,
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => $this->date,
				'Authorization' => 'AWS ' . $this->accessKey . ':' . $this->signature($stringToSign)
			)
		);
		
		// Make the HTTP request
		$response = $this->handleRequest($request);

		// Handle response errors if any
		$this->handleResponse($response);
	}	
	
/**
 * Sets the date we're working with. Used in both HTTP request and signature.
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function setDate($date = null) {
		if (!$date) {
			$this->date = gmdate('D, d M Y H:i:s \G\M\T');
		} else {
			$this->date = $date;
		}
	}

/**
 * Returns the public URL of a file
 *
 * @return string
 * @author Rob Mcvey
 **/
	public function publicUrl($file, $ssl = false) {
		$scheme = 'http';
		if ($ssl) {
			$scheme .= 's';
		}
		// Replace any preceeding slashes
		$file = preg_replace("/^\//" , '', $file);
		return sprintf('%s://%s.s3.amazonaws.com/%s' , $scheme , $this->bucket, $file);
	}	

/**
 * Makes the HTTP Rest request
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function handleRequest($request) {
		// HttpSocket. 
		if (!$this->HttpSocket) {
			$this->HttpSocket = new HttpSocket();
			$this->HttpSocket->quirksMode = true; // Amazon returns sucky XML
		}
		
		// Make request
		try {
			return $this->HttpSocket->request($request);
		} catch (SocketException $e) {
			// If error Amazon returns garbage XML and 
			// throws HttpSocket::_decodeChunkedBody - Could not parse malformed chunk ???
			throw new AmazonS3Exception(__('Could not complete the HTTP request'));
		}
	}
	
/**
 * Handles the HttpSocket response object and checks for any errors
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function handleResponse($response) {
		if (!property_exists($response , 'code')) {
			throw new InvalidArgumentException(__('The response from Amazon S3 is invalid'));
		}
		// All good
		if (in_array($response->code, array(200, 204))) {
			return $response->code;
		} else {
			$headers = $response->headers;
			$genericMessage = __('There was an error communicating with AWS');
			if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/xml') {
				$xml = $response->body;
				$Xml = Xml::build($xml);
				if (property_exists($Xml, 'Message')) {
					throw new AmazonS3Exception($Xml->Message);
				} else {
					throw new AmazonS3Exception($genericMessage);
				}
			} else {
				throw new AmazonS3Exception($genericMessage);
			}
		}
	}

/**
 * Check we have a file string
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function checkFile($file) {
		$this->file = $file;
		// Set the target and local path to where we're saving
		if (empty($this->file)) {
			throw new InvalidArgumentException(__('You must specify the file you are fetching (e.g remote_dir/file.txt)'));
		}
	}
	
/**
 * Check our local path
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function checkLocalPath($localPath) {
		$this->localPath = $localPath;
		if (empty($this->localPath)) {
			throw new InvalidArgumentException(__('You must set a localPath (i.e where to save the file)'));
		}
		if (!file_exists($this->localPath)) {
			throw new InvalidArgumentException(__('The localPath you set does not exist'));
		}
	}
	
/**
 * Removes preceeding/trailing slashes from a remote dir target and builds $this->file again
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function checkRemoteDir($remoteDir) {
		// Is the target a directory? Remove preceending and trailing /				
		if ($remoteDir) {
			$remoteDir = preg_replace(array("/^\//", "/\/$/") , "", $remoteDir);
			$this->file = $remoteDir . '/' . $this->file;
		}
	}

/**
 * Creates the Authorization header string to sign
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function stringToSign($method = 'get') {
		
		// PUT object, need hash and content type
		if (strtoupper($method) == 'PUT') {
			$this->getLocalFileInfo();
			$this->contentType = $this->info['mime'];
			$this->contentMd5 = $this->getContentMd5();
		}

		// Add any additional Amazon specific headers if present
		$this->buildAmazonHeaders();
		
		// stringToSign
		$toSign = strtoupper($method) . "\n";
		$toSign .= $this->contentMd5 . "\n";
		$toSign .= $this->contentType . "\n";
		$toSign .= $this->date . "\n";
		$toSign .= $this->canonicalizedAmzHeaders;
		$toSign .= '/' . $this->bucket . '/' . $this->file;
		return $toSign;
	}
	
/**
 * Takes the request array pre-put and adds any additional amazon headers
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function addAmazonHeadersToRequest($request) {
		if (!empty($this->amazonHeaders) && is_array($this->amazonHeaders)) {
			foreach ($this->amazonHeaders as $k => $header) {
				$request['header'][$k] =$header; 
			}
		}
		return $request;
	}	
	
/**
 * Add any additional Amazon specific headers if present
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function buildAmazonHeaders() {
		if (!empty($this->amazonHeaders) && is_array($this->amazonHeaders)) {
		    $this->canonicalizedAmzHeaders = '';
		    $this->sortLexicographically();
			foreach ($this->amazonHeaders as $k => $header) {
				$this->canonicalizedAmzHeaders .= strtolower($k) . ":" . $header . "\n"; 
			}
		}
	}
	
/**
 * Sort the collection of headers lexicographically by header name.
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function sortLexicographically() {
        ksort($this->amazonHeaders, SORT_FLAG_CASE | SORT_NATURAL);
	}
	
/**
 * Create signature for the Authorization header.
 * Format: Base64( HMAC-SHA1( YourSecretAccessKeyID, UTF-8-Encoding-Of( StringToSign ) ) );
 * @param string the header string to sign
 * @return string base64_encode encoded string
 * @link http://docs.aws.amazon.com/AmazonS3/latest/dev/RESTAuthentication.html
 */
	public function signature($stringToSign) {
		return base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true)); 
	}	

/**
 * Get local file info (uses CakePHP Utility/File class)
 *
 * @return array
 * @author Rob Mcvey
 **/
	public function getLocalFileInfo() {
		$this->File = new File($this->localPath);
		$this->info = $this->File->info();
		return $this->info;
	}

/**
 * Return base64 encoded file checksum
 *
 * @return string
 * @author Rob Mcvey
 **/
	public function getContentMd5() {
		return base64_encode(md5_file($this->localPath , true));
	}	
	
}
