<?php
/*
* File:     Query.php
* Category: -
* Author:   M. Goldenbaum
* Created:  21.07.18 18:54
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Query;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageSearchValidationException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\MessageCollection;

/**
 * Class Query
 *
 * @package Webklex\PHPIMAP\Query
 */
class Query {

    /** @var array $query */
    protected $query;

    /** @var string $raw_query  */
    protected $raw_query;

    /** @var string $charset */
    protected $charset;

    /** @var Client $client */
    protected $client;

    /** @var int $limit */
    protected $limit = null;

    /** @var int $page */
    protected $page = 1;

    /** @var int $fetch_options */
    protected $fetch_options = null;

    /** @var int $fetch_body */
    protected $fetch_body = true;

    /** @var int $fetch_flags */
    protected $fetch_flags = true;

    /** @var string $fetch_order */
    protected $fetch_order;

    /** @var string $date_format */
    protected $date_format;

    /**
     * Query constructor.
     * @param Client $client
     * @param string $charset
     */
    public function __construct(Client $client, $charset = 'UTF-8') {
        $this->setClient($client);

        if(ClientManager::get('options.fetch') === IMAP::FT_PEEK) $this->leaveUnread();

        if (ClientManager::get('options.fetch_order') === 'desc') {
            $this->fetch_order = 'desc';
        } else {
            $this->fetch_order = 'asc';
        }

        $this->date_format = ClientManager::get('date_format', 'd M y');

        $this->charset = $charset;
        $this->query = collect();
        $this->boot();
    }

    /**
     * Instance boot method for additional functionality
     */
    protected function boot(){}

    /**
     * Parse a given value
     * @param mixed $value
     *
     * @return string
     */
    protected function parse_value($value){
        switch(true){
            case $value instanceof \Carbon\Carbon:
                $value = $value->format($this->date_format);
                break;
        }

        return (string) $value;
    }

    /**
     * Check if a given date is a valid carbon object and if not try to convert it
     * @param $date
     *
     * @return Carbon
     * @throws MessageSearchValidationException
     */
    protected function parse_date($date) {
        if($date instanceof \Carbon\Carbon) return $date;

        try {
            $date = Carbon::parse($date);
        } catch (\Exception $e) {
            throw new MessageSearchValidationException();
        }

        return $date;
    }

    /**
     * Don't mark messages as read when fetching
     *
     * @return $this
     */
    public function leaveUnread() {
        $this->setFetchOptions(IMAP::FT_PEEK);

        return $this;
    }

    /**
     * Mark all messages as read when fetching
     *
     * @return $this
     */
    public function markAsRead() {
        $this->setFetchOptions(IMAP::FT_UID);

        return $this;
    }

    /**
     * Perform an imap search request
     *
     * @return Collection
     * @throws GetMessagesFailedException
     */
    protected function search(){
        $this->generate_query();

        try {
            $available_messages = $this->client->getConnection()->search([$this->getRawQuery()]);
        } catch (RuntimeException $e) {
            $available_messages = false;
        } catch (ConnectionFailedException $e) {
            throw new GetMessagesFailedException("failed to fetch messages", 0, $e);
        }

        if ($available_messages !== false) {
            return collect($available_messages);
        }

        return collect();
    }

    /**
     * Count all available messages matching the current search criteria
     *
     * @return int
     * @throws GetMessagesFailedException
     */
    public function count() {
        return $this->search()->count();
    }

    /**
     * Fetch the current query and return all found messages
     *
     * @return MessageCollection
     * @throws GetMessagesFailedException
     */
    public function get() {
        $messages = MessageCollection::make([]);

        $available_messages = $this->search();
        try {
            if (($available_messages_count = $available_messages->count()) > 0) {

                $messages->total($available_messages_count);

                if ($this->fetch_order === 'desc') {
                    $available_messages = $available_messages->reverse();
                }

                $message_key = ClientManager::get('options.message_key');
                $query =& $this;

                $available_messages->forPage($this->page, $this->limit)->each(function($msgno, $msglist) use(&$messages, $message_key, $query) {
                    $message = $query->getMessage($msgno, $msglist);
                    switch ($message_key){
                        case 'number':
                            $message_key = $message->getMessageNo();
                            break;
                        case 'list':
                            $message_key = $msglist;
                            break;
                        default:
                            $message_key = $message->getMessageId();
                            break;

                    }
                    $messages->put($message_key, $message);
                });
            }

            return $messages;
        } catch (\Exception $e) {
            throw new GetMessagesFailedException($e->getMessage(),0, $e);
        }
    }

    /**
     * Get a new Message instance
     * @param $msgno
     * @param null $msglist
     *
     * @return Message
     * @throws ConnectionFailedException
     * @throws RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageHeaderFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\EventNotFoundException
     */
    public function getMessage($msgno, $msglist = null){
        return new Message($msgno, $msglist, $this->getClient(), $this->getFetchOptions(), $this->getFetchBody(), $this->getFetchFlags());
    }

