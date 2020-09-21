<?php
/*
* File:     Client.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;
use Webklex\PHPIMAP\Connection\Protocols\LegacyProtocol;
use Webklex\PHPIMAP\Connection\Protocols\Protocol;
use Webklex\PHPIMAP\Connection\Protocols\ProtocolInterface;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\ProtocolNotSupportedException;
use Webklex\PHPIMAP\Support\FolderCollection;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;
use Webklex\PHPIMAP\Support\Masks\MessageMask;

/**
 * Class Client
 *
 * @package Webklex\PHPIMAP
 */
class Client {


    /**
     * @var boolean|Protocol
     */
    public $connection = false;

    /**
     * Server hostname.
     *
     * @var string
     */
    public $host;

    /**
     * Server port.
     *
     * @var int
     */
    public $port;

    /**
     * Service protocol.
     *
     * @var int
     */
    public $protocol;

    /**
     * Server encryption.
     * Supported: none, ssl, tls, or notls.
     *
     * @var string
     */
    public $encryption;

    /**
     * If server has to validate cert.
     *
     * @var mixed
     */
    public $validate_cert;

    /**
     * Account username/
     *
     * @var mixed
     */
    public $username;

    /**
     * Account password.
     *
     * @var string
     */
    public $password;

    /**
     * Account authentication method.
     *
     * @var string
     */
    public $authentication;

    /**
     * Active folder.
     *
     * @var Folder
     */
    protected $active_folder = false;

    /**
     * All valid and available account config parameters
     *
     * @var array $validConfigKeys
     */
    protected $valid_config_keys = ['host', 'port', 'encryption', 'validate_cert', 'username', 'password', 'protocol',
                                    'authentication'];

    /**
     * @var string $default_message_mask
     */
    protected $default_message_mask = MessageMask::class;

    /**
     * @var string $default_attachment_mask
     */
    protected $default_attachment_mask = AttachmentMask::class;

    /**
     * Client constructor.
     * @param array $config
     *
     * @throws MaskNotFoundException
     */
    public function __construct($config = []) {
        $this->setConfig($config);
        $this->setMaskFromConfig($config);
    }

    /**
     * Client destructor
     */
    public function __destruct() {
        $this->disconnect();
    }

    /**
     * Set the Client configuration
     *
     * @param array $config
     *
     * @return self
     */
    public function setConfig(array $config) {
        $default_account = ClientManager::get('default');
        $default_config  = ClientManager::get("accounts.$default_account");

        foreach ($this->valid_config_keys as $key) {
            $this->$key = isset($config[$key]) ? $config[$key] : $default_config[$key];
        }

        return $this;
    }

    /**
     * Look for a possible mask in any available config
     * @param $config
     *
     * @throws MaskNotFoundException
     */
    protected function setMaskFromConfig($config) {
        $default_config  = ClientManager::get("masks");

        if(isset($config['masks'])){
            if(isset($config['masks']['message'])) {
                if(class_exists($config['masks']['message'])) {
                    $this->default_message_mask = $config['masks']['message'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$config['masks']['message']);
                }
            }else{
                if(class_exists($default_config['message'])) {
                    $this->default_message_mask = $default_config['message'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$default_config['message']);
                }
            }
            if(isset($config['masks']['attachment'])) {
                if(class_exists($config['masks']['attachment'])) {
                    $this->default_message_mask = $config['masks']['attachment'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$config['masks']['attachment']);
                }
            }else{
                if(class_exists($default_config['attachment'])) {
                    $this->default_message_mask = $default_config['attachment'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$default_config['attachment']);
                }
            }
        }else{
            if(class_exists($default_config['message'])) {
                $this->default_message_mask = $default_config['message'];
            }else{
                throw new MaskNotFoundException("Unknown mask provided: ".$default_config['message']);
            }

            if(class_exists($default_config['attachment'])) {
                $this->default_message_mask = $default_config['attachment'];
            }else{
                throw new MaskNotFoundException("Unknown mask provided: ".$default_config['attachment']);
            }
        }

    }

    /**
     * Get the current imap resource
     *
     * @return bool|Protocol|ProtocolInterface
     * @throws ConnectionFailedException
     */
    public function getConnection() {
        $this->checkConnection();
        return $this->connection;
    }

    /**
     * Determine if connection was established.
     *
     * @return bool
     */
    public function isConnected() {
        return $this->connection ? $this->connection->connected() : false;
    }

    /**
     * Determine if connection was established and connect if not.
     *
     * @throws ConnectionFailedException
     */
    public function checkConnection() {
        if (!$this->isConnected()) {
            $this->connect();
        }
    }

    /**
     * Force a reconnect
     *
     * @throws ConnectionFailedException
     */
    public function reconnect() {
        if ($this->isConnected()) {
            $this->disconnect();
        }
        $this->connect();
    }

    /**
     * Connect to server.
     *
     * @return $this
     * @throws ConnectionFailedException
     */
    public function connect() {
        $this->disconnect();
        $protocol = strtolower($this->protocol);

        if ($protocol == "imap") {
            $timeout = $this->connection !== false ? $this->connection->getConnectionTimeout() : null;
            $this->connection = new ImapProtocol($this->validate_cert);
            $this->connection->setConnectionTimeout($timeout);
        }else{
            if (extension_loaded('imap') === false) {
                throw new ConnectionFailedException("connection setup failed", 0, new ProtocolNotSupportedException($protocol." is an unsupported protocol"));
            }
            $this->connection = new LegacyProtocol($this->validate_cert);
            if (strpos($protocol, "legacy-") === 0) {
                $protocol = substr($protocol, 7);
            }
            $this->connection->setProtocol($protocol);
        }

        $this->connection->connect($this->host, $this->port, $this->encryption);
        $this->authenticate();

        return $this;
    }

