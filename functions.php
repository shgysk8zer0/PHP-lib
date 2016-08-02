<?php
namespace Lib\Functions;

const COMP_DIR      = './components';
const AUTOLOAD_DIR  = './classes';
const AUTOLOAD_EXT  = '.php';
const AUTOLOAD_FUNC = 'spl_autoload';

function is_cli()
{
	return in_array(PHP_SAPI, ['cli', 'cli-server']);
}

function php_version_check($version)
{
	if (version_compare(PHP_VERSION, $version, '<')) {
		if (PHP_SAPI !== 'cli') {
			header('Content-Type: text/plain');
			http_response_code(500);
			exit(sprintf('PHP version %s or greater required.', $version));
		} else {
			throw new \Exception(sprintf('PHP version %s or greater required.', $version));
		}
	}
}

function assert_callback($script, $line, $code = 0, $message = null)
{
	echo sprintf('Assert failed: [%s:%u] "%s"', $script, $line, $message) . PHP_EOL;
}

function init_assert(Callable $callback = null)
{
	if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
		// cli_init();
		if (is_null($callback)) {
			$callback = __NAMESPACE__ . '\assert_callback';
		}
		assert_options(ASSERT_ACTIVE,   true);
		assert_options(ASSERT_BAIL,     true);
		assert_options(ASSERT_WARNING,  false);
		assert_options(ASSERT_CALLBACK, $callback);
	} else {
		assert_options(ASSERT_ACTIVE,  false);
		assert_options(ASSERT_BAIL,    false);
		assert_options(ASSERT_WARNING, false);
	}
}

function autoloader(
	Array $path    = array(AUTOLOAD_DIR),
	Callable $func = AUTOLOAD_FUNC,
	Array $exts    = array(AUTOLOAD_EXT)
)
{
	set_include_path(join(PATH_SEPARATOR, array_map('realpath', $path)));
	spl_autoload_register($func);
	spl_autoload_extensions(join(null, $exts));
}

function cli_init($config = './config/env.json')
{
	$vars = json_decode(file_get_contents($config), true);
	forEach($vars as $key => $value) {
		putenv("$key=$value");
	}
}

function json_decode_file($file, $assoc = false)
{
	static $files = array();
	if (array_key_exists($file, $files)) {
		return $files[$file];
	} else if (file_exists($file)) {
		$files[$file] = json_decode(file_get_contents($file), $assoc);
		return $files[$file];
	} else {
		throw new \Exception(sprintf('File "%s" not found in %s', $file, __FUNCTION__));
	}
}

function filename($file)
{
	$ext = pathinfo($file, PATHINFO_EXTENSION);
	return basename($file, ".$ext");
}

function load()
{
	array_map(__NAMESPACE__ . '\load_script', func_get_args());
}

function load_script($script)
{
	static $args = null;
	if (is_null($args)) {
		$args = array(
			\shgysk8zer0\DOM\HTML::getInstance(),
			\shgysk8zer0\Core\Headers::getInstance()
		);
	}

	$ret = require_once COMP_DIR . DIRECTORY_SEPARATOR . "{$script}.php";

	if (is_callable($ret)) {
		call_user_func_array($ret, $args);
	}
}
