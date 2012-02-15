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
 * Exception class for Services_PageRank package
 */
require_once 'Services/PageRank/Exception.php';

/**
 * A debug observer useful for debugging / testing.
 *
 * This observer logs to a log target data corresponding to the various request
 * and response events, it logs by default to php://output but can be configured
 * to log to a file or via the PEAR Log package.
 *
 * A simple example:
 * <code>
 * require_once 'Services/PageRank.php';
 * require_once 'Services/PageRank/ObserverLog.php';
 *
 * $request  = new Services_PageRank();
 * $observer = new Services_PageRank_ObserverLog();
 * $request->attach($observer);
 * $request->query('example.com');
 * </code>
 *
 * A more complex example with PEAR Log:
 * <code>
 * require_once 'Services/PageRank.php';
 * require_once 'Services/PageRank/ObserverLog.php';
 * require_once 'Log.php';
 *
 * $request  = new Services_PageRank();
 * // we want to log with PEAR log
 * $observer = new Services_PageRank_ObserverLog(Log::factory('console'));
 *
 * // we only want to log received headers
 * $observer->events = array('receivedHeaders');
 *
 * $request->attach($observer);
 * $request->query('example.com');
 * </code>
 *
 * @category  Services
 * @package   Services_PageRank
 * @author    James Wade <hm2k@php.net>
 * @copyright 2011 James Wade
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pagerank.phurix.net/
 */
class Services_PageRank_ObserverLog implements SplObserver
{
    // properties {{{

    /**
     * The log target, it can be a a resource or a PEAR Log instance.
     *
     * @var resource|Log $target
     */
    protected $target = null;

    /**
     * The events to log.
     *
     * @var array $events
     */
    public $events = array(
        'setQuery',
        'setCheckhash',
        'setData',
        'setPagerank',
    );

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param mixed $target Can be a file path (default: php://output), a resource,
     *                      or an instance of the PEAR Log class.
     * @param array $events Array of events to listen to (default: all events)
     *
     * @return void
     */
    public function __construct($target = 'php://output', array $events = array())
    {
        if (!empty($events)) {
            $this->events = $events;
        }
        if (is_resource($target) || $target instanceof Log) {
            $this->target = $target;
        } elseif (false === ($this->target = @fopen($target, 'ab'))) {
            throw new Services_PageRank_Exception("Unable to open '{$target}'");
        }
    }

    // }}}
    // update() {{{

    /**
     * Called when the request notifies us of an event.
     *
     * @param Services_PageRank $subject The Services_PageRank instance
     *
     * @return void
     */
    public function update(SplSubject $subject)
    {
        $event = $subject->getLastEvent();
        if (!in_array($event['name'], $this->events)) {
            return;
        }

        switch ($event['name']) {
        case 'setQuery':
            $this->log('* Query: ' . $event['data']);
            break;
        case 'setCheckhash':
            $this->log('* Checkhash: ' . $event['data']);
            break;
        case 'setData':
            $data = explode("\r\n", $event['data']);
            array_pop($data);
            foreach ($data as $header) {
                $this->log('> ' . $data);
            }
            break;
        case 'setPagerank':
            $this->log('* Pagerank: ' . $event['data']);
            break;
        }
    }

    // }}}
    // log() {{{

    /**
     * Logs the given message to the configured target.
     *
     * @param string $message Message to display
     *
     * @return void
     */
    protected function log($message)
    {
        if ($this->target instanceof Log) {
            $this->target->debug($message);
        } elseif (is_resource($this->target)) {
            fwrite($this->target, $message . "\r\n");
        }
    }

    // }}}
}//eof