    /**
     * Authenticate the current session
     *
     * @throws ConnectionFailedException
     */
    protected function authenticate() {
        try {
            if ($this->authentication == "oauth") {
                $this->connection->authenticate($this->username, $this->password);
            }else{
                $this->connection->login($this->username, $this->password);
            }
        } catch (\Exception $e) {
            throw new ConnectionFailedException("connection setup failed", 0, $e);
        }
    }

    /**
     * Disconnect from server.
     *
     * @return $this
     */
    public function disconnect() {
        if ($this->isConnected() && $this->connection !== false) {
            $this->connection->logout();
        }

        return $this;
    }

    /**
     * Get a folder instance by a folder name
     * @param $folder_name
     * @return mixed
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     */
    public function getFolder($folder_name) {
        return $this->getFolders(false)->where("name", $folder_name)->first();
    }

    /**
     * Get folders list.
     * If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.
     *
     * @param boolean     $hierarchical
     * @param string|null $parent_folder
     *
     * @return FolderCollection
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     */
    public function getFolders($hierarchical = true, $parent_folder = null) {
        $this->checkConnection();
        $folders = FolderCollection::make([]);

        $pattern = $parent_folder.($hierarchical ? '%' : '*');
        $items = $this->connection->folders('', $pattern);

        if(is_array($items)){
            foreach ($items as $folder_name => $item) {
                $folder = new Folder($this, $folder_name, $item["delimiter"], $item["flags"]);

                if ($hierarchical && $folder->hasChildren()) {
                    $pattern = $folder->full_name.$folder->delimiter.'%';

                    $children = $this->getFolders(true, $pattern);
                    $folder->setChildren($children);
                }

                $folders->push($folder);
            }

            return $folders;
        }else{
            throw new FolderFetchingException("failed to fetch any folders");
        }
    }

    /**
     * Open folder.
     *
     * @param string $folder
     * @return mixed
     * @throws ConnectionFailedException
     */
    public function openFolder($folder) {
        if ($this->active_folder == $folder && $this->isConnected()) {
            return true;
        }
        $this->checkConnection();
        $this->active_folder = $folder;
        return $this->connection->selectFolder($folder);
    }

    /**
     * Create a new Folder
     * @param string $folder
     * @param boolean $expunge
     *
     * @return bool
     * @throws ConnectionFailedException
     */
    public function createFolder($folder, $expunge = true) {
        $this->checkConnection();
        $status = $this->connection->createFolder($folder);
        if($expunge) $this->expunge();

        return $status;
    }

    /**
     * Check a given folder
     * @param $folder
     * @return false|object
     * @throws ConnectionFailedException
     */
    public function checkFolder($folder) {
        $this->checkConnection();
        return $this->connection->examineFolder($folder);
    }

    /**
     * Get the current active folder
     *
     * @return Folder
     */
    public function getFolderPath(){
        return $this->active_folder;
    }

    /**
     * Retrieve the quota level settings, and usage statics per mailbox
     *
     * @return array
     * @throws ConnectionFailedException
     */
    public function getQuota() {
        $this->checkConnection();
        return $this->connection->getQuota($this->username);
    }

    /**
     * Retrieve the quota settings per user
     *
     * @param string $quota_root
     *
     * @return array
     * @throws ConnectionFailedException
     */
    public function getQuotaRoot($quota_root = 'INBOX') {
        $this->checkConnection();
        return $this->connection->getQuotaRoot($quota_root);
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return bool
     * @throws ConnectionFailedException
     */
    public function expunge() {
        $this->checkConnection();
        return $this->connection->expunge();
    }

    /**
     * Set the imap timeout for a given operation type
     * @param $timeout
     *
     * @return Protocol
     */
    public function setTimeout($timeout) {
        return $this->connection->setConnectionTimeout($timeout);
    }

    /**
     * Get the timeout for a certain operation
     * @param $type
     *
     * @return int
     */
    public function getTimeout($type){
        return $this->connection->getConnectionTimeout();
    }

    /**
     * @return string
     */
    public function getDefaultMessageMask(){
        return $this->default_message_mask;
    }

    /**
     * @param $mask
     *
     * @return $this
     * @throws MaskNotFoundException
     */
    public function setDefaultMessageMask($mask) {
        if(class_exists($mask)) {
            $this->default_message_mask = $mask;

            return $this;
        }

        throw new MaskNotFoundException("Unknown mask provided: ".$mask);
    }

    /**
     * @return string
     */
    public function getDefaultAttachmentMask(){
        return $this->default_attachment_mask;
    }

    /**
     * @param $mask
     *
     * @return $this
     * @throws MaskNotFoundException
     */
    public function setDefaultAttachmentMask($mask) {
        if(class_exists($mask)) {
            $this->default_attachment_mask = $mask;

            return $this;
        }

        throw new MaskNotFoundException("Unknown mask provided: ".$mask);
    }
}
