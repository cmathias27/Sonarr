<?php

use GuzzleHttp\Client;

class sonarrApi
{
    protected $url;
    protected $apiKey;
    protected $httpAuthUsername;
    protected $httpAuthPassword;

    public function __construct($url, $apiKey, $httpAuthUsername = null, $httpAuthPassword = null)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
        $this->httpAuthUsername = $httpAuthUsername;
        $this->httpAuthPassword = $httpAuthPassword;
    }

    /**
     * Gets upcoming episodes, if start/end are not supplied episodes airing today and tomorrow will be returned
     * When supplying start and/or end date you must supply date in format yyyy-mm-dd
     * Example: $sonarr->getCalendar('2015-01-25', '2016-01-15');
     * 'start' and 'end' not required. You may supply, one or both.
     *
     * @param string|null $start
     * @param string|null $end
     * @return array|object|string
     */
    public function getCalendar($start = null, $end = null)
    {
        $uriData = [];

        if ( $start ) {
            if ( $this->validateDate($start) ) {
                $uriData['start'] = $start;
            } else {
                echo json_encode(array(
                    'error' => array(
                        'msg' => 'Start date string was not recognized as a valid DateTime. Format must be yyyy-mm-dd.',
                        'code' => 400,
                    ),
                ));

                exit();
            }
        }
        if ( $end ) {
            if ( $this->validateDate($end) ) {
                $uriData['end'] = $end;
            } else {
                echo json_encode(array(
                    'error' => array(
                        'msg' => 'End date string was not recognized as a valid DateTime. Format must be yyyy-mm-dd.',
                        'code' => 400,
                    ),
                ));

                exit();
            }
        }

        $response = [
            'uri' => 'calendar',
            'type' => 'get',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

    /**
     * Queries the status of a previously started command, or all currently started commands.
     *
     * @param null $id Unique ID of the command
     * @return array|object|string
     */
    public function getCommand($id = null)
    {
        $uri = ($id) ? 'command/' . $id : 'command';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Publish a new command for Sonarr to run.
     * These commands are executed asynchronously; use GET to retrieve the current status.
     *
     * Commands and their parameters can be found here:
     * https://github.com/Sonarr/Sonarr/wiki/Command#commands
     *
     * @param $name
     * @param array|null $params
     * @return string
     */
    public function postCommand($name, array $params = null)
    {
        $uri = 'command';
        $uriData = [
            'name' => $name
        ];

        if (is_array($params)) {
        	foreach($params as $key=>$value) {
        		$uriData[$key] = $value;
	        }
        }

        $response = [
            'uri' => $uri,
            'type' => 'post',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

    /**
     * Gets Diskspace
     *
     * @return array|object|string
     */
    public function getDiskspace()
    {
        $uri = 'diskspace';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Returns all episodes for the given series
     *
     * @param $seriesId
     * @return array|object|string
     */
    public function getEpisodes($seriesId)
    {
        $uri = 'episode';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => [
                'SeriesId' => $seriesId
            ]
        ];

        return $this->processRequest($response);
    }

    /**
     * Returns the episode with the matching id
     *
     * @param $id
     * @return string
     */
    public function getEpisode($id)
    {
        $uri = 'episode';

        $response = [
            'uri' => $uri . '/' . $id,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Update the given episodes, currently only monitored is changed, all other modifications are ignored.
     *
     * Required: All parameters; You should perform a getEpisode(id)
     * and submit the full body with the changes, as other values may be editable in the future.
     *
     * @param array $data
     * @return string
     */
    public function updateEpisode(array $data)
    {
        $uri = 'episode';

        $response = [
            'uri' => $uri,
            'type' => 'put',
            'data' => $data
        ];

        return $this->processRequest($response);
    }

    /**
     * Returns all episode files for the given series
     *
     * @param $seriesId
     * @return array|object|string
     */
    public function getEpisodeFiles($seriesId)
    {
        $uri = 'episodefile';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => [
                'SeriesId' => $seriesId
            ]
        ];

        return $this->processRequest($response);
    }

    /**
     * Returns the episode file with the matching id
     *
     * @param $id
     * @return string
     */
    public function getEpisodeFile($id)
    {
        $uri = 'episodefile';

        $response = [
            'uri' => $uri . '/' . $id,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Delete the given episode file
     *
     * @param $id
     * @return string
     */
    public function deleteEpisodeFile($id)
    {
        $uri = 'episodefile';

        $response = [
            'uri' => $uri . '/' . $id,
            'type' => 'delete',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Gets history (grabs/failures/completed).
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'series.title' or 'date'
     * @param string $sortDir 'asc' or 'desc'
     * @return array|object|string
     */
    public function getHistory($page = 1, $pageSize = 10, $sortKey = 'series.title', $sortDir = 'asc')
    {
        $uri = 'history';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'sortKey' => $sortKey,
                'sortDir' => $sortDir
            ]
        ];

        return $this->processRequest($response);
    }

    /**
     * Gets missing episode (episodes without files).
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'series.title' or 'airDateUtc'
     * @param string $sortDir 'asc' or 'desc'
     * @return array|object|string
     */
    public function getWantedMissing($page = 1, $pageSize = 10, $sortKey = 'series.title', $sortDir = 'asc')
    {
        $uri = 'wanted/missing';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'sortKey' => $sortKey,
                'sortDir' => $sortDir
            ]
        ];

        return $this->processRequest($response);
    }

    /**
     * Displays currently downloading info
     *
     * @return array|object|string
     */
    public function getQueue()
    {
        $uri = 'queue';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Gets all quality profiles
     *
     * @return array|object|string
     */
    public function getProfiles()
    {
        $uri = 'profile';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Get release by episode id
     *
     * @param $episodeId
     * @return string
     */
    public function getRelease($episodeId)
    {
        $uri = 'release';
        $uriData = [
            'episodeId' => $episodeId
        ];

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

    /**
     * Adds a previously searched release to the download client,
     * if the release is still in Sonarr's search cache (30 minute cache).
     * If the release is not found in the cache Sonarr will return a 404.
     *
     * @param $guid
     * @return string
     */
    public function postRelease($guid)
    {
        $uri = 'release';
        $uriData = [
            'guid' => $guid
        ];

        $response = [
            'uri' => $uri,
            'type' => 'post',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

    /**
     * Push a release to download client
     *
     * @param $title
     * @param $downloadUrl
     * @param $downloadProtocol (Usenet or Torrent)
     * @param $publishDate (ISO8601 Date)
     * @return string
     */
    public function postReleasePush($title, $downloadUrl, $downloadProtocol, $publishDate)
    {
        $uri = 'release';
        $uriData = [
            'title' => $title,
            'downloadUrl' => $downloadUrl,
            'downloadProtocol' => $downloadProtocol,
            'publishDate' => $publishDate
        ];

        $response = [
            'uri' => $uri,
            'type' => 'post',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

    /**
     * Gets root folder
     *
     * @return array|object|string
     */
    public function getRootFolder()
    {
        $uri = 'rootfolder';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Returns all series in your collection
     *
     * @param int $id (Optional) If specified, fetch the show matching $id.
     *
     * @return array|object|string
     */
    public function getSeries($id=null)
    {
        $uri = 'series';
	    if ($id !== null) $uri.='/'.$id;

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Adds a new series to your collection
     *
     * NOTE: if you do not add the required params, then the series wont function.
     * Some of these without the others can indeed make a "series". But it wont function properly in Sonarr.
     *
     * Required: tvdbId (int) title (string) qualityProfileId (int) titleSlug (string) seasons (array)
     * See GET output for format
     *
     * path (string) - full path to the series on disk or rootFolderPath (string)
     * Full path will be created by combining the rootFolderPath with the series title
     *
     * Optional: tvRageId (int) seasonFolder (bool) monitored (bool)
     *
     * @param array $data
     * @param bool|true $onlyFutureEpisodes It can be used to control which episodes Sonarr monitors
     * after adding the series, setting to true (default) will only monitor future episodes.
     *
     * @return array|object|string
     */
    public function postSeries(array $data)
    {
        $uri = 'series';
        $uriData = [];

        // Required
        $uriData['tvdbId'] = $data['tvdbId'];
        $uriData['title'] = $data['title'];
        $uriData['qualityProfileId'] = $data['qualityProfileId'];

        if ( array_key_exists('titleSlug', $data) ) { $uriData['titleSlug'] = $data['titleSlug']; }
        if ( array_key_exists('seasons', $data) ) { $uriData['seasons'] = $data['seasons']; }
        if ( array_key_exists('path', $data) ) { $uriData['path'] = $data['path']; }
        if ( array_key_exists('rootFolderPath', $data) ) { $uriData['rootFolderPath'] = $data['rootFolderPath']; }
        if ( array_key_exists('tvRageId', $data) ) { $uriData['tvRageId'] = $data['tvRageId']; }
        $uriData['seasonFolder'] = ( array_key_exists('seasonFolder', $data) ) ? $data['seasonFolder'] : true;
        if ( array_key_exists('monitored', $data) ) { $uriData['monitored'] = $data['monitored']; }
        if ( array_key_exists('tags', $data) ) { $uriData['tags'] = $data['tags']; }
        if ( array_key_exists('seriesType', $data) ) { $uriData['seriesType'] = $data['seriesType']; }
        if ( array_key_exists('monitoringType', $data) ) { 
            $uriData['addOptions'] = ['monitor' => $data['monitoringType']];
        }

        $response = [
            'uri' => $uri,
            'type' => 'post',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

	/**
	 * Update an existing series in your collection
	 *
	 * NOTE: if you do not add the required params, you will get an error.
	 * It is recommended to use the result of getSeriesLookup or getSeries($id), and then modify it to create the data array.
	 *
	 * See GET output for format
	 *
	 * @param array $data
	 *
	 * @return array|object|string
	 */
	public function putSeries(array $data)
	{
		if (! array_key_exists("id", $data)) return [
			"error"=>[
				"msg"=>"No episode ID found!"
			]
		];
		$uri = 'series/'.$data['id'];
		$uriData = [];
		$response = [
			'uri' => $uri,
			'type' => 'put',
			'data' => $data
		];
		return $this->processRequest($response);
	}
    /**
     * Delete the series with the given ID
     *
     * @param int $id
     * @param bool|true $deleteFiles
     * @return string
     */
    public function deleteSeries($id, $deleteFiles = true)
    {
        $uri = 'series';
        $uriData = [];
        $uriData['deleteFiles'] = ($deleteFiles) ? 'true' : 'false';

        $response = [
            'uri' => $uri . '/' . $id,
            'type' => 'delete',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

    /**
     * Searches for new shows on trakt
     * Search by name or tvdbid
     * Example: 'The Blacklist' or 'tvdb:266189'
     *
     * @param string $searchTerm query string for the search (Use tvdb:12345 to lookup TVDB ID 12345)
     * @return string
     */
    public function getSeriesLookup($searchTerm)
    {
        $uri = 'series/lookup';
        $uriData = [
            'term' => $searchTerm
        ];

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }

    /**
     * Get System Status
     *
     * @return string
     */
    public function getSystemStatus()
    {
        $uri = 'system/status';

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    public function getTags($id=null)
    {
        $uri = 'tag';
	    if ($id !== null) $uri.='/'.$id;

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => []
        ];

        return $this->processRequest($response);
    }

    /**
     * Process requests with Guzzle
     *
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function _request(array $params)
    {
        $client = new Client();
        $options = [
            'headers' => [
                'X-Api-Key' => $this->apiKey    
            ]    
        ];
        
        if ( $this->httpAuthUsername && $this->httpAuthPassword ) {
            $options['auth'] = [
                $this->httpAuthUsername,
                $this->httpAuthPassword
            ];
        }

        if ( $params['type'] == 'get' ) {
            $url = $this->url . '/api/v3/' . $params['uri'] . '?' . http_build_query($params['data']) . '&includeSeries=true&includeEpisode=true';

            return $client->get($url, $options);
        }

        if ( $params['type'] == 'put' ) {
            $url = $this->url . '/api/v3/' . $params['uri'];
            $options['json'] = $params['data'];
            
            return $client->put($url, $options);
        }

        if ( $params['type'] == 'post' ) {
            $url = $this->url . '/api/v3/' . $params['uri'];
            $options['json'] = $params['data'];
            
            return $client->post($url, $options);
        }

        if ( $params['type'] == 'delete' ) {
            $url = $this->url . '/api/v3/' . $params['uri'] . '?' . http_build_query($params['data']);

            return $client->delete($url, $options);
        }
    }

    /**
     * Process requests, catch exceptions, return json response
     *
     * @param array $request uri, type, data from method
     * @return string json encoded response
     */
    protected function processRequest(array $request)
    {

        try {
            $response = $this->_request(
                [
                    'uri' => $request['uri'],
                    'type' => $request['type'],
                    'data' => $request['data']
                ]
            );
        } catch ( \Exception $e ) {
            return json_encode(array(
                'error' => array(
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                ),
            ));
        }

        return $response->getBody()->getContents();
    }

    /**
     * Verify date is in proper format
     *
     * @param $date
     * @param string $format
     * @return bool
     */
    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
