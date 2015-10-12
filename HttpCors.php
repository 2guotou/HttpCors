<?php
class HttpCors
{
	private $map = array();
	private $preflight_request = FALSE;
	
	public function __construct(){}
	
	public function addRule($origin, array $rule=array())
	{
		$this->map[$origin] = array_merge(
			array(
				'allowMethods'  => array(),
				'allowHeaders'  => array(),
				'exposeHeaders' => array(),
				'credentials'   => FALSE,
				'maxAge'        => 0,
				),
			$rule
		);
		$this->map[$origin]['allowMethods']  = self::_mapTranform($this->map[$origin]['allowMethods']);
		$this->map[$origin]['allowHeaders']  = self::_mapTranform($this->map[$origin]['allowHeaders']);
		$this->map[$origin]['exposeHeaders'] = self::_mapTranform($this->map[$origin]['exposeHeaders']);
	}
	/* 调整转换录入的 mapRule 的内容 */
	private static function _mapTranform($input)
	{
		if( is_array($input) ){
			foreach ($input as $key => $value) {
				$input[$key] = strtolower(trim((string)$value));
			}
			return $input;
		}else{
			return array();
		}
	}

	public function response($isReturn = FALSE)
	{
		$origin    = strtolower( self::_getHeader('origin') );
		$method    = strtolower( self::_getHeader('method') );
		$ACRMethod = strtolower( self::_getHeader('access-control-request-method') );
		$ACRHeader = strtolower( self::_getHeader('access-control-request-header') );
		/* Normal Request : 因为不携带 Origin 头信息 */
		if( !$origin ){ 
			return TRUE; 
		}
		/* White List Filter : 不允许访问的 Origin，程序终止响应 */
		$current = $this->_getMatchMap( $origin );
		if( !$current || !is_array( $current ) ){
			header('Sorry: '.$origin.'Not Allow Access');
			if( $isReturn ) { return FALSE; } else { exit; }
		}

		/* Preflight Check Request : 预检请求 */
		if( 'options'==$method and $ACRMethod){
			$this->preflight_request = TRUE;
			/* 告知 User Agent 浏览器 允许的范畴 */
			header('Access-Control-Allow-Methods:'.implode(', ', $current['allowMethods']));
			header('Access-Control-Allow-Headers:'.implode(', ', $current['allowHeaders']));
			/* 是否为准许实际请求的 Method */
			if( !in_array($ACRMethod, $current['allowMethods']) ){
				header("Sorry: Method '{$ACRMethod}' Not Allow");
				if( $isReturn ) { return FALSE; } else { exit; }
			}
			/* 是否为准许的实际请求的 Header */
			if( $ACRHeader ){
				$ACRHeaders = explode(',', $ACRHeader);
				foreach($ACRHeaders as $v){
					$v = trim((string)$v);
					if( !in_array($v, $current['allowHeaders']) ){
						header("Sorry: Real Request Header '{$v}' Not Allow");
						if( $isReturn ) { return FALSE; } else { exit; }
					}
				}
			}
			if( $current['maxAge'] ){
				header('Access-Control-Max-Age:'.$current['maxAge']);
			}
		}
		/* Simple CORS Request */
		else{
			if( count($current['exposeHeaders']) ){
				header('Access-Control-Expose-Headers:'.implode(', ', $current['exposeHeaders']));
			}
		}
		if( $current['credentials'] ){
			header('Access-Control-Allow-Credentials: true');
		}
		header('Access-Control-Allow-Origin:'.$origin);
		if( $this->preflight_request ){
			if( $isReturn ) { return FALSE; } else { exit; }
		}
		return TRUE;
	}
	/* 获取 Request Header 头信息 */
	private static function _getHeader($header)
	{
		$header = strtoupper(str_replace('-', '_', $header));
		$prefix = ('METHOD'==$header) ? 'REQUEST_' : 'HTTP_';
		$header = $prefix.$header;
		return isset( $_SERVER[$header] ) ? $_SERVER[$header] : NULL;
	}

	/* return FALSE or the current Key */
	private function _getMatchMap( $origin )
	{
		if( isset($this->map[$origin]) ){
			return $this->map[$origin];
		}
		$origins = array_keys($this->map);
		/* 是否存在 * 通配规则 */
		foreach ($origins as $mapkey) {
			if( '*' == $mapkey ){
				return $this->map[$mapkey];
			}
		}
		/* 是否存在 *.domain 通配规则 */
		foreach ($origins as $mapkey) {
			$domain1 = strstr($mapkey, '://*.');
			if( !$domain1 ) continue;
			$domain1 = substr($domain1, 5);//字符串 ://*. 长度为5
			if( !$domain1 ) continue;
			$domain2 = stristr($origin, $domain1);
			if( $domain1 == $domain2 ){
				return $this->map[$mapkey];
			}
		}
		return NULL;
	}
}
?>
