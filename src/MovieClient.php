<?php

namespace MightyCode\GoogleMovieClient;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use MightyCode\GoogleMovieClient\Models\DataResponse;
use MightyCode\GoogleMovieClient\Parsers\ShowtimeParser;

class MovieClient implements MovieClientInterface
{

    private $_baseUrl = "http://www.google.com/movies";

    private $_dev_mode = true;

    //current release of chrome. Got user agent string from: http://www.useragentstring.com/pages/Chrome/
    private $_userAgent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36";

    private $http_client;

    public function __construct(){
        $this->constructHttpClient();
    }

    /**
     *  Returns Showtimes for a specific movie near a location
     *
     * @param string $mid
     * @param string $near
     * @param string $lang
     * @param int $date
     * @param int $start
     * @return array
     */
    public function getShowtimesByMovieId($mid, $near, $lang = 'en')
    {
        //http://google.com/movies?near=thun&hl=de&mid=808c5c8cc99039b7

        //TODO: Add multiple result pages parsing

        $dataResponse = $this->getData($near, null, $mid, null, $lang);
        $days = array();
        if ($dataResponse) {
            $dayDate = Carbon::now();
            $dayDate->setTime(0, 0, 0);

            $parser = new ShowtimeParser($dataResponse->getCrawler());
            $result = $parser->getShowtimeDayByMovie($dayDate->copy());
            $days[] = $result;

            for ($i = 1; $i < 20; $i++) {
                $dataResponse = $this->getData($near, null, $mid, null, $lang, $i);

                $parser = new ShowtimeParser($dataResponse->getCrawler());
                $result = $parser->getShowtimeDayByMovie($dayDate->addDay(1)->copy());

                if ($result == null) {
                    break;
                } else {
                    $days[] = $result;
                }
            }

        }
        return $days;
    }

    /**
     * Returns Showtimes for a specific Theater
     *
     * @param string $tid
     * @param string $lang
     * @param int $date
     * @param int $start
     * @return mixed
     */
    public function getShowtimesByTheaterId($tid, $near, $lang = 'en')
    {
        //http://google.com/movies?tid=eef3a3f57d224cf7&hl=de

        //TODO: Add multiple result pages parsing

        $dataResponse = $this->getData($near, null, null, $tid, $lang);
        $days = array();
        if ($dataResponse) {
            $dayDate = Carbon::now();
            $dayDate->setTime(0, 0, 0);

            $parser = new ShowtimeParser($dataResponse->getCrawler());

            $result = $parser->getShowtimeDayByTheater($dayDate);
            $days[] = $result;

            for ($i = 1; $i < 20; $i++) {
                $dataResponse = $this->getData($near, null, null, $tid, $lang, $i);

                $parser = new ShowtimeParser($dataResponse->getCrawler());
                $result = $parser->getShowtimeDayByTheater($dayDate->addDay(1)->copy());

                if ($result == null) {
                    break;
                } else {
                    $days[] = $result;
                }
            }
        }

        return $days;
    }

    /**
     * Returns Theaters near a location
     *
     * @param string $near
     * @param string $lang
     * @param int $date
     * @param int $start
     * @return mixed
     */
    public function getTheatersNear($near, $lang = 'en')
    {
        //http://google.com/movies?near=thun&hl=de
        //http://google.com/movies?near=thun&hl=de&start=10 (next page)

        // TODO: Implement getTheatersNear() method.
    }

    /**
     * Returns Showtimes near a location
     *
     * @param string $near
     * @param string $lang
     * @param int $date
     * @param int $start
     * @return mixed
     */
    public function getShowtimesNear($near, $lang = 'en')
    {
        //http://google.com/movies?near=Interlaken&hl=de
        //http://google.com/movies?near=Interlaken&hl=de&start=10
        // TODO: Implement getShowtimesNear() method.
    }

    /**
     * Returns Showtimes found by a search for a movie title
     *
     * @param string $near
     * @param string $name
     * @param string $lang
     * @param int $date
     * @param int $start
     * @return mixed
     */
    public function queryShowtimesByMovieTitleNear($near, $name, $lang = 'en')
    {
        // http://google.com/movies?near=Thun&hl=de&q=jurassic+world
        // TODO: Implement queryShowtimesByMovieTitleNear() method.
    }

    private function constructHttpClient(){
        $this->http_client = new Client();
        $this->http_client->setDefaultOption('headers', ['User-Agent' => $this->_userAgent]);
    }

    /**
     * get the requested html from google with the passed parameters
     *
     * @param null $near
     * @param null $search
     * @param null $mid
     * @param null $tid
     * @param string $language
     * @param null $date
     * @param int $start
     * @return DataResponse
     */
    private function getData($near = null, $search = null, $mid = null, $tid = null, $language = "en", $date = null, $start = null)
    {
        $params = array(
            'near' => $near,
            'mid' => $mid,
            'tid' => $tid,
            'q' => $search, //Movie title
            'hl' => $language, //en, de, fr...
            'date' => $date,
            'start' => $start
        );

        $response = new DataResponse();
        if ($this->_dev_mode) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/google-movie-client/testdata/movies_' . http_build_query($params) . '.html';
            $response->body = file_get_contents($url);
        } else {
            $url = $this->_baseUrl . '?' . http_build_query($params);
            
            $guzzle_response = $this->http_client->get($url);

            $response->body = $guzzle_response->getBody()->getContents();
            $response->code = $guzzle_response->getStatusCode();
            $response->headers = $guzzle_response->getHeaders();
        }


        return $response;
    }
}