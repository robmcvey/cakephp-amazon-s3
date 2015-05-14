<?php
/**
 * AmazonS3Test.php
 * Created by Rob Mcvey on 2013-09-22.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Rob Mcvey on 2013-09-22.
 * @link          www.copify.com
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('AmazonS3', 'AmazonS3.Lib');

/**
 * PaypalTest class
 */
class AmazonS3TestCase extends CakeTestCase {

/**
 * AmazonS3 class
 * @var AmazonS3
 */	
	public $AmazonS3;
	
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->AmazonS3 = new AmazonS3(array('foo' , 'bar', 'bucket'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->AmazonS3);
	}
	
/**
 * testConstructor
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testConstructor() {
		$this->assertEqual('foo' , $this->AmazonS3->accessKey);
		$this->assertEqual('bar' , $this->AmazonS3->secretKey);
		$this->assertEqual('bucket' , $this->AmazonS3->bucket);
		$this->assertEqual(date('Y') , date('Y' , strtotime($this->AmazonS3->date)));
	}	
	
/**
 * testHandleResponse
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testHandleResponse() {
		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '????JFIFdd??Duckya??Adobed??????????';
		$HttpSocketResponse->code = 200;
		$HttpSocketResponse->reasonPhrase = 'OK';
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Last-Modified' => 'Tue, 29 Nov 2011 10:30:03 GMT',
			'ETag' => '24562346dgdgsdgf2352"',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'image/jpeg',
			'Content-Length' => 36410,
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);
		$result = $this->AmazonS3->handleResponse($HttpSocketResponse);
		$this->assertEqual(200 , $result);
	}
	
/**
 * testHandleResponseBadFormat
 *
 * @return void
 * @author Rob Mcvey
 * @expectedException InvalidArgumentException
 * @expectedExceptionMessage The response from Amazon S3 is invalid
 **/
	public function testHandleResponseBadFormat() {
		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '????JFIFdd??Duckya??Adobed??????????';
		$HttpSocketResponse->reasonPhrase = 'Foo';
		$HttpSocketResponse->headers = array(
			'x-amz-request-id' => 'GSDFGt45egdfsC',
		);
		$result = $this->AmazonS3->handleResponse($HttpSocketResponse);
	}	

/**
 * testPut
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testPut() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'dots.csv';
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'PUT',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'dots.csv'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:9Lsir9m2y16ffUi6v+KlRe0pGdA=',
				'Content-MD5' => 'L0O0L9gz0ed0IKja50GQAA==',
				'Content-Type' => 'text/plain',
				'Content-Length' => 3
			),
			'body' => '...'
		);

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '????JFIFdd??Duckya??Adobed??????????';
		$HttpSocketResponse->code = 200;
		$HttpSocketResponse->reasonPhrase = 'OK';
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Last-Modified' => 'Tue, 29 Nov 2011 10:30:03 GMT',
			'ETag' => '24562346dgdgsdgf2352"',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'image/jpeg',
			'Content-Length' => 0,
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);

		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with($expectedRequest)
			->will($this->returnValue($HttpSocketResponse));
	
		$this->AmazonS3->put($file_path);
	}
	
/**
 * testPutWithDir
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testPutWithDir() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'dots.csv';
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'PUT',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'some/dir/in/the/bucket/dots.csv'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:mCE9RQ8UJYRItzike6XZFd7XjcI=',
				'Content-MD5' => 'L0O0L9gz0ed0IKja50GQAA==',
				'Content-Type' => 'text/plain',
				'Content-Length' => 3
			),
			'body' => '...'
		);

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '????JFIFdd??Duckya??Adobed??????????';
		$HttpSocketResponse->code = 200;
		$HttpSocketResponse->reasonPhrase = 'OK';
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Last-Modified' => 'Tue, 29 Nov 2011 10:30:03 GMT',
			'ETag' => '24562346dgdgsdgf2352"',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'image/jpeg',
			'Content-Length' => 0,
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);

		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with($expectedRequest)
			->will($this->returnValue($HttpSocketResponse));

		$this->AmazonS3->put($file_path , '/some/dir/in/the/bucket/');
	}

/**
 * testPutWithMoreHeaders
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testPutWithMoreHeaders() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'dots.csv';
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'PUT',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'some/dir/in/the/bucket/dots.csv'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:WxdnOvuaK37BwO72xShLSFu80LI=',
				'Content-MD5' => 'L0O0L9gz0ed0IKja50GQAA==',
				'Content-Type' => 'text/plain',
				'Content-Length' => 3,
				'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
				'x-amz-acl' => 'public-read',
			),
			'body' => '...'
		);

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '????JFIFdd??Duckya??Adobed??????????';
		$HttpSocketResponse->code = 200;
		$HttpSocketResponse->reasonPhrase = 'OK';
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Last-Modified' => 'Tue, 29 Nov 2011 10:30:03 GMT',
			'ETag' => '24562346dgdgsdgf2352"',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'image/jpeg',
			'Content-Length' => 0,
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);

		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with($expectedRequest)
			->will($this->returnValue($HttpSocketResponse));
			
		$this->AmazonS3->amazonHeaders = array(
			'x-amz-acl' => 'public-read',
			'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
		);	

		$this->AmazonS3->put($file_path , '/some/dir/in/the/bucket/');
	}	
	
/**
 * testGet
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testGet() {
		$localPath = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files';
		$file = 'lolcat.jpg';
		$fullpath = $localPath . DS . $file;
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'GET',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'lolcat.jpg'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:diD8K7IEVSvuywBxAoERbEu1rXM=',
			)
		);

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '????JFIFdd??Duckya??Adobed??????????';
		$HttpSocketResponse->code = 200;
		$HttpSocketResponse->reasonPhrase = 'OK';
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Last-Modified' => 'Tue, 29 Nov 2011 10:30:03 GMT',
			'ETag' => '24562346dgdgsdgf2352"',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'image/jpeg',
			'Content-Length' => '36410',
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);
		
		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with($expectedRequest)
			->will($this->returnValue($HttpSocketResponse));
			
		$this->AmazonS3->get($file , $localPath);
		$this->assertFileExists($fullpath);
		
		$this->assertEquals('????JFIFdd??Duckya??Adobed??????????' , file_get_contents($fullpath));
		unlink($fullpath); // Bye bye
	}

/**
 * testDelete
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testDelete() {		
		$file = 'public/dots.csv';
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'DELETE',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'public/dots.csv'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:+UdJAQGhwJf06KRWWIXumX/5V4Y=',
			)
		);

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '';
		$HttpSocketResponse->code = 204;
		$HttpSocketResponse->reasonPhrase = 'No Content';
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);

		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with($expectedRequest)
			->will($this->returnValue($HttpSocketResponse));
		
		$this->AmazonS3->delete($file);
	}	
	
/**
 * testGetException
 *
 * @return void
 * @author Rob Mcvey
 * @expectedException AmazonS3Exception
 * @expectedExceptionMessage The AWS Access Key Id you provided does not exist in our records.
 **/
	public function testGetException() {
		$localPath = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files';
		$file = 'lolcat.jpg';
		$fullpath = $localPath . DS . $file;
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'GET',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'lolcat.jpg'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:diD8K7IEVSvuywBxAoERbEu1rXM=',
			)
		);

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>InvalidAccessKeyId</Code><Message>The AWS Access Key Id you provided does not exist in our records.</Message><RequestId>452345DFSG</RequestId><HostId>3425</HostId><AWSAccessKeyId>foo</AWSAccessKeyId></Error>';
		$HttpSocketResponse->code = 403;
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'application/xml',
			'Transfer-Encoding' => 'chunked',
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);

		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with($expectedRequest)
			->will($this->returnValue($HttpSocketResponse));
					
		$this->AmazonS3->get($file, $localPath);
		$this->assertFalse(file_exists($fullpath));
		unlink($fullpath); // Bye bye just in case
	}	
	
