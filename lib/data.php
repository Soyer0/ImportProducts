<?php //if (!defined('SYS_PATH')) exit('Access denied');

/*
 * Шлях: SYS_PATH/libraries/data.php
 *
 * Отримуємо POST дані і частини URI
 *
 * 1.0.1 (06.11.2014) Додано insertFromForm()
 * 1.0.2 (29.09.2015) Додано getShortText()
 * 1.0.3 (19.10.2015) Бібліотеку розділено на 2 (data + form), модифіковано функцію make()
 * 1.0.4 (04.12.2015) Виправлено getShortText()
 * 1.0.5 (09.03.2016) Виправлено констуктор Data() згідно режиму мультимовності 'main domain', додано заміну "багато дефів" на один у latterUAtoEN(), додано перевірку існування папки у removeDirectory()
 * 1.1 (07.04.2016) Додано re_get(), re_post()
 * 1.1.1 (22.07.2016) До re_get(), re_post() додано значення за замовчуванням; приведено дос тандарту php7
 * 1.2 (27.09.2016) Виправлено make()
 * 1.2.1 (17.10.2016) Виправлено latterUAtoEN(): додано символ +
 * 1.2.2 (24.10.2016) Виправлено make(): правильно розпізнає поля як з типом так і без; непередані поля
 * 1.3 (06.12.2016) Додано get_link() - формування лінку за GET зі змінами
 * 1.3.1 (20.01.2017) Функцію make() перейменовано на prepare()
 * 1.3.2 (14.09.2017) Fix get_link(): коректно працює із масивами в get
 * 1.3.3 (05.09.2018) Add userIP()
 * 1.3.4 (28.11.2018) Fix get_link(): масив в get. Якщо є підзначення:забрати, нема: додати
 * 1.3.5 (11.09.2019) getShortText(): add $after, якщо останній обрізаний символ не число чи літера, обрізається теж
 * 1.4 (16.10.2019) Додано formatPhone() - форматний вивід номеру телефону
 * 1.4.1 (18.11.2019) Додано parse_size() - отримати розмір у байтах
 * 1.4.2 (19.11.2019) Додано get_file_path() - сформувати шлях до файлу для wl_files, wl_images..
 * 1.4.3 (05.08.2020) До url() додано $as_text - як текст а не масив
 * 1.4.4 (22.09.2020) xss_clean() може обробляти масиви, private => public
 * 1.4.5 (19.06.2021) latterUAtoEN() розширено символами ( ) *
 * 1.4.6 (07.08.2021) get_link() add param `skip_key`
 * 1.4.7 (03.01.2022) get_link() fix empty `new_value` for _GET:array
 * 1.4.8 (16.03.2023) removeDirectory() with patern
 * 1.4.9 (28.03.2024) add create_check_path()
 * 1.5.0 (23.04.2024) format_filesize()
 * 1.5.1 (11.11.2024) fix xss_clean() array
 * 1.5.2 (**.12.2024) fix create_check_path(), add get_subpathByContent()
 * 1.5.3 (**.02.2025) enhance latterUAtoEN function (add support for 'ü' and 'ä'); improve get_subpathByContent(), formatPhone() logic
 * 1.5.4 (**.03.2025) add download_file()
 */

class Data
{

	private $uri_data;
	public $errors = array();

