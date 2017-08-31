<?php

class Tienda extends Service
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
		$items = $connection->deepQuery("SELECT * FROM _tienda_post WHERE id = '{$request->query}'");

		// return error email if no items were found
		if(count($items) == 0){
			$response = new Response();
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
	 * Publishes a new item
	 *
	 * @author kuma
	 * @param Request
	 * @return Response
	 */
	public function _publicar(Request $request)
	{
		$title = str_replace("'"," ",$request->query);
		$desc = str_replace("'"," ",$request->body);

		$title = substr(trim($title), 0, 100);
		$desc = substr(trim($desc), 0, 1000);

		if ($title == '') $title = substr($desc, 0, 100);

		$prices = $this->getPricesFrom($title.' '.$desc);
		$price = '0';
		$currency = 'CUC';

		if (isset($prices[0]))
		{
			$price = $prices[0]['value'];
			$currency = $prices[0]['currency'];
		}

		$hash = $this->utils->generateRandomHash();
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		$number_of_pictures = 0;
		foreach($request->attachments as $at)
		{
			if (isset($at->type))
			{
				if (strpos("jpg,jpeg,image/jpg,image/jpeg,image/png,png,image/gif,gif",$at->type)!==false)
				{
					if (isset($at->path))
					{
						// save the image
						$number_of_pictures++;
						$img = file_get_contents($at->path);
						$filePath = "$wwwroot/public/tienda/".md5($hash)."_$number_of_pictures.jpg";
						file_put_contents($filePath, $img);

						// optimize the image
						$this->utils->optimizeImage($filePath);
					}
				}
			}
		}

		$owner = $request->email;
		$date = new DateTime(date('Y-m-d'));
		$date->modify('+30 days');
		$expire = $date->format('Y-m-d')." 00:00:00";
		$category = $this->classify($title.' '.$desc);
		$contact_name = explode('@', $owner)[0];

		$db = new Connection();
		$db->deepQuery("INSERT INTO _tienda_post (ad_title,ad_body,contact_email_1,price,currency,contact_name,category,number_of_pictures,source_url) VALUES ('$title','$desc','$owner',$price,'$currency','$contact_name','$category',$number_of_pictures,'$hash');");

		$response = new Response();
		$response->setResponseSubject('Su anuncio a sido publicado');
		$response->createFromText('Su anuncio <b>"'.$title.'"</b> ha sido publicado satisfactoriamente.');

		return $response;
	}

	/**
	 * Get a category for the post
	 *
	 * @author kuma
	 * @param String, title and body concatenated
	 * @return String category
	 */
	private function classify($text)
	{
		$map = array(
		    'computers' => 'laptop,pc,computadora,kit,mouse,teclado,usb,flash,memoria,sd,ram,micro,tarjeta de video,tarjeta de sonido,motherboard,display,impresora',
			'cars' => 'carro,auto,moto,bicicleta',
			'electronics' => 'equipo,ventilador,acondicionado,aire,televisor,tv,radio,musica,teatro en casa,bocina',
			'events' => 'evento,fiesta,concierto',
			'home' => 'cubiertos,mesa,muebles,silla,escaparate,cocina',
			'jobs' => 'trabajo,contrato,profesional',
			'phones' => 'bateria,celular,iphone,blu,android,ios,cell,rooteo,root,jailbreak,samsung galaxy,blackberry,sony erickson',
			'music_instruments' => 'guitarra,piano,trompeta,bajo,bateria',
			'places' => 'restaurant,bar,cibercafe,club',
			'software' => 'software,programa,juego de pc,juegos,instalador,mapa',
			'real_state' => 'casa,vivienda,permuto,apartamento,apto',
			'relationship' => 'pareja,amigo,novia,novio,singler',
			'services' => 'servicio,reparador,reparan,raparacion,taller,a domicilio,mensajero,taxi',
			'videogames' => 'nintendo,wii,playstation,ps2,xbox',
			'antiques' => 'colleci,antig,moneda,sello,carta,tarjeta',
			'books' => 'libro,revista,biblio',
			'for_sale' => 'venta,vendo,ganga'
		);

		foreach($map as $class => $kws)
		{
			$kws = explode(',',$kws);
			foreach($kws as $kw)
			{
				if (stripos($text,' '.$kw)!==false || stripos($text,' '.$kw)===0)
				{
					return $class;
				}
			}
		}

		return 'for_sale';
	}

	/**
	* Extract prices from a text
	*
	* @author kuma
	* @param string $text
	* @return array
	*/
	private function getPricesFrom($text)
	{
		// trying the cases 17.000 20.500 etc... 20 000
		for ($i = 0; $i < 10; $i++)
		{
			$text = str_replace(".{$i}00", "{$i}00", $text);
			$text = str_replace(",{$i}00", "{$i}00", $text);
			$text = str_replace(" {$i}00", "{$i}00", $text);
		}

		preg_match_all('/(\$?([0-9]+)\.?(\d{0,2}))\s*(pesos)?\s*(cuc)?\s*(mn)?\s*(cup)?/xi', $text, $matches);

		$prices = array();

		foreach ($matches [0] as $price)
		{
			$price = trim($price);
			if ( ! is_numeric($price))
			{
				if (stripos($price, "cuc") || stripos($price, "\$")) $m = "CUC";
				elseif (stripos($price, "peso") || stripos($price, "mn") || stripos($price, "cup")) $m = "CUP";
				else continue;

				$prices [] = array(
					"value" => trim(str_ireplace(array('$','cuc','pesos','peso','cup','mn'), '', $price)),
					"currency" => $m
				);
			}
		}

		return $prices;
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
			$response->setResponseSubject("Que desea hacer en la tienda?");
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
		$response->setResponseSubject("La busqueda que usted pidio");
		$response->createFromTemplate($template, $responseContent, $images);
		return $response;
	}

	/**
	 * Search in the database for the most similar results
	 *
	 */
	private function search($query, $limit){
		// get the count and data
		$connection = new Connection();

		$words = array();
		foreach(explode(" ", $query) as $word)
		{
			// do not process ignored words
			$isNegativeWord = $connection->deepQuery("SELECT word FROM _search_ignored_words WHERE word='$word'");
			if( ! empty($isNegativeWord)) { $words[] = "~$word"; continue; }

			// calculate how many permutations are needed to be considered a typo
			$typoMargin = floor(strlen($word)/5);
			if($typoMargin==0) $typoMargin = 1;

			// check if the word is a typo and add it to the list
			$correctWord = $connection->deepQuery("SELECT word FROM _search_words WHERE word<>'$word' AND levenshtein(word, '$word')<$typoMargin LIMIT 1");
			if( ! empty($correctWord))
			{
				$correctWord = $correctWord[0]->word;
				$connection->deepQuery("INSERT IGNORE INTO _search_variations VALUES ('$correctWord','$word','TYPO')");
				$word = $correctWord;
			}

			// save each word to the database, update the count if the word was already saved
			$words[] = $word;
			$connection->deepQuery("INSERT IGNORE INTO _search_words(word) VALUES ('$word');
				UPDATE _search_words SET count=count+1, last_usage=CURRENT_TIMESTAMP WHERE word='$word'");

			// add the list of all synonyms and typos to the expression
			$variations = $connection->deepQuery("SELECT variation FROM _search_variations WHERE word='$word'");
			foreach ($variations as $variation) $words[] = $variation->variation;
		}

		// create the new, enhanced phrase to search
		$enhancedQuery = implode(" ", $words);

		// search for all the results based on the query created
		$sql = "SELECT *, 0 as popularity
			FROM _tienda_post
			WHERE MATCH (ad_title) AGAINST ('$enhancedQuery' IN BOOLEAN MODE) > 0
			AND DATEDIFF(NOW(), COALESCE(date_time_posted, NOW())) <=30
			GROUP BY ad_title
			HAVING COUNT(ad_title) = 1";

		$results = $connection->deepQuery($sql);

		// get every search term and its strength
		$sql = "SELECT * FROM _search_words WHERE word in ('" . implode("','", $words) . "')";
		$terms = $connection->deepQuery($sql);

		// assign popularity based on other factors
		foreach($results as $result)
		{
			// restart populatiry
			$popularity = 0;

			// popularity based on the post's age
			$datediff = time() - strtotime($result->date_time_posted);
			$popularity = 100 - floor($datediff/(60*60*24));

			// popularity based on strong words in the title and body
			// ensures keywords with more searches show always first
			foreach($terms as $term)
			{
				if (stripos($result->ad_title, $term->word) !== false)
					$popularity += 50 + ceil($term->count/10);

				if (stripos($result->ad_body, $term->word) !== false)
					$popularity += 25 + ceil($term->count/10);
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
