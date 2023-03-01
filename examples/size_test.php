<?php
/*
* File: SizeTest.php
* Category: -
* Author: D.Malli
* Created: 24.02.23 15:53
* Updated: -
*
* Description: Tests FETCH NNN RFC822.SIZE (issue 378)
*  -
*/

// Note: This is no real unit test, as it needs a real server to make sense.
//       I installed composer to the tests directory and had to:
//       composer require illuminate/pagination
//       to make the pagination library available!
//       Fill in your real configuration to use this file:
$CONFIG = array(
  'host'          => 'toFill',
  'port'          => 993,
  'encryption'    => 'ssl',
  'validate_cert' => true,
  'username'      => 'toFill',
  'password'      => 'toFill',
  'protocol'      => 'imap'
);
set_include_path('/home/didi1357/git/php-imap/src');


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'IMAP.php';
require_once 'Traits/HasEvents.php';
require_once 'Exceptions/MaskNotFoundException.php';
require_once 'Client.php';
require_once 'ClientManager.php';
require_once 'Support/Masks/Mask.php';
require_once 'Support/Masks/MessageMask.php';
require_once 'Support/Masks/AttachmentMask.php';
require_once 'Connection/Protocols/Response.php';
require_once 'Connection/Protocols/ProtocolInterface.php';
require_once 'Connection/Protocols/Protocol.php';
require_once 'Connection/Protocols/ImapProtocol.php';
require_once '../tests/vendor/autoload.php';
require_once 'Support/PaginatedCollection.php';
require_once 'Support/FolderCollection.php';
require_once 'Folder.php';
require_once 'Exceptions/ResponseException.php';
require_once 'Query/Query.php';
require_once 'Query/WhereQuery.php';
require_once 'Support/MessageCollection.php';
require_once 'Support/FlagCollection.php';
require_once 'Support/AttachmentCollection.php';
require_once 'Part.php';
require_once 'Structure.php';
require_once 'Attribute.php';
require_once 'Address.php';
require_once 'EncodingAliases.php';
require_once 'Header.php';
require_once 'Message.php';

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Support\Masks\MessageMask;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;

$CONFIG['masks'] = array(
  'message' => MessageMask::class,
  'attachment' => AttachmentMask::class
);
echo "<pre>";
$cm = new ClientManager($options = []);
$client = $cm->make($CONFIG)->connect();
//$client->getConnection()->enableDebug(); // uncomment this for debug output!
$folder = $client->getFolderByPath('INBOX');
//$message = $folder->messages()->getMessageByMsgn(1); // did not work as msgn implementation is currently broken!
$message = $folder->messages()->getMessageByUid(2);
var_dump($message->getSize());


?>