	function __construct()
	{
		$arr = (empty($_GET['request'])) ? '' : $_GET['request'];
		$arr = trim($arr, '/\\');
		$arr = explode('/', $arr);

		if (end($arr) == 'amp') {
			$_SESSION['amp'] = true;
			array_pop($arr);
		}
		$this->uri_data = $arr;
		if ($_SESSION['language'] && ($GLOBALS['multilanguage_type'] == 'main domain' || $_SERVER["SERVER_NAME"] == 'localhost')) {
			if (isset($_SESSION['language']) && $_SESSION['language'] != $_SESSION['all_languages'][0] && in_array($_SESSION['language'], $_SESSION['all_languages']))
				array_shift($this->uri_data);
		}

		if (!empty($_POST)) {
			foreach ($_POST as $key => $value) {
				if (is_array($value))
					foreach ($value as $key2 => $value2) {
						if (is_array($value2)) {
							foreach ($value2 as $key3 => $value3) {
								$_SESSION['_POST'][$key][$key2][$key3] = $this->xss_clean($value3);
							}
						} else
							$_SESSION['_POST'][$key][$key2] = $this->xss_clean($value2);
					}
				else
					$_SESSION['_POST'][$key] = $this->post($key);
			}
		}
		if (!empty($_GET)) {
			foreach ($_GET as $key => $value) {
				if (is_array($value))
					foreach ($value as $key2 => $value2) {
						$_SESSION['_GET'][$key][$key2] = $this->xss_clean($value2);
					}
				else
					$_SESSION['_GET'][$key] = $this->get($key);
			}
		}
	}

	/**
	 * Отримуємо частину URI
	 *
	 * @param int частина
	 * @param bool очистити від xss
	 */
	public function uri($idx = null, $xss = false)
	{
		if ($idx && array_key_exists($idx, $this->uri_data)) {
			if ($xss)
				return $this->xss_clean($this->uri_data[$idx]);
			else
				return $this->uri_data[$idx];
		}
		return null;
	}

	public function url($as_text = false)
	{
		if ($as_text)
			return implode('/', $this->uri_data);
		return $this->uri_data;
	}

	/**
	 * Отримуємо POST дані
	 *
	 * @param string ключ
	 * @param bool очистити від xss
	 */
	public function post($key, $xss = true)
	{
		if ($key && array_key_exists($key, $_POST)) {
			if ($xss)
				return $this->xss_clean($_POST[$key]);
			else
				return $_POST[$key];
		}
		return null;
	}

	public function get($key, $xss = true)
	{
		if ($key && array_key_exists($key, $_GET)) {
			if ($xss)
				return $this->xss_clean($_GET[$key]);
			else
				return $_GET[$key];
		}
		return null;
	}

	public function re_post($key = '', $default = false)
	{
		if (isset($_SESSION['_POST'][$key]))
			return $_SESSION['_POST'][$key];
		return $default;
	}

	public function re_get($key = '', $default = false)
	{
		if (isset($_SESSION['_GET'][$key]))
			return $_SESSION['_GET'][$key];
		return $default;
	}

	public function get_link($new_key = '', $new_value = '', $skip_key = '')
	{
		$link = SERVER_URL . $_GET['request'];
		if (($new_key != '' && $new_value != '') || count($_GET) > 1) {
			$link .= '?';
			$updated = false;
			$nk2 = $new_key;
			if (substr($nk2, -2) == '[]')
				$nk2 = substr($nk2, 0, -2);
			foreach ($_GET as $key => $value) {
				if ($key != 'request' && $key != $skip_key) {
					if (substr($key, -2) == '[]')
						$key = substr($key, 0, -2);
					if ($key == $new_key && $new_value == '') {
						$updated = true;
						continue;
					}
					if (is_array($value)) {
						if ($key == $nk2) {
							if (in_array($new_value, $value)) {
								$updated = true;
								foreach ($value as $sub_value) {
									if ($new_value != $sub_value)
										$link .= $key . '[]=' . $sub_value . '&';
								}
							} else
								foreach ($value as $sub_value) {
									$link .= $key . '[]=' . $sub_value . '&';
								}
						} else
							foreach ($value as $sub_value) {
								$link .= $key . '[]=' . $sub_value . '&';
							}
					} elseif ($key != $nk2 && $value != '')
						$link .= $key . '=' . $value . '&';
					elseif ($key == $new_key && $new_value != '') {
						$link .= $key . '=' . $new_value . '&';
						$updated = true;
					}
				}
			}
			if (!$updated && $new_value != '')
				$link .= $new_key . '=' . $new_value . '&';
			$link = substr($link, 0, -1);
		}
		return $link;
	}

