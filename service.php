<?php

class Revolico extends Service
{
	/**
	 * Function called once this service is called
	 *
	 * @param Request
	 * @return Response
	 */
	public function _main(Request $request)
	{
		return $this->getResponseBasedOnNumberOfResults($request, 10);
	}

	/**
	 * Same as the _main function
	 *
	 * @param Request
	 * @return Response
	 */
	public function _buscar(Request $request)
	{
		return $this->getResponseBasedOnNumberOfResults($request, 10);
	}

	/**
	 * Search 100 items
	 *
	 * @param Request
	 * @return Response
	 */
	public function _buscartodo(Request $request)
	{
		return $this->getResponseBasedOnNumberOfResults($request, 100);
	}

	/**
	 * View only one item based on the id
	 *
	 * @param Request
	 * @return Response
	 */
	public function _ver(Request $request)
	{
		// get the item to look up
		$connection = new Connection();
		$items = $connection->query("SELECT * FROM _tienda_post WHERE id = '{$request->query}'");

		// return error email if no items were found
		if(count($items) == 0){
			$response = new Response();
			$response->setCache();
			$response->setResponseSubject("El producto o servicio no existe");
			$response->createFromText("El producto o servicio que usted intenta buscar no existe. Puede que el n&uacute;mero sea incorrecto o que el vendedor lo halla sacado del sistema.");
			return $response;
		}

		// get the first one, thta is the item
		$item = $items[0];

		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get the images to embeb into the email
		$images = array();
		if($item->number_of_pictures > 0)
		{
			for($i=1; $i<=$item->number_of_pictures; $i++)
			{
				$file = "$wwwroot/public/tienda/".md5($item->source_url)."_$i.jpg";
				if(file_exists($file)) $images[] = $file;
			}
		}

		// display the results in the template
		$response = new Response();
		$response->setCache();
		$response->setResponseSubject("El articulo que pidio ver");
		$responseContent = array("item" => $item, "wwwroot" => $wwwroot);
		$response->createFromTemplate("ver.tpl", $responseContent, $images);
		return $response;
	}

	/**
	 * Search and return based on number of results
	 *
	 * @author salvipascual
	 */
	private function getResponseBasedOnNumberOfResults(Request $request, $numberOfResults)
	{
		// if the search is empty, return a message to the user
		if(empty($request->query))
		{
			$response = new Response();
			$response->setCache();
			$response->setResponseSubject("Que desea comprar?");
			$response->createFromTemplate("home.tpl", array());
			return $response;
		}

		// search for the results of the query
		$searchResult = $this->search($request->query, $numberOfResults);
		$count = $searchResult['count'];
		$items = $searchResult['items'];

		// return error to the user if no items were found
		if(count($items) == 0)
		{
			$response = new Response();
			$response->setCache("day");
			$response->setResponseSubject("Su busqueda no produjo resultados");
			$response->createFromText("Su b&uacute;squeda '{$request->query}' no produjo ning&uacute;n resultado. Por favor utilice otra frase de b&uacute;squeda e intente nuevamente.");
			return $response;
		}

		// get the path
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// check if is a buscar or buscartodo
		$isSimpleSearch = $numberOfResults<=10;

		// clean the text and save the images
		$images = array();
		foreach ($items as $item)
		{
			// clean the text
			$item->ad_title = $this->clean($item->ad_title);
			$item->ad_body = $this->clean($item->ad_body);

			// save images if 10 results
			if($isSimpleSearch)
			{
				if($item->number_of_pictures == 0) continue;
				$file = "$wwwroot/public/tienda/".md5($item->source_url)."_1.jpg";
				if(file_exists($file)) $images[] = $file;
			}
		}

		// select template
		$template = $isSimpleSearch ? "buscar.tpl" : "buscartodo.tpl";

		// send variables to the template
		$responseContent = array(
			"numberOfDisplayedResults" => $isSimpleSearch ? (count($items) > 10 ? 10 : count($items)) : count($items),
			"numberOfTotalResults" => $count,
			"searchQuery" => $request->query,
			"items" => $items,
			"wwwroot" => $wwwroot
		);

		// display the results in the template
		$response = new Response();
		$response->setCache("day");
		$response->setResponseSubject("La busqueda que usted pidio");
		$response->createFromTemplate($template, $responseContent, $images);
		return $response;
	}

