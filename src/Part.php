<?php
/*
* File: Part.php
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
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;

/**
 * Class Part
 *
 * @package Webklex\PHPIMAP
 */
class Part {

    /**
     * @var string $raw
     */
    public $raw = "";

    /**
     * @var int $type
     */
    public $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * @var string $content
     */
    public $content = "";

    /**
     * @var string $subtype
     */
    public $subtype = null;

    /**
     * @var string $charset
     */
    public $charset = "utf-8";

    /**
     * @var int $encoding
     */
    public $encoding = IMAP::MESSAGE_ENC_OTHER;

    /**
     * @var boolean $ifdisposition
     */
    public $ifdisposition = false;

    /**
     * @var string $disposition
     */
    public $disposition = null;

    /**
     * @var boolean $ifdescription
     */
    public $ifdescription = false;

    /**
     * @var string $description
     */
    public $description = null;

    /**
     * @var string $filename
     */
    public $filename = null;

    /**
     * @var string $name
     */
    public $name = null;

    /**
     * @var string $id
     */
    public $id = null;

    /**
     * @var integer $part_number
     */
    public $part_number = 0;

    /**
     * @var integer $bytes
     */
    public $bytes = null;

    /**
     * @var Header $header
     */
    private $header = null;

    /**
     * Part constructor.
     * @param $raw_part
     * @param Header $header
     * @param integer $part_number
     *
     * @throws InvalidMessageDateException
     */
    public function __construct($raw_part, $header = null, $part_number = 0) {
        $this->raw = $raw_part;
        $this->header = $header;
        $this->part_number = $part_number;
        $this->parse();
    }

    /**
     * Parse the raw parts
     *
     * @throws InvalidMessageDateException
     */
    protected function parse(){
        if ($this->header === null) {
            $body = $this->findHeaders();
        }else{
            $body = $this->raw;
        }

        $this->parseSubtype();
        $this->parseDisposition();
        $this->parseDescription();
        $this->parseEncoding();

        $this->charset = $this->header->get("charset");
        $this->name = $this->header->get("name");
        $this->filename = $this->header->get("filename");
        $this->id = $this->header->get("id");

        $this->content = trim(rtrim($body));
        $this->bytes = strlen($this->content);
    }

    /**
     * Find all available headers and return the left over body segment
     *
     * @return string
     * @throws InvalidMessageDateException
     */
    private function findHeaders(){
        $body = $this->raw;
        while (($pos = strpos($body, "\r\n")) > 0) {
            $body = substr($body, $pos + 2);
        }
        $headers = substr($this->raw, 0, strlen($body) * -1);
        $body = substr($body, 0, -2);

        $this->header = new Header($headers);

        return (string) $body;
    }

    private function parseSubtype(){
        $content_type = $this->header->get("content-type");
        if (($pos = strpos($content_type, "/")) !== false){
            $this->subtype = substr($content_type, $pos + 1);
        }
    }

    private function parseDisposition(){
        $content_disposition = $this->header->get("content-disposition");
        if($content_disposition !== null) {
            $this->ifdisposition = true;
            $this->disposition = $content_disposition;
        }
    }

    private function parseDescription(){
        $content_description = $this->header->get("content-description");
        if($content_description !== null) {
            $this->ifdescription = true;
            $this->description = $content_description;
        }
    }

    private function parseEncoding(){
        $encoding = $this->header->get("content-transfer-encoding");
        if($encoding !== null) {
            switch (strtolower($encoding)) {
                case "quoted-printable":
                    $this->encoding = IMAP::MESSAGE_ENC_QUOTED_PRINTABLE;
                    break;
                case "base64":
                    $this->encoding = IMAP::MESSAGE_ENC_BASE64;
                    break;
                case "7bit":
                    $this->encoding = IMAP::MESSAGE_ENC_7BIT;
                    break;
                case "8bit":
                    $this->encoding = IMAP::MESSAGE_ENC_8BIT;
                    break;
                case "binary":
                    $this->encoding = IMAP::MESSAGE_ENC_BINARY;
                    break;
                default:
                    $this->encoding = IMAP::MESSAGE_ENC_OTHER;
                    break;

            }
        }
    }

}