	public function getShortText($text, $len = 155, $after = '..')
	{
		$text = strip_tags(html_entity_decode($text));
		if (mb_strlen($text, 'UTF-8') > $len) {
			$pos = mb_strpos($text, ' ', $len, 'UTF-8');
			if ($pos) {
				$subText = mb_substr($text, 0, $pos, 'UTF-8');
				if (!preg_match('/[a-z0-9]+/i', mb_substr($subText, -1)))
					$subText = mb_substr($subText, 0, -1);
				return $subText . $after;
			} else {
				$pos = mb_strpos($text, ' ', $len - 10, 'UTF-8');
				if ($pos) {
					$subText = mb_substr($text, 0, $pos, 'UTF-8');
					if (!preg_match('/[a-z0-9]+/i', mb_substr($subText, -1)))
						$subText = mb_substr($subText, 0, -1);
					return $subText . $after;
				}
			}
		}
		return $text;
	}

	/**
	 * Отримуємо та готуємо POST дані до вставки в бд
	 *
	 * @param string ключ
	 * @param bool очистити від xss
	 */
	public function prepare($fields, $var = '_POST')
	{
		if (!empty($fields) && is_array($fields)) {
			$data = array();
			foreach ($fields as $f => $type) {
				$value = null;
				if ($var == '_POST')
					$value = $this->post($f, true);
				else
					$value = $this->get($f, true);
				if ($value !== null) {
					if ($type == 'number' && is_numeric($value))
						$data[$f] = $value;
					elseif ($type == 'date') {
						$date = explode('.', $value);
						if (count($date) == 3) {
							$date = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
							if (is_numeric($date))
								$data[$f] = $date;
						}
					} else
						$data[$f] = $value;
				} elseif (is_numeric($f)) {
					if ($var == '_POST')
						$data[$type] = $this->post($type, true);
					else
						$data[$type] = $this->get($type, true);
				}
			}
			return $data;
		}
		return null;
	}

	/**
	 * Очищуємо від xss
	 */
	public function xss_clean($value)
	{
		if (is_array($value))
			foreach ($value as &$v) {
				$v = $this->xss_clean($v);
			}
		else if (is_string($value))
			return htmlspecialchars(trim($value), ENT_QUOTES);
		return $value;
	}