    /**
     * Paginate the current query
     * @param int $per_page
     * @param null $page
     * @param string $page_name
     *
     * @return LengthAwarePaginator
     * @throws GetMessagesFailedException
     */
    public function paginate($per_page = 5, $page = null, $page_name = 'imap_page'){
        if (
               $page === null
            && isset($_GET[$page_name])
            && $_GET[$page_name] > 0
        ) {
            $this->page = intval($_GET[$page_name]);
        } elseif ($page > 0) {
            $this->page = $page;
        }

        $this->limit = $per_page;

        return $this->get()->paginate($per_page, $this->page, $page_name);
    }

    /**
     * Get the raw IMAP search query
     *
     * @return string
     */
    public function generate_query() {
        $query = '';
        $this->query->each(function($statement) use(&$query) {
            if (count($statement) == 1) {
                $query .= $statement[0];
            } else {
                if($statement[1] === null){
                    $query .= $statement[0];
                }else{
                    $query .= $statement[0].' "'.$statement[1].'"';
                }
            }
            $query .= ' ';

        });

        $this->raw_query = trim($query);

        return $this->raw_query;
    }

    /**
     * @return Client
     * @throws ConnectionFailedException
     */
    public function getClient() {
        $this->client->checkConnection();
        return $this->client;
    }

    /**
     * Set the limit and page for the current query
     * @param int $limit
     * @param int $page
     *
     * @return $this
     */
    public function limit($limit, $page = 1) {
        if($page >= 1) $this->page = $page;
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return array
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @param array $query
     * @return Query
     */
    public function setQuery($query) {
        $this->query = $query;
        return $this;
    }

    /**
     * @return string
     */
    public function getRawQuery() {
        return $this->raw_query;
    }

    /**
     * @param string $raw_query
     * @return Query
     */
    public function setRawQuery($raw_query) {
        $this->raw_query = $raw_query;
        return $this;
    }

    /**
     * @return string
     */
    public function getCharset() {
        return $this->charset;
    }

    /**
     * @param string $charset
     * @return Query
     */
    public function setCharset($charset) {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @param Client $client
     * @return Query
     */
    public function setClient(Client $client) {
        $this->client = $client;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return Query
     */
    public function setLimit($limit) {
        $this->limit = $limit <= 0 ? null : $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * @param int $page
     * @return Query
     */
    public function setPage($page) {
        $this->page = $page;
        return $this;
    }

    /**
     * @param boolean $fetch_options
     * @return Query
     */
    public function setFetchOptions($fetch_options) {
        $this->fetch_options = $fetch_options;
        return $this;
    }

    /**
     * @param boolean $fetch_options
     * @return Query
     */
    public function fetchOptions($fetch_options) {
        return $this->setFetchOptions($fetch_options);
    }

    /**
     * @return int
     */
    public function getFetchOptions() {
        return $this->fetch_options;
    }

    /**
     * @return boolean
     */
    public function getFetchBody() {
        return $this->fetch_body;
    }

    /**
     * @param boolean $fetch_body
     * @return Query
     */
    public function setFetchBody($fetch_body) {
        $this->fetch_body = $fetch_body;
        return $this;
    }

    /**
     * @param boolean $fetch_body
     * @return Query
     */
    public function fetchBody($fetch_body) {
        return $this->setFetchBody($fetch_body);
    }

    /**
     * @return int
     */
    public function getFetchFlags() {
        return $this->fetch_flags;
    }

    /**
     * @param int $fetch_flags
     * @return Query
     */
    public function setFetchFlags($fetch_flags) {
        $this->fetch_flags = $fetch_flags;
        return $this;
    }

    /**
     * @param string $fetch_order
     * @return Query
     */
    public function setFetchOrder($fetch_order) {
        $fetch_order = strtolower($fetch_order);

        if (in_array($fetch_order, ['asc', 'desc'])) {
            $this->fetch_order = $fetch_order;
        }

        return $this;
    }

    /**
     * @param string $fetch_order
     * @return Query
     */
    public function fetchOrder($fetch_order) {
        return $this->setFetchOrder($fetch_order);
    }

    /**
     * @return string
     */
    public function getFetchOrder() {
        return $this->fetch_order;
    }

    /**
     * @return Query
     */
    public function setFetchOrderAsc() {
        return $this->setFetchOrder('asc');
    }

    /**
     * @return Query
     */
    public function fetchOrderAsc($fetch_order) {
        return $this->setFetchOrderAsc();
    }

    /**
     * @return Query
     */
    public function setFetchOrderDesc() {
        return $this->setFetchOrder('desc');
    }

    /**
     * @return Query
     */
    public function fetchOrderDesc($fetch_order) {
        return $this->setFetchOrderDesc();
    }
}