/**
 * testGetGenericException
 *
 * @return void
 * @author Rob Mcvey
 * @expectedException AmazonS3Exception
 * @expectedExceptionMessage There was an error communicating with AWS
 **/
	public function testGetGenericException() {
		$localPath = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files';
		$file = 'lolcat.jpg';
		$fullpath = $localPath . DS . $file;
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'GET',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'lolcat.jpg'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:diD8K7IEVSvuywBxAoERbEu1rXM=',
			)
		);

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>Woops</Code><RequestId>2342134123</RequestId><HostId>DFHGDHGDHDFGH</HostId><AWSAccessKeyId>foo</AWSAccessKeyId></Error>';
		$HttpSocketResponse->code = 403;
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'application/xml',
			'Transfer-Encoding' => 'chunked',
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);

		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with($expectedRequest)
			->will($this->returnValue($HttpSocketResponse));

		$this->AmazonS3->get($file, $localPath);
		$this->assertFalse(file_exists($fullpath));
		unlink($fullpath); // Bye bye just in case
	}	
	
/**
 * testGetBuggyXmlException
 *
 * @return void
 * @author Rob Mcvey
 * @expectedException AmazonS3Exception
 * @expectedExceptionMessage There was an error communicating with AWS
 **/
	public function testGetBuggyXmlException() {
		$localPath = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files';
		$file = 'lolcat.jpg';
		$fullpath = $localPath . DS . $file;
		$this->AmazonS3->setDate('Mon, 23 Sep 2013 08:46:05 GMT');

		// Mock the built request
		$expectedRequest = array(
			'method' => 'GET',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'lolcat.jpg'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:+UdJAQGhwJf06KRWWIXumX/5V4Y=',
			)
		);
		

		// Mock the HttpSocket response
		$HttpSocketResponse = new stdClass();
		$HttpSocketResponse->body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>Woops</Code><RequestId>2342134123</RequestId><HostId>DFHGDHGDHDFGH</HostId><AWSAccessKeyId>foo</AWSAccessKeyId></Error>';
		$HttpSocketResponse->code = 403;
		$HttpSocketResponse->headers = array(
			'x-amz-id-2' => '4589328529385938',
			'x-amz-request-id' => 'GSDFGt45egdfsC',
			'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
			'Accept-Ranges' => 'bytes',
			'Content-Type' => 'application/xml',
			'Transfer-Encoding' => 'chunked',
			'Connection' => 'close',
			'Server' => 'AmazonS3'
		);

		// Mock the HttpSocket class
		$this->AmazonS3->HttpSocket = $this->getMock('HttpSocket');
		$this->AmazonS3->HttpSocket->expects($this->once())
			->method('request')
			->with()
			->will($this->returnValue($HttpSocketResponse));
			
		$this->AmazonS3->get($file, $localPath);
		$this->assertFalse(file_exists($fullpath));
		unlink($fullpath); // Bye bye just in case
	}	
	
