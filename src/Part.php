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


use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;

/**
 * Class Part
 *
 * @package Webklex\PHPIMAP
 */
class Part {

    /**
     * Raw part
     *
     * @var string $raw
     */
    public $raw = "";

    /**
     * Part type
     *
     * @var int $type
     */
    public $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * Part content
     *
     * @var string $content
     */
    public $content = "";

    /**
     * Part subtype
     *
     * @var string $subtype
     */
    public $subtype = null;

    /**
     * Part charset - if available
     *
     * @var string $charset
     */
    public $charset = "utf-8";

    /**
     * Part encoding method
     *
     * @var int $encoding
     */
    public $encoding = IMAP::MESSAGE_ENC_OTHER;

    /**
     * Alias to check if the part is an attachment
     *
     * @var boolean $ifdisposition
     */
    public $ifdisposition = false;

    /**
     * Indicates if the part is an attachment
     *
     * @var string $disposition
     */
    public $disposition = null;

    /**
     * Alias to check if the part has a description
     *
     * @var boolean $ifdescription
     */
    public $ifdescription = false;

    /**
     * Part description if available
     *
     * @var string $description
     */
    public $description = null;

    /**
     * Part filename if available
     *
     * @var string $filename
     */
    public $filename = null;

    /**
     * Part name if available
     *
     * @var string $name
     */
    public $name = null;

    /**
     * Part id if available
     *
     * @var string $id
     */
    public $id = null;

    /**
     * The part number of the current part
     *
     * @var integer $part_number
     */
    public $part_number = 0;

    /**
     * Part length in bytes
     *
     * @var integer $bytes
     */
    public $bytes = null;

    /**
     * Part content type
     *
     * @var string|null $content_type
     */
    public $content_type = null;

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

        if(!empty($this->header->get("id"))) {
            $this->id = $this->header->get("id");
        } else if(!empty($this->header->get("x-attachment-id"))){
            $this->id = $this->header->get("x-attachment-id");
        } else if(!empty($this->header->get("content-id"))){
            $this->id = strtr($this->header->get("content-id"), [
                '<' => '',
                '>' => ''
            ]);
        }

        if(!empty($this->header->get("content-type"))){
            $rawContentType = $this->header->get("content-type");
            $contentTypeArray = explode(';', $rawContentType);
            $this->content_type = trim($contentTypeArray[0]);
        }


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

    /**
     * Try to parse the subtype if any is present
     */
    private function parseSubtype(){
        $content_type = $this->header->get("content-type");
        if (($pos = strpos($content_type, "/")) !== false){
            $this->subtype = substr($content_type, $pos + 1);
        }
    }

    /**
     * Try to parse the disposition if any is present
     */
    private function parseDisposition(){
        $content_disposition = $this->header->get("content-disposition");
        if($content_disposition !== null) {
            $this->ifdisposition = true;
            $this->disposition = $content_disposition;
        }
    }

    /**
     * Try to parse the description if any is present
     */
    private function parseDescription(){
        $content_description = $this->header->get("content-description");
        if($content_description !== null) {
            $this->ifdescription = true;
            $this->description = $content_description;
        }
    }

    /**
     * Try to parse the encoding if any is present
     */
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