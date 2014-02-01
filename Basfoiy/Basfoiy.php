<?php

/**
 * Basfoiy App
 *
 */

Class Basfoiy
{

	private $config;

	private $urlParam;
	private $db;

	/*
	 * initialize  basfoiy	
	 */
	public function __construct(Array $config)
	{
		$this->config = $config;
		// set database
		$this->db = new Db($this->config['db']);
		// set token
		header('X-Bas-Token: THETOKEN');
		// set url params
		$this->urlParam = $this->parseUrl();
	}

	/*
	 * basfoiy home
	 */
	public function homeAction()
	{
		echo 'home';
	}

	/*
	 * basfoiy search
	 */
	public function searchAction()
	{
		// respond as json
		header('Content-Type: application/json');

		// ignore all requests except needed ones 
		$keyword = $this->urlParam(2);
		// if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $keyword === false || $keyword == ''){
		if ($keyword === false || $keyword == '')
		{
			exit(json_encode(array('error' => true)));
		}

		// query the keyowrd
		$result = $this->db->query(
				'select * from basdata WHERE eng like :word or dhi like :word or latin like :word order by eng limit 5',
				array('word' => '%' . $keyword . '%')
			);

		// if no results are found
		if ($result === false && $this->config['findSimilar'])
		{
			// check for similar words in english
			$result = $this->db->query(
				'select eng from basdata where levenshtein(:word,eng) < 3 order by levenshtein(:word,eng) asc limit 1',
				array('word' => $keyword)
			);
			// if still not found
			if ($result === false) 
			{
				// query for similar words in latin
				$result = $this->db->query(
					'select eng from basdata where levenshtein(:word,latin) < 3 order by levenshtein(:word,latin) asc limit 1',
					array('word' => $keyword)
				);
			}
			// update keyword with any similar words found
			if (property_exists($result[0],'eng'))
			{
				$keyword = $result[0]->eng;
			} 
			else 
			{
				$keyword = $result[0]->latin;
			}
		}

		// query one more time
		$result = $this->db->query(
				'select * from basdata WHERE eng like :word or dhi like :word or latin like :word order by eng limit 5',
				array('word' => '%' . $keyword . '%')
			);

		// give up!
		if ($result !== false)
		{
			echo json_encode($result);
		} 
		else
		{
			echo json_encode(array('error' => true));
		}

	}

	/*
	 * return the parsed urlParams
	 */
	public function urlParam($index = 1)
	{
		$index = $index - 1;
		return isset($this->urlParam[$index]) ? $this->urlParam[$index] : false;
	}

	/*
	 * parse the current url
	 */
	private function parseUrl()
	{
		// identify sub directory
		$subdir = explode('index.php',$_SERVER['PHP_SELF']);
		$subdir = isset($subdir[0]) ? $subdir[0] : '';
		$subdir = ($subdir == '/') ? '' : $subdir;
		// prepare url params
		$urlParams = str_replace($subdir,'',str_replace('index.php','',$_SERVER['REQUEST_URI']));
		$urlParams = explode('/',$urlParams);
		// eliminate empty values
		foreach ($urlParams as $key => $value) {
			if ($value == '') unset($urlParams[$key]);
		}
		// reorder array
		return array_values($urlParams);
	}

}

require_once 'Db.php';