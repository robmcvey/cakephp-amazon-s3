h2. cakephp-amazon-s3

CakePHP 1.3.x component which provides RESTful GET, PUT and DELETE functionality for Amazon S3 without the need for any PEAR packages

h3. Requirements

* PHP 5.2 or above
* CakePHP 1.3.x branch
* Won't work on Windows. Oh well.

h3. Examples

The component has 3 main variables that you can set depending on your particular setup;

<pre>$this->AmazonS3->local_dir</pre> This is the local directory you want to keep your stuff. Required for GET and PUT requests.

<pre>$this->AmazonS3->local_object</pre> This is the local item relative to $local_dir.  Required when attempting a PUT.

<pre>$this->AmazonS3->remote_object</pre> This is the item on Amazon relative to the bucket. Required when attempting a GET or DELETE request.

h3. GET

<pre>
$this->AmazonS3->remote_object = 'WurcJ.jpg'; 
$this->AmazonS3->local_dir = WWW_ROOT.'files/amazons3/test'; 
if($this->AmazonS3->get()) {
	debug($this->AmazonS3->results);
} else {
	debug($this->AmazonS3->errors);
}
</pre>

h3. PUT

<pre>
$this->AmazonS3->local_dir = WWW_ROOT.'files/amazons3'; 
$this->AmazonS3->local_object = 'subfolder/plain.txt'; 
if($this->AmazonS3->put()) {
	debug($this->AmazonS3->results);
} else {
	debug($this->AmazonS3->errors);
}
</pre>

h3. DELETE

<pre>
// DELETE example
$this->AmazonS3->remote_object = 'folder_within_bucket/unwanted.txt'; 
if($this->AmazonS3->delete()) {
	debug($this->AmazonS3->results);
} else {
	debug($this->AmazonS3->errors);
}
</pre>

h3. References

Amazon REST api docs
http://docs.amazonwebservices.com/AmazonS3/latest/dev/