/**
 * testStringToSignGet
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testStringToSignGet() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files';
		$this->AmazonS3->file = 'lolcat.jpg';
		$this->AmazonS3->localPath = $file_path;
		$this->AmazonS3->setDate('Sun, 22 Sep 2013 14:43:04 GMT');
		$expected = "GET" . "\n";
		$expected .= "" . "\n";
		$expected .= "" . "\n";
		$expected .= "Sun, 22 Sep 2013 14:43:04 GMT" . "\n";
		$expected .= "/bucket/lolcat.jpg";	
		$result = $this->AmazonS3->stringToSign('get');
		$this->assertEqual($expected, $result);
	}

/**
 * testStringToSignPutPng
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testStringToSignPutPng() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files'. DS . 'avatars' . DS . 'copify.png';
		$this->AmazonS3->localPath = $file_path;
		$this->AmazonS3->file = basename($file_path);
		$this->AmazonS3->setDate('Sun, 22 Sep 2013 14:43:04 GMT');
		$expected = "PUT" . "\n";
		$expected .= "aUIOL+kLNYqj1ZPXnf8+yw==" . "\n";
		$expected .= "image/png" . "\n";
		$expected .= "Sun, 22 Sep 2013 14:43:04 GMT" . "\n";
		$expected .= "/bucket/copify.png"; 
		$result = $this->AmazonS3->stringToSign('put');
		$this->assertEqual($expected, $result); 	
	}	

/**
 * testStringToSignPutCsv
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testStringToSignPutCsv() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'dots.csv';
		$this->AmazonS3->localPath = $file_path;
		$this->AmazonS3->file = basename($file_path);
		$this->AmazonS3->setDate('Sun, 22 Sep 2013 14:43:04 GMT');
		$expected = "PUT" . "\n";
		$expected .= "L0O0L9gz0ed0IKja50GQAA==" . "\n";
		$expected .= "text/plain" . "\n";
		$expected .= "Sun, 22 Sep 2013 14:43:04 GMT" . "\n";
		$expected .= "/bucket/dots.csv"; 
		$result = $this->AmazonS3->stringToSign('put');
		$this->assertEqual($expected, $result); 	
	}
		
/**
 * testStringToSignPutCsv
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testStringToSignDeleteCsv() {
		$this->AmazonS3->file = 'lolcat.jpg';
		$this->AmazonS3->setDate('Sun, 22 Sep 2013 14:43:04 GMT');
		$expected = "DELETE" . "\n";
		$expected .= "" . "\n";
		$expected .= "" . "\n";
		$expected .= "Sun, 22 Sep 2013 14:43:04 GMT" . "\n";
		$expected .= "/bucket/lolcat.jpg";
		$result = $this->AmazonS3->stringToSign('delete');
		$this->assertEqual($expected, $result); 	
	}
	
/**
 * testStringToSignPutPngAdditionalHeaders
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testStringToSignPutPngAdditionalHeaders() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'avatars' . DS . 'copify.png';
		$this->AmazonS3->localPath = $file_path;
		$this->AmazonS3->file = basename($file_path);
		$this->AmazonS3->setDate('Sun, 22 Sep 2013 14:43:04 GMT');
		$this->AmazonS3->amazonHeaders = array(
			'x-amz-acl' => 'public-read',
			'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
		);
		$expected = "PUT" . "\n";
		$expected .= "aUIOL+kLNYqj1ZPXnf8+yw==" . "\n";
		$expected .= "image/png" . "\n";
		$expected .= "Sun, 22 Sep 2013 14:43:04 GMT";
		$expected .= "\n";
		$expected .= "x-amz-acl:public-read" . "\n";
		$expected .= "x-amz-meta-reviewedby:john.doe@yahoo.biz" . "\n";
		$expected .= "/bucket/copify.png"; 
		$result = $this->AmazonS3->stringToSign('put');
		$this->assertEqual($expected, $result); 	
	}	
	
/**
 * testBuildAmazonHeaders
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testBuildAmazonHeaders() {
		$this->AmazonS3->buildAmazonHeaders();
		$this->assertTrue(empty($this->AmazonS3->canonicalizedAmzHeaders));
		$this->AmazonS3->amazonHeaders = array(
			'x-amz-acl' => 'public-read',
			'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
		);
		$this->AmazonS3->buildAmazonHeaders();
		$this->assertEqual("x-amz-acl:public-read\nx-amz-meta-reviewedby:john.doe@yahoo.biz\n" , $this->AmazonS3->canonicalizedAmzHeaders);
	}

/**
 * testSortLexicographically
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testSortLexicographically() {
        $this->AmazonS3->amazonHeaders = array(
			'X-Amz-Meta-ReviewedBy' => 'bob@gmail.biz',
            'X-Amz-CUSTOM' => 'chicken-soup',
		);
		$this->AmazonS3->sortLexicographically();
		$result = $this->AmazonS3->amazonHeaders;
		$expected = array(
			'X-Amz-CUSTOM' => 'chicken-soup',
			'X-Amz-Meta-ReviewedBy' => 'bob@gmail.biz',
		);
		$this->assertSame($expected, $result);
		
		//
		$this->AmazonS3->amazonHeaders = array(
            'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
            'x-amz-acl' => 'public-read',  
        );
        $this->AmazonS3->sortLexicographically();
        $result = $this->AmazonS3->amazonHeaders;
        $expected = array(
            'x-amz-acl' => 'public-read',
    		'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
		);
		$this->assertSame($expected, $result);
	}

/**
 * testAddAmazonHeadersToRequest
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testAddAmazonHeadersToRequest() {
		// Request
		$request = array(
			'method' => 'PUT',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'some/dir/in/the/bucket/dots.csv'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:mCE9RQ8UJYRItzike6XZFd7XjcI=',
				'Content-MD5' => 'L0O0L9gz0ed0IKja50GQAA==',
				'Content-Type' => 'text/plain',
				'Content-Length' => 3
			),
			'body' => '...'
		);
		$this->AmazonS3->amazonHeaders = array(
			'x-amz-acl' => 'public-read',
			'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
		);
		$result = $this->AmazonS3->addAmazonHeadersToRequest($request);
		// Request
		$expected = array(
			'method' => 'PUT',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'some/dir/in/the/bucket/dots.csv'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:mCE9RQ8UJYRItzike6XZFd7XjcI=',
				'Content-MD5' => 'L0O0L9gz0ed0IKja50GQAA==',
				'Content-Type' => 'text/plain',
				'Content-Length' => 3,
				'x-amz-acl' => 'public-read',
				'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz',
			),
			'body' => '...'
		);
		$this->assertEqual($expected , $result);
	}		
	
/**
 * testAddAmazonHeadersToRequestNone
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testAddAmazonHeadersToRequestNone() {
		// Request
		$request = array(
			'method' => 'PUT',
			'uri' => array(
				'host' => 'bucket.s3.amazonaws.com',
				'scheme' => 'https',
				'path' => 'some/dir/in/the/bucket/dots.csv'
			),
			'header' => array(
				'Accept' => '*/*',
				'User-Agent' => 'CakePHP',
				'Date' => 'Mon, 23 Sep 2013 08:46:05 GMT',
				'Authorization' => 'AWS foo:mCE9RQ8UJYRItzike6XZFd7XjcI=',
				'Content-MD5' => 'L0O0L9gz0ed0IKja50GQAA==',
				'Content-Type' => 'text/plain',
				'Content-Length' => 3
			),
			'body' => '...'
		);
		$result = $this->AmazonS3->addAmazonHeadersToRequest($request);
		$this->assertEqual($request , $result);
	}	
	
