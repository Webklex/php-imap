<?php
/*
* File: Structure.php
* Category: -
* Author: M.Goldenbaum
* Created: 17.09.20 20:38
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;


use Carbon\Carbon;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;

/**
 * Class Structure
 *
 * @package Webklex\PHPIMAP
 */
class Structure {

    /**
     * @var string $raw
     */
    public $raw = "";

    /**
     * @var Header $header
     */
    private $header = null;

    /**
     * @var int $type
     */
    public $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * @var Part[] $parts
     */
    public $parts = [];

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * Structure constructor.
     * @param $raw_structure
     * @param Header $header
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    public function __construct($raw_structure, Header $header) {
        $this->raw = $raw_structure;
        $this->header = $header;
        $this->config = ClientManager::get('options');
        $this->parse();
    }

    /**
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    protected function parse(){
        $this->findContentType();
        $this->parts = $this->find_parts();
    }

    /**
     * Determine the message content type
     */
    public function findContentType(){
        if(stripos($this->header->get("content-type"), 'multipart') === 0) {
            $this->type = IMAP::MESSAGE_TYPE_MULTIPART;
        }else{
            $this->type = IMAP::MESSAGE_TYPE_TEXT;
        }
    }

    /**
     * Determine the message content type
     */
    public function getBoundary(){
        return $this->header->find("/boundary\=\"(.*)\"/");
    }

    /**
     * @return array
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    public function find_parts(){
        if($this->type === IMAP::MESSAGE_TYPE_MULTIPART) {
            if (($boundary = $this->getBoundary()) === null)  {
                throw new MessageContentFetchingException("no content found", 0);
            }

            $raw_parts = explode($boundary, $this->raw);
            $parts = [];
            foreach($raw_parts as $part) {
                $part = trim(rtrim($part));
                if ($part !== "--") {
                    $parts[] = new Part($part);
                }
            }
            return $parts;
        }

        return [new Part($this->raw, $this->header)];
    }
}