	public function latterUAtoEN($text)
	{
		$text = mb_strtolower($text, "utf-8");
		$ua = array('а', 'б', 'в', 'г', 'ґ', 'д', 'е', 'є', 'ж', 'з', 'и', 'і', 'ї', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч',  'ш',  'щ', 'ь', 'ю', 'я', 'ы', 'ё', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '-', '_', ' ', '`', '~', '!', '@', '#', '$', '%', '^', '&', '"', ',', '\.', '\?', '/', ';', ':', '\*', '\'', '[+]', '“', '”', '\(', '\)', '№', '\|', 'ü', 'ä');
		$en = array('a', 'b', 'v', 'h', 'g', 'd', 'e', 'e', 'zh', 'z', 'y', 'i', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'c', 'ch', 'sh', 'sch', '', 'u', 'ja', 'y', 'e', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '-', '-', '-', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'u', 'a');
		for ($i = 0; $i < count($ua); $i++) {
			$text = mb_eregi_replace($ua[$i], $en[$i], $text);
		}
		return mb_eregi_replace("[-]{2,}", '-', $text);
	}

	public function removeDirectory($dir, $patern = '/*')
	{
		if (is_dir($dir)) {
			if ($objs = glob($dir . $patern)) {
				foreach ($objs as $obj) {
					is_dir($obj) ? $this->removeDirectory($obj) : unlink($obj);
				}
			}

			rmdir($dir);
		}
	}

	// Вивід номеру телефону у форматі +00 (000) 000-00-00
	public function formatPhone($phone, $tag = '')
	{
		$phone = trim($phone);
		if (empty($phone))
			return $phone;
		if ($phone[0] == '+')
			$phone = substr($phone, 1);
		if (strlen($phone) == 12) {
			$format = ($tag) ? "<{$tag}>+" : '+';
			$parts = [2, 3, 3, 2, 2];
			$start = 0;
			foreach ($parts as $i => $length) {
				$part = substr($phone, $start, $length);
				$start += $length;
				if ($i == 1)
					$format .= " ({$part}) ";
				else
					$format .= $part;
				if ($i > 1 && $i < 4)
					$format .= '-';
			}
			if ($tag)
				$format .= "</{$tag}>";
			return $format;
		}
		return $phone;
	}

	public function get_file_path($file, $folder = 'files')
	{
		$alias_folder = !empty($_SESSION['option']->folder) ? $_SESSION['option']->folder : $_SESSION['alias']->alias;
		return SERVER_URL . $folder . DIRSEP . $alias_folder . DIRSEP . $file->content . DIRSEP . $file->name;
	}

	public function parse_size($size)
	{
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size);

		// Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\\.]/', '', $size);

		// Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}

	public function format_filesize(int $size, int $precision = 2): string
	{
		$base = log($size, 1024);
		$suffixes = array('', 'Kb', 'Mb', 'Gb', 'Tb');

		return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
		
	}

	public function userIP()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
			return $_SERVER['HTTP_CLIENT_IP'];
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif (!empty($_SERVER['REMOTE_ADDR']))
			return $_SERVER['REMOTE_ADDR'];
		return false;
	}
	public function getUserAgent ()
	{
		if ( !empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
			return $_SERVER[ 'HTTP_USER_AGENT' ];
		}

		return '';
	}

	/**
	 * Check if the request is an AJAX request
	 * @return bool
	 */
	public function isAjax()
	{
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	/**
	 * Check if the request method is POST
	 * @return bool
	 */
	public function isPost ()
	{
		if(strtolower($_SERVER['REQUEST_METHOD']) == 'post')
			return true;
		return false;
	}

	public function create_check_path(string $path)
	{
		if (empty($path)) {
			return false;
		}

		$path = str_replace(DIRSEP, '/', $path);
		if (is_dir($path)) {
			return true;
		}

	    if (substr($path, 0, 1) == '/') {
		    $root_path = str_replace(DIRSEP, '/', ROOT_PATH);
		    if (substr($path, 0, strlen($root_path))) {
			    $path = substr($path, strlen($root_path));
			    if (substr($path, 0, 1) == '/') {
				    $path = substr($path, 1);
			    }
		    }
	    }

		$path = explode('/', $path);
		$url = array_shift($path);
		if (!empty($url) && !is_dir($url)) {
			if (mkdir($url, 0777) == false) {
				return false;
			}
		}
		foreach ($path as $uri) {
			$url .= '/' . $uri;
			if (!is_dir($url)) {
				if (mkdir($url, 0777) == false) {
					return false;
				}
			}
		}
		return true;
	}

	public function download_file($external_file_path, $local_file_path) {
		// Initialize a new cURL session
		$ch = curl_init($external_file_path);

		// Open the local file
		$fp = fopen($local_file_path, 'w');

		// Set cURL options
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// Execute the cURL session
		if (curl_exec($ch) === false) {
			exit("Error downloading file from {$external_file_path}");
		}

		// Close the cURL session and the local file
		curl_close($ch);
		fclose($fp);

		return true;

		// $file_data = file_get_contents($external_file_path);
		// if ($file_data === false) {
		// 	exit("Error downloading file from {$external_file_path}");
		// }

		// $result = file_put_contents($local_file_path, $file_data);
		// if ($result === false) {
		// 	exit("Error saving file to {$local_file_path}");
		// }
		// return true;
	}

	public function get_subpathByContent($content_id = 0, $depth = 2)
	{
		if ($depth == 0 || $content_id == 0 || !is_numeric($content_id))
			return $content_id;
		$p_100 = ceil($content_id / 100) * 100;
		if ($depth == 1)
			return "{$p_100}/{$content_id}";
		// $depth == 2
		$p_1000 = ceil($content_id / 1000) * 1000;
		return "{$p_1000}/{$p_100}/{$content_id}";
	}



}