	/**
	 * Search in the database for the most similar results
	 */
	private function search($query, $limit = 100)
	{
		// get the count and data
		$connection = new Connection();
		$words = explode(" ", $query);
		$enhancedQuery = str_replace("'","",$query);

		Connection::query("DELETE FROM _tienda_post WHERE date_time_posted IS NULL;");
        Connection::query("DELETE FROM _tienda_post WHERE DATEDIFF(NOW(), date_time_posted, NOW()) > 30;");

        if (empty("$limit")) $limit = 100;

        // search for all the results based on the query created
		$results = $connection->query("
			SELECT * FROM (
			  SELECT *, '0' as popularity, 
			      MATCH (ad_title) AGAINST ('$enhancedQuery' IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION) as score
			  FROM _tienda_post
			  WHERE  score > 0
			  ORDER BY score DESC
			  LIMIT 0, $limit
			  ) as subq
            WHERE score > 0");

		// get every search term and its strength
		$sql = "SELECT * FROM _tienda_words WHERE word in ('" . implode("','", $words) . "')";
		$terms = $connection->query($sql);

		// assign popularity based on other factors
		foreach($results as $result)
		{
			// restart populatiry
			$popularity = 0;

			// do not show email desconocido@apretaste.com
			if($result->contact_email_1 == 'desconocido@apretaste.com') $result->contact_email_1 = "";
			if($result->contact_email_2 == 'desconocido@apretaste.com') $result->contact_email_2 = "";
			if($result->contact_email_3 == 'desconocido@apretaste.com') $result->contact_email_3 = "";

			// popularity based on the post's age
			$datediff = time() - strtotime($result->date_time_posted);
			$popularity = 100 - floor($datediff/(60*60*24));

			// popularity based on strong words in the title and body
			// ensures keywords with more searches show always first
			foreach($terms as $term)
			{
				if (stripos($result->ad_title, $term->word) !== false) $popularity += 50 + ceil($term->count/10);
				if (stripos($result->ad_body, $term->word) !== false) $popularity += 25 + ceil($term->count/10);
			}

			// popularity based on image and contact info
			if($result->number_of_pictures > 0) $popularity += 50;
			if($result->contact_email_1) $popularity += 20;
			if($result->contact_email_2) $popularity += 20;
			if($result->contact_email_3) $popularity += 20;
			if($result->contact_phone) $popularity += 20;
			if($result->contact_cellphone) $popularity += 20;

			// popularity when the query fully match the the title or body
			if(strpos($result->ad_title, $query) !== false) $popularity += 100;
			if(strpos($result->ad_body, $query) !== false) $popularity += 50;

			// popularity when the query partially match the the title or body
			$words = explode(" ", $query);
			if(count($words)>2)
			{
				for($i=0; $i<count($words)-2; $i++)
				{
					// read words from right to left
					$phraseR = implode(" ", array_slice($words, 0, $i+2)) . " ";
					if(strpos($result->ad_title, $phraseR) !== false) $popularity += 20 * count($phraseR);
					if(strpos($result->ad_body, $phraseR) !== false) $popularity += 10 * count($phraseR);

					// read words from left to right
					$phraseL = " " . implode(" ", array_slice($words, $i+1, count($words)));
					if(strpos($result->ad_title, $phraseL) !== false) $popularity += 20 * count($phraseL);
					if(strpos($result->ad_body, $phraseL) !== false) $popularity += 10 * count($phraseL);
				}
			}

			// popularity based on location
			// TODO set popularity based on location

			// assign new popularity
			$result->popularity = $popularity;
		}

		// sort the results based on popularity
		usort($results, function ($a, $b) {
			if ($a->popularity == $b->popularity) return 0;
			return ($a->popularity > $b->popularity) ? -1 : 1;
		});

		// get only the first X elements depending $limit
		$resultsToDisplay = count($results)>$limit ? array_slice($results,0,$limit) : $results;

		// return an array with the count and the data
		return array(
			"count" => count($results),
			"items" => $resultsToDisplay
		);
	}

	/**
	 * Clean a text to erase weird characters and make it look nicer
	 *
	 * @author salvipascual
	 */
	private function clean($text)
	{
		// erase weird symbols
		$text = preg_replace('/[^A-Za-z0-9\-\s\.]/', ' ', $text);
		// erase double spaces
		$text = preg_replace('/\s+/', ' ', $text);
		// remove more than one dots together
		$text = preg_replace('/\.{2,}/', '', $text);
		// Do not accept all capitals
		$text = $this->fixSentenceCase($text);
		// erase spaces at the beggining and end
		return trim($text);
	}

	/**
	 * Fix the case of the sentense to look properly
	 *
	 * @author taken from the internet, updated by salvipascual
	 */
	private function fixSentenceCase($str)
	{
		$cap = true;
		$ret = '';

		for($x = 0; $x < strlen($str); $x++)
		{
			$letter = strtolower(substr($str, $x, 1));
			if($letter == "." || $letter == "!" || $letter == "?")
			{
				$cap = true;
			}
			elseif($letter != " " && $cap == true)
			{
				$letter = strtoupper($letter);
				$cap = false;
			}
			$ret .= $letter;
		}
		return $ret;
	}
}
