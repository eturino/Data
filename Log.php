<?php

/**
 * LoggingModel
 *
 * This class is called through the observable class.
 *
 * It stores al logs and connections to the DB and logs it
 *
 * @author Sergio modified by Eduardo
 * @version
 */
class EtuDev_Data_Log {

	const LOG_DIRECTORY = './applog';

	const MODULE_DEFAULT = 'error.log';
	const MODULE_DATA    = 'data.log';
	const MODULE_404     = '404.log';

	const FORCED = -1; // FORCED to be logged: system is unusable
	const EMERG = 0; // Emergency: system is unusable
	const ALERT = 1; // Alert: action must be taken immediately
	const CRIT = 2; // Critical: critical conditions
	const ERR = 3; // Error: error conditions
	const WARN = 4; // Warning: warning conditions
	const NOTICE = 5; // Notice: normal but significant condition
	const INFO = 6; // Informational: informational messages
	const DEBUG = 7; // Debug: debug messages

	static public function get_page_url($querystring = true) {
		$url = 'http';
		if (isset($_SERVER) && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$url .= 's';
		}
		$url .= '://' . $_SERVER['SERVER_NAME'];
		if (isset($_SERVER) && isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
			$url .= ':' . $_SERVER['SERVER_PORT'];
		}
		if ($querystring) {
			$url .= $_SERVER["REQUEST_URI"];
		} else {
			$parts = parse_url($_SERVER["REQUEST_URI"]);
			if ($parts['path']) {
				$url .= $parts['path'];
			} else {
				$url .= $_SERVER['SCRIPT_NAME'];
			}
		}
		return $url;
	}

	static public function log404($message = null) {
		if ($message) {
			$message .= '. ';
		}
		$message .= 'URL pedida:' . static::get_page_url() . ', GET:' . json_encode($_GET);
		return self::logCustom($_SERVER['REQUEST_URI'], $message, self::ERR, self::MODULE_404);
	}

	static public function log($caller, $message, $level, $module = NULL) {
		return self::logCustom($caller, $message, $level, $module);
	}

	static public function logException(Exception $exception, $caller = null, $module = null) {
		static::log($caller ?: 'EXCEPTION', $exception->getMessage() . " \n trace: " . $exception->getTraceAsString(), self::ERR, $module);
	}

	/**
	 * log
	 *
	 * Static method for loggin from whereever, without taking care if is a table or whatever.
	 * Useful for try catch exceptions
	 *
	 */
	static protected function logZend($caller, $message, $level, $module = NULL) {
		$time0 = time() + microtime(true);
		if (defined('BR_APP_LOG_MAX_LEVEL') && is_numeric(BR_APP_LOG_MAX_LEVEL) && BR_APP_LOG_MAX_LEVEL < $level) {
			return true;
		}

		$logger = new Zend_Log();

		$m = trim($module);
		if ($m) {
			$file = $m;
		} else {
			$file = 'app.log';
		}

		$logdir = static::LOG_DIRECTORY . DIRECTORY_SEPARATOR . $file;


		try {
			$writter = new Zend_Log_Writer_Stream($logdir);
			$logger->addWriter($writter);


			$format    = '[ %timestamp% ] caller: %caller% [ %priorityName% ] [ %requestUri% ] from: [ %visitorIp% ]:' . PHP_EOL . ' %message%' . PHP_EOL;
			$formatter = new Zend_Log_Formatter_Simple($format);
			$writter->setFormatter($formatter);

			$string = '';

			if (isset($_SERVER['HTTP_REFERER'])) {
				$string .= ' Referer: ' . $_SERVER['HTTP_REFERER'] . ' ';
			}

			$string .= $message;

			$logger->setEventItem('caller', $caller);
			$logger->setEventItem('requestUri', $_SERVER['REQUEST_URI']);
			$logger->setEventItem('visitorIp', $_SERVER['REMOTE_ADDR']);
			$logger->setEventItem('timestamp', date('d-m-Y H:i:s', time()));

			$logger->log($string, $level);

			return true;
		} catch (Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}


	/**
	 * log
	 *
	 * Static method for loggin from whereever, without taking care if is a table or whatever.
	 * Useful for try catch exceptions
	 *
	 */
	static protected function logCustom($caller, $message, $level, $module = NULL) {
		if (defined('BR_APP_LOG_MAX_LEVEL') && is_numeric(BR_APP_LOG_MAX_LEVEL) && BR_APP_LOG_MAX_LEVEL < $level) {
			return true;
		}

		$m = trim($module);
		if ($m) {
			$file = $m;
		} else {
			$file = static::MODULE_DEFAULT;
		}

		$file_url = static::LOG_DIRECTORY . DIRECTORY_SEPARATOR . $file;

		$pns = array(self::EMERG => 'EMERG',
					 self::ALERT => 'ALERT',
					 self::CRIT => 'CRIT',
					 self::ERR => 'ERR',
					 self::WARN => 'WARN',
					 self::NOTICE => 'NOTICE',
					 self::INFO => 'INFO',
					 self::DEBUG => 'DEBUG');

		$string = '';

		if (isset($_SERVER['HTTP_REFERER'])) {
			$string .= ' Referer: ' . $_SERVER['HTTP_REFERER'] . ' ';
		}

		$string .= $message;

		$requestUri = $_SERVER['REQUEST_URI'];
		$visitorIp  = $_SERVER['REMOTE_ADDR'];
		$timestamp  = date('d-m-Y H:i:s', time()) . ' UTC';

		$priorityName = array_key_exists($level, $pns) ? $pns[$level] : $level;

		$logstring = "[ $timestamp ] caller: $caller [ $priorityName ] [ $requestUri ] from: [$visitorIp ]:" . PHP_EOL . " $string" . PHP_EOL;

		try {
			$file_handler = fopen($file_url, 'a+');
			fwrite($file_handler, $logstring);
			fclose($file_handler);

			return true;
		} catch (Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}

}