/**
 * testGetContentMd5
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testGetContentMd5() {
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'csv_iso8859.csv';
		$this->AmazonS3->localPath = $file_path;
		$result = $this->AmazonS3->getContentMd5();
		$this->assertEqual('ywdK3sOES1D0A+NyHoVKeA==', $result);
		// Image
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'avatars' . DS . 'copify.png';
		$this->AmazonS3->localPath = $file_path;
		$result = $this->AmazonS3->getContentMd5();
		$this->assertEqual('aUIOL+kLNYqj1ZPXnf8+yw==', $result);
	}	

/**
 * testGetLocalFileInfo
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testGetLocalFileInfo() {
		// CSV
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'csv_iso8859.csv';
		$this->AmazonS3->localPath = $file_path;
		$result = $this->AmazonS3->getLocalFileInfo();
		$this->assertEqual('text/plain' , $result['mime']);
		$this->assertEqual(1114 , $result['filesize']);
		// Image
		$file_path = APP . 'Plugin' . DS . 'AmazonS3' . DS . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'files' . DS . 'avatars' . DS . 'copify.png';
		$this->AmazonS3->localPath = $file_path;
		$result = $this->AmazonS3->getLocalFileInfo();
		$this->assertEqual('image/png' , $result['mime']);
		$this->assertEqual(48319 , $result['filesize']);
	}
	
/**
 * testPublicUrl
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testPublicUrl() {
		$this->assertEqual('http://bucket.s3.amazonaws.com/lolcat.jpg' , $this->AmazonS3->publicUrl('lolcat.jpg'));
		$this->assertEqual('http://bucket.s3.amazonaws.com/lolcat.jpg' , $this->AmazonS3->publicUrl('/lolcat.jpg'));
		$this->assertEqual('http://bucket.s3.amazonaws.com/foo/lolcat.jpg' , $this->AmazonS3->publicUrl('foo/lolcat.jpg'));
		$this->assertEqual('http://bucket.s3.amazonaws.com/foo/lolcat.jpg' , $this->AmazonS3->publicUrl('/foo/lolcat.jpg'));
	}
	
/**
 * testSetDate
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testSetDate() {
		$this->AmazonS3->setDate('Sun, 22 Sep 2013 14:43:04 GMT');
		$this->assertEqual('Sun, 22 Sep 2013 14:43:04 GMT', $this->AmazonS3->date);
	}
	
/**
 * testCheckRemoteDir
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testCheckRemoteDir() {
		$this->AmazonS3->file = 'foo.jpg';
		$this->AmazonS3->checkRemoteDir('/flip/flop/bing/bing/toss/');
		$this->assertEqual('flip/flop/bing/bing/toss/foo.jpg' , $this->AmazonS3->file);
	}

/**
 * testSetDate
 *
 * @return void
 * @author Rob Mcvey
 **/
	public function testSignature() {
		$this->AmazonS3->setDate('Sun, 22 Sep 2013 14:43:04 GMT');
		$stringToSign = "PUT" . "\n";
		$stringToSign .= "aUIOL+kLNYqj1ZPXnf8+yw==" . "\n";
		$stringToSign .= "image/png" . "\n";
		$stringToSign .= "Sun, 22 Sep 2013 14:43:04 GMT";
		$stringToSign .= "\n";
		$stringToSign .= "x-amz-acl:public-read" . "\n";
		$stringToSign .= "x-amz-meta-reviewedby:john.doe@yahoo.biz" . "\n";
		$stringToSign .= "/bucket/copify.png";
		$signature = $this->AmazonS3->signature($stringToSign);
		$this->assertEqual('gbcL98O77pVLUSdcSIz4RCAots4=', $signature);
		
		$this->AmazonS3 = new AmazonS3(array('bang' , 'fizz', 'lulz'));
		$signature = $this->AmazonS3->signature($stringToSign);
		$this->assertEqual('dF2swNTRWEs9LiMxdxiVeWPwCR0=', $signature);
	}

}
