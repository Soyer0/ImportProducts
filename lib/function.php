<?php
function pr($key)
{
    echo "<pre>";
    print_r($key);
    echo "</pre>";
}
function pp($key)
{
    pr($key);
    exit;
}

function vd($key)
{
    echo "<pre>";
    var_dump($key);
    echo "</pre>";
}
function dd($key)
{
    vd($key);
    exit;
}


function mp(...$parameters)
{
	if((defined('WL_MODE') && WL_MODE != 'dev') && !isset($_COOKIE['DEV']))
		return;


	$trace_ = debug_backtrace();
	$trace = $trace_[ 0 ];

	$callerFile = $trace['file'];
	$sourceCode = file($callerFile);

	$line = $sourceCode[$trace['line'] - 1];
	preg_match('/mp\((.*)\)/', $line, $matches);
	if ( !empty( $matches[ 1 ] ) ){
		$pars = explode(',', $matches[ 1 ]);
		$tmpArr = $parameters;
		$parameters = [];
		foreach ($pars as $i => $name){
			$parameters[ trim( $name ) ] = $tmpArr[ $i ];
		}
		unset($tmpArr);
	}


	$trace[ "file" ] = str_replace( $_SERVER[ "DOCUMENT_ROOT" ], "", $trace[ "file" ] );

	if(empty($_COOKIE['debug_js'])) {

		echo '<br><pre style="background-color: #fff; color: #333; z-index: 99999; display: block; clear: both;">';
		echo $trace[ "file" ].':'.$trace[ "line" ];

		echo (defined('SHELL_MODE') && SHELL_MODE) ? "\n" : "<br />\n";

		$first = false;
		foreach ( $parameters as $i => $ar_ ) {
			if ( $first ) {
				echo (defined('SHELL_MODE') && SHELL_MODE) ? "\n" : "<br />\n";
			}
			$first = true;

			echo (is_numeric($i) ? ($i + 1 ) : $i).': ';
			print_r( $ar_ );
		}
		echo (defined('SHELL_MODE') && SHELL_MODE) ? "\n" : "<br />\n";
		echo "---end</pre>";
		echo (defined('SHELL_MODE') && SHELL_MODE) ? "\n" : "<br />\n";
	} else {

		$arr = ['file' => $trace[ "file" ].':'.$trace[ "line" ], 'params' => $parameters];
		echo '<script>console.log('.json_encode($arr).');</script>';
	}
}
function fSort ( $sort, &$data )
{
	usort( $data, function ( $a, $b ) use ( $sort )
	{
		$key = key( $sort );
		$order = $sort[ $key ];

		if ( $a->{$key} == $b->{$key} ) {
			return 0;
		}

		$val1 = -1;
		$val2 = 1;
		if ( $order == 'desc' ) {
			$val1 = 1;
			$val2 = -1;
		}

		return ( $a->{$key} < $b->{$key} ) ? $val1 : $val2;
	} );
}


function emoji (){
	global $containerEmoji;
	if(is_object($containerEmoji) && !empty($containerEmoji)){
		return $containerEmoji;
	}

	require_once SYS_PATH . 'libraries/emoji.php';
	$containerEmoji = new \emoji();
	return $containerEmoji;
}

function debug_string_backtrace() {
	ob_start();
	debug_print_backtrace();
	$trace = ob_get_contents();
	ob_end_clean();

	// Remove first item from backtrace as it's this function which is redundant.
	// $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);
	// Renumber backtrace items.
	// $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);
	return $trace;
}