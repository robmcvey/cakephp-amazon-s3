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

* Add `app/Lib/AmazonS3.php` to your project
* Include the library with `App::uses('AmazonS3', 'Lib');`

### Usuage examples

Initialise the class with your AWS Access key, secret key and the bucket name you wish to work with.

```php
$AmazonS3 = new AmazonS3(array('{access key}', '{secret key}', '{bucket name}'));
```

### GET

The `get` method retrieves a remote file and saves it locally. So let's say there is the file `foo.jpg` on S3 and you want to save it locally in `/home/me/stuff/photos` you'd use the following.

```php
$AmazonS->get('foo.jpg' , '/home/me/stuff/photos');
```

### PUT

The `put` method does the reverse of `get`, and saves a local file to S3.

```php
$AmazonS->put('/home/me/stuff/photos/foo.jpg');
```

You can optionally specifiy a remote directory within the bucket to save the file in.

```php
$AmazonS->put('/home/me/stuff/photos/foo.jpg' , 'some/folder');
```

### DELETE

Deletes a file from S3.

```php
$AmazonS->delete('foo.jpg');
```

Or delete from within a directory in the bucket:

```php
$AmazonS->delete('/some/folder/foo.jpg');
```