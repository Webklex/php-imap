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


use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;

/**
 * Class Structure
 *
 * @package Webklex\PHPIMAP
 */
class Structure {

    /**
     * Raw structure
     *
     * @var string $raw
     */
    public $raw = "";

    /**
     * @var Header $header
     */
    private $header = null;

    /**
     * Message type (if multipart or not)
     *
     * @var int $type
     */
    public $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * All available parts
     *
     * @var Part[] $parts
     */
    public $parts = [];

    /**
     * Config holder
     *
     * @var array $config
     */
    protected $config = [];

    /**
     * Structure constructor.
     * @param $raw_structure
     * @param Header $header
     *
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
     * Parse the given raw structure
     *
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

        $content_type = $this->header->get("content_type");
        $content_type = (is_array($content_type)) ? implode(' ', $content_type) : $content_type;
        if(stripos($content_type, 'multipart') === 0) {
            $this->type = IMAP::MESSAGE_TYPE_MULTIPART;
        }else{
            $this->type = IMAP::MESSAGE_TYPE_TEXT;
        }
    }

    /**
     * Determine the message content type
     */
    public function getBoundary(){
        $boundary = $this->header->find("/boundary=\"?([^\"]*)[\";\s]/");
        return str_replace('"', '', $boundary);
    }

    /**
     * Find all available parts
     *
     * @return array
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    public function find_parts(){
        if($this->type === IMAP::MESSAGE_TYPE_MULTIPART) {
            if (($boundary = $this->getBoundary()) === null)  {
                throw new MessageContentFetchingException("no content found", 0);
            }

            $boundaries = [
                $boundary
            ];

            if (preg_match("/boundary\=\"?(.*)\"?/", $this->raw, $match) == 1) {
                if(is_array($match[1])){
                    foreach($match[1] as $matched){
                        $boundaries[] = str_replace('"', '', $matched);
                    }
                }else{
                    if(!empty($match[1])) {
                        $boundaries[] = str_replace('"', '', $match[1]);
                    }
                }
            }

            $raw_parts = explode( $boundaries[0], str_replace($boundaries, $boundaries[0], $this->raw) );
            $parts = [];
            $part_number = 0;
            foreach($raw_parts as $part) {
                $part = trim(rtrim($part));
                if ($part !== "--") {
                    $parts[] = new Part($part, null, $part_number);
                    $part_number++;
                }
            }
            return $parts;
        }

        return [new Part($this->raw, $this->header)];
    }
}
