# Amazon S3 Utility for CakePHP 2.3.x

A utility class to interact with Amazon Web Services S3 objects. This class provides a simple and robust library that can be added to any CakePHP project to complete the following:

* Retrieve a remote file from an S3 bucket and save locally
* Save a local file in an S3 bucket
* Delete a file in an S3 bucket

### Requirements

* CakePHP 2.3.x
* An Amazon Web Services account (http://aws.amazon.com/s3/)
* Your AWS access key and secret key

### Installation

_[Manual]_

* Download this: [http://github.com/robmcvey/cakephp-amazon-s3/zipball/master](http://github.com/robmcvey/cakephp-amazon-s3/zipball/master)
* Unzip that download.
* Copy the resulting folder to `app/Plugin`
* Rename the folder you just copied to `AmazonS3`

_[GIT Submodule]_

In your app directory type:

```shell
git submodule add -b master git://github.com/robmcvey/cakephp-amazon-s3.git Plugin/AmazonS3
git submodule init
git submodule update
```

_[GIT Clone]_

In your `Plugin` directory type:

```shell
git clone -b master git://github.com/robmcvey/cakephp-amazon-s3.git AmazonS3
```

### Usuage examples

Initialise the class with your AWS Access key, secret key and the bucket name you wish to work with.

```php
App::uses('AmazonS3', 'AmazonS3.Lib');
$AmazonS3 = new AmazonS3(array('{access key}', '{secret key}', '{bucket name}'));
```

### GET

The `get` method retrieves a remote file and saves it locally. So let's say there is the file `foo.jpg` on S3 and you want to save it locally in `/home/me/stuff/photos` you'd use the following.

```php
$AmazonS3->get('foo.jpg' , '/home/me/stuff/photos');
```

### PUT

The `put` method does the reverse of `get`, and saves a local file to S3.

```php
$AmazonS3->put('/home/me/stuff/photos/foo.jpg');
```

You can optionally specifiy a remote directory within the bucket to save the file in.

```php
$AmazonS3->put('/home/me/stuff/photos/foo.jpg' , 'some/folder');
```

To add any additional AWS headers to a `put`, example to set the file as "public", they can be passed as an array to the `amazonHeaders` property.

```php
$AmazonS3->amazonHeaders = array(
	'x-amz-acl' => 'public-read',
	'X-Amz-Meta-ReviewedBy' => 'john.doe@yahoo.biz'
);
$AmazonS3->put('/home/me/stuff/photos/foo.jpg' , 'some/folder');
```

### DELETE

Deletes a file from S3.

```php
$AmazonS3->delete('foo.jpg');
```

Or delete from within a directory in the bucket:

```php
$AmazonS3->delete('/some/folder/foo.jpg');
```
