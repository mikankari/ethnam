<?php
// vim: foldmethod=marker
/**
 *	Ethna_LogWriter.php
 *
 *	@author		Masaki Fujimoto <fujimoto@php.net>
 *	@license	http://www.opensource.org/licenses/bsd-license.php The BSD License
 *	@package	Ethna
 *	@version	$Id$
 */

// {{{ Ethna_LogWriter
/**
 *	�������ϴ��쥯�饹
 *
 *	@author		Masaki Fujimoto <fujimoto@php.net>
 *	@access		public
 *	@package	Ethna
 */
class Ethna_LogWriter
{
	/**#@+
	 *	@access	private
	 */

	/**	@var	string	���������ǥ�ƥ��ƥ�ʸ���� */
	var	$ident;

	/**	@var	int		�����ե�����ƥ� */
	var	$facility;

	/**	@var	int		�������ץ���� */
	var	$option;

	/**	@var	string	�����ե����� */
	var	$file;

	/**	@var	bool	�Хå��ȥ졼����������ǽ���ɤ��� */
	var	$have_backtrace;

	/**	@var	array	������٥�̾�ơ��֥� */
	var	$level_name_table = array(
		LOG_EMERG	=> 'EMERG',
		LOG_ALERT	=> 'ALERT',
		LOG_CRIT	=> 'CRIT',
		LOG_ERR		=> 'ERR',
		LOG_WARNING	=> 'WARNING',
		LOG_NOTICE	=> 'NOTICE',
		LOG_INFO	=> 'INFO',
		LOG_DEBUG	=> 'DEBUG',
	);

	/**#@-*/

	/**
	 *	Ethna_LogWriter���饹�Υ��󥹥ȥ饯��
	 *
	 *	@access	public
	 *	@param	string	$log_ident		���������ǥ�ƥ��ƥ�ʸ����(�ץ�����̾��)
	 *	@param	int		$log_facility	�����ե�����ƥ�
	 *	@param	string	$log_file		����������ե�����̾(LOG_FILE���ץ���󤬻��ꤵ��Ƥ�����Τ�)
	 *	@param	int		$log_option		�������ץ����(LOG_FILE,LOG_FUNCTION...)
	 */
	function Ethna_LogWriter($log_ident, $log_facility, $log_file, $log_option)
	{
		$this->ident = $log_ident;
		$this->facility = $log_facility;
		$this->option = $log_option;
		$this->file = $log_file;
		$this->have_backtrace = function_exists('debug_backtrace');
	}

	/**
	 *	�������Ϥ򳫻Ϥ���
	 *
	 *	@access	public
	 */
	function begin()
	{
	}

	/**
	 *	��������Ϥ���
	 *
	 *	@access	public
	 *	@param	int		$level		������٥�(LOG_DEBUG, LOG_NOTICE...)
	 *	@param	string	$message	������å�����(+����)
	 */
	function log($level, $message)
	{
		$c =& Ethna_Controller::getInstance();

		$prefix = $this->ident;
		if ($this->option & LOG_PID) {
			$prefix .= sprintf('[%d]', getmypid());
		}
		$prefix .= sprintf($c->getCLI() ? '(%s): ' : '(<b>%s</b>): ',
			$this->_getLogLevelName($level)
		);
		if ($this->option & (LOG_FUNCTION | LOG_POS)) {
			$tmp = "";
			$bt = $this->_getBacktrace();
			if ($bt && ($this->option & LOG_FUNCTION) && $bt['function']) {
				$tmp .= $bt['function'];
			}
			if ($bt && ($this->option & LOG_POS) && $bt['pos']) {
				$tmp .= $tmp ? sprintf('(%s)', $bt['pos']) : $bt['pos'];
			}
			if ($tmp) {
				$prefix .= $tmp . ": ";
			}
		}

		printf($prefix . $message . "%s\n", $c->getCLI() ? "" : "<br />");

		return $prefix . $message;
	}

	/**
	 *	�������Ϥ�λ����
	 *
	 *	@access	public
	 */
	function end()
	{
	}

	/**
	 *	���������ǥ�ƥ��ƥ�ʸ������������
	 *
	 *	@access	public
	 *	@return	string	���������ǥ�ƥ��ƥ�ʸ����
	 */
	function getIdent()
	{
		return $this->ident;
	}

	/**
	 *	������٥��ɽ��ʸ������Ѵ�����
	 *
	 *	@access	private
	 *	@param	int		$level	������٥�(LOG_DEBUG,LOG_NOTICE...)
	 *	@return	string	������٥�ɽ��ʸ����(LOG_DEBUG��"DEBUG")
	 */
	function _getLogLevelName($level)
	{
		if (isset($this->level_name_table[$level]) == false) {
			return null;
		}
		return $this->level_name_table[$level];
	}

	/**
	 *	�������ϲս�ξ���(�ؿ�̾/�ե�����̾��)���������
	 *
	 *	@access	private
	 *	@return	array	�������ϲս�ξ���
	 */
	function _getBacktrace()
	{
		$skip_method_list = array(
			array('ethna', 'raise.*'),
			array(null, 'raiseerror'),
			array(null, 'handleerror'),
			array('ethna_logger', null),
			array('ethna_logwriter*', null),
			array('ethna_error', null),
			array('ethna_apperror', null),
			array('ethna_actionerror', null),
			array('ethna_backend', 'log'),
			array(null, 'ethna_error_handler'),
			array(null, 'trigger_error'),
		);

		if ($this->have_backtrace == false) {
			return null;
		}

		$bt = debug_backtrace();
		$i = 0;
		while ($i < count($bt)) {
			if (isset($bt[$i]['class']) == false) {
				$bt[$i]['class'] = null;
			}
			$skip = false;

			// �᥽�åɥ����å׽���
			foreach ($skip_method_list as $method) {
				$class = $function = true;
				if ($method[0] != null) {
					$class = preg_match("/$method[0]/i", $bt[$i]['class']);
				}
				if ($method[1] != null) {
					$function = preg_match("/$method[1]/i", $bt[$i]['function']);
				}
				if ($class && $function) {
					$skip = true;
					break;
				}
			}

			if ($skip) {
				$i++;
			} else {
				break;
			}
		}

		$c =& Ethna_Controller::getInstance();
		$basedir = $c->getBasedir();

		$function = sprintf("%s.%s", isset($bt[$i]['class']) ? $bt[$i]['class'] : 'global', $bt[$i]['function']);

		$file = $bt[$i]['file'];
		if (strncmp($file, $basedir, strlen($basedir)) == 0) {
			$file = substr($file, strlen($basedir));
		}
		if (strncmp($file, ETHNA_BASE, strlen(ETHNA_BASE)) == 0) {
			$file = preg_replace('#^/+#', '', substr($file, strlen(ETHNA_BASE)));
		}
		$line = $bt[$i]['line'];
		return array('function' => $function, 'pos' => sprintf('%s:%s', $file, $line));
	}
}
// }}}
?>