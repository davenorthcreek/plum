<?php
/*
 * FormController.php
 * Controller for interactions with WorldApp form data
 * for transfer of data between WorldApp and Bullhorn
 *
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Controller;

class FormController {

	//allow someone to pass in a $logger
	protected $_logger;

	public function setLogger($lgr) {
		//$lgr better be a logger of some sort -missing real OOP here
		$this->_logger = $lgr;
	}

	function var_debug($object=null) {
		ob_start();                    // start buffer capture
		var_dump( $object );           // dump the values
		$contents = ob_get_contents(); // put the buffer into a variable
		ob_end_clean();                // end capture
		$this->log_debug( $contents ); // log contents of the result of var_dump( $object )
	}


	protected function log_debug($str) {
		if (!is_null($this->_logger)) {
			$e = debug_backtrace(true, 2);
			//$this->_logger->debug(var_dump($e[0]));
			$result = date("Ymd H:i:s");
			$result .= ":";
			$result .= $e[1]["line"];
			$result .= ":";
			$result .= $e[1]['function'];
			$result .= ': '.$str;
			$this->_logger->debug($result);
		} else {  //no logger configured
			echo( $str);
		}
	}

	public $form;

	protected $jsonDecoded;

	public function parse($entityBody) {
		if (substr_compare($entityBody, 'data=%7B', 0)) {
			$entityDecoded = urldecode($entityBody);
		} else {
			$entityDecoded = $entityBody; //already decoded
		}
		//string starts with "data="
		$entity2 = substr($entityDecoded, 5);
		$this->jsonDecoded = json_decode($entity2, true);
		$this->setupForm();
		$formResult = new \Stratum\Model\FormResult();
		$formResult->init($this->jsonDecoded, $this->form);
		$questions = $this->mapQuestions($this->jsonDecoded, $this->form);
		$formResult->set("questions", $questions);
		//$formResult->dump();
		$responses = Storage::disk('local')->put($formResult->get("respondentId")."_decoded.json",
			json_encode($formResult->get("response"), JSON_PRETTY_PRINT));
		return $formResult;
	}

	public function setupForm() {
		$this->form = new \Stratum\Model\Form();
		$this->form->parse_mapping();
		return $this->form;
	}

	public function mapQuestions($jsonDecoded, $form) {
		$questions = [];
		$index = 0;
		foreach($jsonDecoded["response"] as $theQuestion) {
			//echo "Question #".++$index."\n\n";
			//var_dump($theQuestion);
			$question = new \Stratum\Model\Question();
			$question->init($theQuestion, $form);
			//$form = $form->updateMapping($question);
			$questions[] = $question;
		}
		return $questions;
	}


}
