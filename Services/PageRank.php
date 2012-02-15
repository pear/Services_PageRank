<?php
/**
 * PageRank Lookup (Based on Google Toolbar for Mozilla Firefox)
 *
 * Generates the CheckHash (ch) and lookups up the URL to parse the PageRank from Google.
 *
 * PHP version 5
 *
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * + Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * + Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 * + Neither the name of the <ORGANIZATION> nor the names of its contributors
 * may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Services
 * @package   Services_PageRank
 * @author    James Wade <hm2k@php.net>
 * @copyright 2011 James Wade
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   GIT: $Id:$
 * @link      http://pagerank.phurix.net/
 */
/**
 * A class for the HTTP_Request2 object
 */
require_once 'HTTP/Request2.php';
/**
 * Exception class for Services_PageRank package
 */
require_once 'Services/PageRank/Exception.php';
/**
 * Services_PageRank Class
 *
 * @category  Services
 * @package   Services_PageRank
 * @author    James Wade <hm2k@php.net>
 * @copyright 2011 James Wade
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pagerank.phurix.net/
 */
class Services_PageRank implements SplSubject
{
    /**
     * Default PageRank Toolbar Lookup URL
     * @var string
     */
    protected $url = 'http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=%s&features=Rank&q=info:%s';
    /**
     * Query string input for the lookup URL (eg: example.com)
     *
     * @var string
     */
    protected $q;
    /**
     * CheckHash generated
     *
     * @var string
     */
    protected $ch;
    /**
     * Data fetched
     *
     * @var string
     */
    protected $data;
    /**
     * PageRank parsed
     *
     * @var string
     */
    protected $pagerank;
    /**
     * A HTTP request instance
     *
     * @var HTTP_Request2 $request
     */
    public $request = null;
   /**
    * Observers attached to the request (instances of SplObserver)
    * @var  array
    */
    protected $observers = array();
   /**
    * Last event in request / response handling, intended for observers
    * @var  array
    * @see  getLastEvent()
    */
    protected $lastEvent = array(
        'name' => 'start',
        'data' => null
    );
    /**
     * Constructor
     *
     * @param string        $query   Set the query string (eg: example.com)
     * @param HTTP_Request2 $request Provide your HTTP request instance
     */
    public function __construct($query = false, HTTP_Request2 $request = null)
    {
        $this->setRequest($request);
        if ($query) {
            return $this->query($query, $request);
        }
    }
    /**
     * Query Google for PageRank
     *
     * @param string $query Set the query string (eg: example.com)
     *
     * @return string The parsed PageRank value
     */
    public function query($query)
    {
        $this->setQuery($query);
        $this->checkHash();
        $this->lookup();
        $this->parse();
        return $this->getPagerank();
    }
    /**
     * Sets the query string for the lookup (eg: example.com)
     *
     * @param string $string The query
     *
     * @return Services_PageRank
     *
     * @throws Services_PageRank_Exception
     */
    public function setQuery($string)
    {
        if (empty($string)) {
            throw new Services_PageRank_Exception(
                'setQuery() does not expect parameter 1 to be empty',
                Services_PageRank_Exception::USER_INPUT
            );
        }
        if (!is_string($string)) {
            throw new Services_PageRank_Exception(
                'setQuery() expects parameter 1 to be string, ' .
                gettype($string) . ' given',
                Services_PageRank_Exception::USER_INPUT
            );
        }
        $this->q=$string;
        $this->setLastEvent('setQuery', $string);
        return $this;
    }
    /**
     * Sets the request object
     *
     * @param HTTP_Request2 $object A HTTP request instance (otherwise one will be created)
     *
     * @return Services_PageRank
     */
    public function setRequest(HTTP_Request2 $object = null)
    {
        $this->request = ($object instanceof HTTP_Request2) ? $object : new HTTP_Request2();
        return $this;
    }
    /**
     * Checks if there is a CheckHash value or not
     *
     * @return bool
     */
    public function hasCheckhash()
    {
        return !empty($this->ch);
    }
    /**
     * Sets the CheckHash
     *
     * @param string $string The CheckHash
     *
     * @return Services_PageRank
     *
     * @throws Services_PageRank_Exception
     */
    public function setCheckhash($string = '')
    {
        if (empty($string)) {
            throw new Services_PageRank_Exception(
                'setCheckhash() does not expect parameter 1 to be empty',
                Services_PageRank_Exception::USER_INPUT
            );
        }
        if (!is_string($string)) {
            throw new Services_PageRank_Exception(
                'setCheckhash() expects parameter 1 to be string, ' .
                gettype($string) . ' given',
                Services_PageRank_Exception::USER_INPUT
            );
        }
        $this->ch = $string;
        $this->setLastEvent('setCheckhash', $string);
        return $this;
    }
    /**
     * Get the CheckHash
     *
     * @return the CheckHash
     */
    public function getCheckhash()
    {
        if (!$this->hasCheckhash()) {
            $this->checkHash();
        }
        return $this->ch ? $this->ch : '';
    }
    /**
     * Generates the CheckHash
     *
     * @return string The CheckHash
     */
    protected function checkHash ()
    {
        $seed = "Mining PageRank is AGAINST GOOGLE'S TERMS OF SERVICE. Yes, I'm talking to you, scammer.";
        $result = 0x01020345;
        $len = strlen($this->q);
        for ($i = 0; $i < $len; $i++) {
            $result ^= ord($seed{$i%strlen($seed)}) ^ ord($this->q{$i});
            $result = (($result >> 23) & 0x1ff) | $result << 9;
        }
        $ch = sprintf('8%x', $result);
        $this->setCheckhash($ch);
        return $ch;
    }
    /**
     * Builds the PageRank Toolbar Lookup URL with the checkhash and the query
     *
     * @return the URL
     */
    public function getUrl ()
    {
        return sprintf($this->url, $this->getCheckhash(), $this->getQuery());
    }
    /**
     * Return the Query (eg: example.com)
     *
     * @return the Query
     */
    public function getQuery()
    {
        return $this->q ? $this->q : '';
    }
    /**
     * Sets the fetch data
     *
     * @param string $string The fetch data
     *
     * @return Services_PageRank
     */
    public function setData($string = '')
    {
        $this->data = $string;
        $this->setLastEvent('setData', $string);
        return $this;
    }
    /**
     * Get the Data
     *
     * @return the Data
     */
    public function getData()
    {
        return $this->data ? $this->data : '';
    }
    /**
     * Sets the PageRank
     *
     * @param string $string The PageRank
     *
     * @return Services_PageRank
     */
    public function setPagerank($string = '')
    {
        $this->pagerank = $string;
        $this->setLastEvent('setPagerank', $string);
        return $this;
    }
    /**
     * Get the PageRank
     *
     * @return the PageRank
     */
    public function getPagerank()
    {
        return $this->pagerank ? $this->pagerank : '';
    }
    /**
     * Do a PageRank lookup
     *
     * @return the raw fetch data
     */
    public function lookup()    
    {
        $data = $this->fetch($this->getUrl());
        return $this->setData($data);
    }
    /**
     * Fetches the data
     *
     * @param string $url A valid URL to fetch
     *
     * @return string Return data from the fetched URL
     *
     * @throws Services_PageRank_Exception
     */
    protected function fetch($url)
    {
        $url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
        if (!$url) {
            throw new Services_PageRank_Exception(
                'Invalid URL',
                Services_PageRank_Exception::INVALID_URL
            );
        }
        try {
            $this->request->setUrl($url);
            $this->request->setMethod('GET');
            $data = $this->request->send()->getBody();
        } catch (HTTP_Request2_Exception $e) {
            throw new Services_PageRank_Exception(
                $e,
                Services_PageRank_Exception::FETCH
            );
        } 
        if (strlen($data)>1) {
            return $data;
        }
        throw new Services_PageRank_Exception(
            'Unable to fetch data',
            Services_PageRank_Exception::FETCH
        );
    }
    /**
     * Parses the data
     *
     * @return string Return parsed PageRank
     *
     * @throws Services_PageRank_Exception
     */
    protected function parse()
    {
        $pr = $this->getData();
        if (!$pr) {
            throw new Services_PageRank_Exception(
                'Unable to parse, no data',
                Services_PageRank_Exception::PARSE
            );
        } elseif ($pr[0] == '<') {
            throw new Services_PageRank_Exception(
                'Unable to parse, found HTML',
                Services_PageRank_Exception::PARSE
            );
        } else {
            $pr = substr(strrchr($pr, ':'), 1);
        }
        if ($pr) {
            $pr = trim($pr);
            $this->setPagerank($pr);
            return $pr;
        }
    }
   /**
    * Attaches a new observer
    *
    * @param    SplObserver
    */
    public function attach(SplObserver $observer)
    {
        foreach ($this->observers as $attached) {
            if ($attached === $observer) {
                return;
            }
        }
        $this->observers[] = $observer;
    }
   /**
    * Detaches an existing observer
    *
    * @param    SplObserver
    */
    public function detach(SplObserver $observer)
    {
        foreach ($this->observers as $key => $attached) {
            if ($attached === $observer) {
                unset($this->observers[$key]);
                return;
            }
        }
    }
   /**
    * Notifies all observers
    */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
   /**
    * Sets the last event
    *
    * Set the current state of the request and notify the observers.
    *
    * @param    string  event name
    * @param    mixed   event data
    */
    public function setLastEvent($name, $data = null)
    {
        $this->lastEvent = array(
            'name' => $name,
            'data' => $data
        );
        $this->notify();
    }
   /**
    * Returns the last event
    *
    * Observers should use this method to access the last change in request.
    * The following event names are possible:
    * <ul>
    *   <li>'setQuery'                - when the query string is set (string)</li>
    *   <li>'setCheckhash'            - when the checkhash is set (string)</li>
    *   <li>'setData'                 - when the raw data returned is set (string)</li>
    *   <li>'setPagerank'             - when the actual pagerank is set (string)</li>
    * </ul>
    *
    * @return   array   The array has two keys: 'name' and 'data'
    */
    public function getLastEvent()
    {
        return $this->lastEvent;
    }
}//eof