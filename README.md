# HttpCors
	esay way for solving HTTP CORS request
	PHP 简单的应对CORS请求，关于[CORS](http://newhtml.net/using-cors/)

# Sample

```php
	$cors = new HttpCors();
	$cors->addRule('*');//允许所有的Origin进行访问
	$cors->addRule('http://*.a.com');//允许某子域名访问
	$cors->addRule('http://c.b.com');
	$cors->addRule('http://e.d.com', [
		'allowMethods'  => ['put','post','get','options'],# Access-Control-Allow-Methods
		'allowHeaders'  => ['custom-header','custom-header2'],# Access-Control-Allow-Headers
		'exposeHeaders' => [],   # Access-Control-Expose-Headers
		'credentials'   => true, # Access-Control-Allow-Credentials
		'maxAge'        => 0,    # Access-Control-Max-Age
	]);
	$cors->response();# 自动根据跨域情况，内部执行 exit 操作
```
