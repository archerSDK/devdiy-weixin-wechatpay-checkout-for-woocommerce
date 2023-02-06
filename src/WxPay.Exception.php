<?php
if (!defined('ABSPATH'))
	exit;

class WechatPaymentException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
