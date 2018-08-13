# IMAP Library for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

## Description

PHPIMAP is an easy way to integrate the native php imap library into your php app.

>This package is a fork of one of my other projects [webklex/laravel-imap](https://github.com/Webklex/laravel-imap) 
which was specially designed to work with the laravel framework. 

>Since this library received way more interest than I thought, I decided to take the core features and 
and move them into a new repository.

>This repository will be part of [webklex/laravel-imap](https://github.com/Webklex/laravel-imap) within the next few releases.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Basic usage example](#basic-usage-example)
    - [Folder / Mailbox](#folder--mailbox)
    - [Search](#search-for-messages)
    - [Result limiting](#result-limiting)
    - [Pagination](#pagination)
    - [Fetch a specific message](#fetch-a-specific-message)
    - [Message flags](#message-flags)
    - [Attachments](#attachments)
    - [Advanced fetching](#advanced-fetching)
    - [Specials](#specials)
- [Support](#support)
- [Documentation](#documentation)
  - [Client::class](#clientclass)
  - [Message::class](#messageclass)
  - [Folder::class](#folderclass)
  - [Query::class](#queryclass)
  - [Attachment::class](#attachmentclass) 
  - [MessageCollection::class](#messagecollectionclass) 
  - [AttachmentCollection::class](#attachmentcollectionclass) 
  - [FolderCollection::class](#foldercollectionclass) 
  - [FlagCollection::class](#flagcollectionclass) 
- [Known issues](#known-issues)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

## Installation

1) Install the php-imap library if it isn't already installed:

``` shell
sudo apt-get install php*-imap php*-mbstring php*-mcrypt && sudo apache2ctl graceful
```

You might also want to check `phpinfo()` if the extension is enabled.

2) Now install the Laravel IMAP package by running the following command:

``` shell
composer require webklex/php-imap
```

3) Create your own custom config file like [config/imap.php](src/config/imap.php):

## Configuration

Supported protocols:
- `imap` &mdash; Use IMAP [default]
- `pop3` &mdash; Use POP3
- `nntp` &mdash; Use NNTP

The following encryption methods are supported:
- `false` &mdash; Disable encryption 
- `ssl` &mdash; Use SSL
- `tls` &mdash; Use TLS

Detailed [config/imap.php](src/config/imap.php) configuration:
 - `default` &mdash; used default account. It will be used as default for any missing account parameters. If however the default account is missing a parameter the package default will be used. Set to `false` to disable this functionality.
 - `accounts` &mdash; all available accounts
   - `default` &mdash; The account identifier (in this case `default` but could also be `fooBar` etc).
     - `host` &mdash; imap host
     - `port` &mdash; imap port
     - `encryption` &mdash; desired encryption method
     - `validate_cert` &mdash; decide weather you want to verify the certificate or not
     - `username` &mdash; imap account username
     - `password` &mdash; imap account password
 - `options` &mdash; additional fetch options
   - `delimiter` &mdash; you can use any supported char such as ".", "/", etc
   - `fetch` &mdash; `FT_UID` (message marked as read by fetching the message) or `FT_PEEK` (fetch the message without setting the "read" flag)
   - `fetch_body` &mdash; If set to `false` all messages will be fetched without the body and any potential attachments
   - `fetch_attachment` &mdash;  If set to `false` all messages will be fetched without any attachments
   - `fetch_flags` &mdash;  If set to `false` all messages will be fetched without any flags
   - `message_key` &mdash; Message key identifier option
   - `fetch_order` &mdash; Message fetch order
   - `open` &mdash; special configuration for imap_open()
     - `DISABLE_AUTHENTICATOR` &mdash; Disable authentication properties.

## Usage
#### Basic usage example
This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not ben
tested in real live but it gives an impression on how things work.

``` php
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;

$cm = new ClientManager('path/to/config/imap.php');

// or use an array of options instead
$cm = new ClientManager(array $options = []);

/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$oClient = $cm->account('account_identifier');

// or create a new instance manually        
$oClient = new Client([
    'host'          => 'somehost.com',
    'port'          => 993,
    'encryption'    => 'ssl',
    'validate_cert' => true,
    'username'      => 'username',
    'password'      => 'password',
    'protocol'      => 'imap'
]);

//Connect to the IMAP Server
$oClient->connect();

//Get all Mailboxes
/** @var \Webklex\PHPIMAP\Support\FolderCollection $aFolder */
$aFolder = $oClient->getFolders();

//Loop through every Mailbox
/** @var \Webklex\PHPIMAP\Folder $oFolder */
foreach($aFolder as $oFolder){

    //Get all Messages of the current Mailbox $oFolder
    /** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
    $aMessage = $oFolder->messages()->all()->get();
    
    /** @var \Webklex\PHPIMAP\Message $oMessage */
    foreach($aMessage as $oMessage){
        echo $oMessage->getSubject().'<br />';
        echo 'Attachments: '.$oMessage->getAttachments()->count().'<br />';
        echo $oMessage->getHTMLBody(true);
        
        //Move the current Message to 'INBOX.read'
        if($oMessage->moveToFolder('INBOX.read') == true){
            echo 'Message has ben moved';
        }else{
            echo 'Message could not be moved';
        }
    }
}
```

#### Folder / Mailbox
There is an experimental function available to get a Folder instance by name. 
For an easier access please take a look at the new config option `imap.options.delimiter` however the `getFolder` 
method takes three options: the required (string) $folder_name and two optional variables. An integer $attributes which 
seems to be sometimes 32 or 64 (I honestly have no clue what this number does, so feel free to enlighten me and anyone 
else) and a delimiter which if it isn't set will use the default option configured inside the [config/imap.php](src/config/imap.php) file.
``` php
/** @var \Webklex\PHPIMAP\Client $oClient */

/** @var \Webklex\PHPIMAP\Folder $oFolder */
$oFolder = $oClient->getFolder('INBOX.name');
```

List all available folders:
``` php
/** @var \Webklex\PHPIMAP\Client $oClient */

/** @var \Webklex\PHPIMAP\Support\FolderCollection $aFolder */
$aFolder = $oClient->getFolders();
```


#### Search for messages
Search for specific emails:
``` php
/** @var \Webklex\PHPIMAP\Folder $oFolder */

//Get all messages
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->all()->get();

//Get all messages from example@domain.com
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->from('example@domain.com')->get();

//Get all messages since march 15 2018
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->since('15.03.2018')->get();

//Get all messages within the last 5 days
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->since(now()->subDays(5))->get();
//Or for older laravel versions..
$aMessage = $oFolder->query()->since(\Carbon::now()->subDays(5))->get();

//Get all messages containing "hello world"
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('hello world')->get();

//Get all unseen messages containing "hello world"
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->unseen()->text('hello world')->get();

//Extended custom search query for all messages containing "hello world" 
//and have been received since march 15 2018
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('hello world')->since('15.03.2018')->get();
$aMessage = $oFolder->query()->Text('hello world')->Since('15.03.2018')->get();
$aMessage = $oFolder->query()->whereText('hello world')->whereSince('15.03.2018')->get();

// Build a custom search query
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()
->where([['TEXT', 'Hello world'], ['SINCE', \Carbon::parse('15.03.2018')]])
->get();
```

Available search aliases for a better code reading:
``` php
// Folder::search() is just an alias for Folder::query()
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->search()->text('hello world')->since('15.03.2018')->get();

// Folder::messages() is just an alias for Folder::query()
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->messages()->text('hello world')->since('15.03.2018')->get();

```
All available query / search methods can be found here: [Query::class](src/Query/WhereQuery.php)

Available search criteria:
- `ALL` &mdash; return all messages matching the rest of the criteria
- `ANSWERED` &mdash; match messages with the \\ANSWERED flag set
- `BCC` "string" &mdash; match messages with "string" in the Bcc: field
- `BEFORE` "date" &mdash; match messages with Date: before "date"
- `BODY` "string" &mdash; match messages with "string" in the body of the message
- `CC` "string" &mdash; match messages with "string" in the Cc: field
- `DELETED` &mdash; match deleted messages
- `FLAGGED` &mdash; match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
- `FROM` "string" &mdash; match messages with "string" in the From: field
- `KEYWORD` "string" &mdash; match messages with "string" as a keyword
- `NEW` &mdash; match new messages
- `OLD` &mdash; match old messages
- `ON` "date" &mdash; match messages with Date: matching "date"
- `RECENT` &mdash; match messages with the \\RECENT flag set
- `SEEN` &mdash; match messages that have been read (the \\SEEN flag is set)
- `SINCE` "date" &mdash; match messages with Date: after "date"
- `SUBJECT` "string" &mdash; match messages with "string" in the Subject:
- `TEXT` "string" &mdash; match messages with text "string"
- `TO` "string" &mdash; match messages with "string" in the To:
- `UNANSWERED` &mdash; match messages that have not been answered
- `UNDELETED` &mdash; match messages that are not deleted
- `UNFLAGGED` &mdash; match messages that are not flagged
- `UNKEYWORD` "string" &mdash; match messages that do not have the keyword "string"
- `UNSEEN` &mdash; match messages which have not been read yet

Further information:
- http://php.net/manual/en/function.imap-search.php
- https://tools.ietf.org/html/rfc1176
- https://tools.ietf.org/html/rfc1064
- https://tools.ietf.org/html/rfc822

#### Result limiting
Limiting the request emails:
``` php
/** @var \Webklex\PHPIMAP\Folder $oFolder */

//Get all messages for page 2 since march 15 2018 where each apge contains 10 messages
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->since('15.03.2018')->limit(10, 2)->get();
```

#### Pagination
Paginate a message collection:
``` php
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $aMessage->paginate();
```

View example for a paginated list:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $oFolder->search()
->since(\Carbon::now()->subDays(14))->get()
->paginate($perPage = 5, $page = null, $pageName = 'imap_blade_example');
```

``` html
<table>
    <thead>
        <tr>
            <th>UID</th>
            <th>Subject</th>
            <th>From</th>
            <th>Attachments</th>
        </tr>
    </thead>
    <tbody>
        <?php if($paginator->count() > 0): ?>
            <?php foreach($paginator as $oMessage): ?>
                <tr>
                    <td><?php echo $oMessage->getUid(); ?></td>
                    <td><?php echo $oMessage->getSubject(); ?></td>
                    <td><?php echo $oMessage->getFrom()[0]->mail; ?></td>
                    <td><?php echo $oMessage->getAttachments()->count() > 0 ? 'yes' : 'no'; ?></td>
                </tr>
            <?php endforeach: ?>
        <?php else: ?>
            <tr>
                <td colspan="4">No messages found</td>
            </tr>
        <?php endif: ?>
    </tbody>
</table>

{{$paginator->links()}}
```
> You can also paginate a Folder-, Attachment- or FlagCollection instance.


#### Fetch a specific message
Get a specific message by uid (Please note that the uid is not unique and can change):
``` php
/** @var \Webklex\PHPIMAP\Folder $oFolder */

/** @var \Webklex\PHPIMAP\Message $oMessage */
$oMessage = $oFolder->getMessage($uid = 1);
```

#### Message flags
Flag or "unflag" a message:
``` php
/** @var \Webklex\PHPIMAP\Message $oMessage */
$oMessage->setFlag(['Seen', 'Spam']);
$oMessage->unsetFlag('Spam');
```

Mark all messages as "read" while fetching:
``` php
/** @var \Webklex\PHPIMAP\Folder $oFolder */
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('Hello world')->markAsRead()->get();
```

Don't mark all messages as "read" while fetching:
``` php
/** @var \Webklex\PHPIMAP\Folder $oFolder */
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('Hello world')->leaveUnread()->get();
```

#### Attachments
Save message attachments:
``` php
/** @var \Webklex\PHPIMAP\Message $oMessage */

/** @var \Webklex\PHPIMAP\Support\AttachmentCollection $aAttachment */
$aAttachment = $oMessage->getAttachments();

$aAttachment->each(function ($oAttachment) {
    /** @var \Webklex\PHPIMAP\Attachment $oAttachment */
    $oAttachment->save();
});
```

#### Advanced fetching
Fetch messages without body fetching (decrease load):
``` php
/** @var \Webklex\PHPIMAP\Folder $oFolder */

/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereText('Hello world')->setFetchBody(false)->get();

/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereAll()->setFetchBody(false)->setFetchAttachment();
```

Fetch messages without body, flag and attachment fetching (decrease load):
``` php
/** @var \Webklex\PHPIMAP\Folder $oFolder */

/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereText('Hello world')
->setFetchFlags(false)
->setFetchBody(false)
->setFetchAttachment(false)
->get();

/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereAll()
->setFetchFlags(false)
->setFetchBody(false)
->setFetchAttachment(false)
->get();
```

#### Specials
Find the folder containing a message:
``` php
$oFolder = $aMessage->getContainingFolder();
```

## Support
If you encounter any problems or if you find a bug, please don't hesitate to create a new [issue](https://github.com/Webklex/php-imap/issues).
However please be aware that it might take some time to get an answer.

If you need **immediate** or **commercial** support, feel free to send me a mail at github@webklex.com. 

##### A little notice
If you write source code in your issue, please consider to format it correctly. This makes it so much nicer to read 
and people are more likely to comment and help :)

&#96;&#96;&#96; php

echo 'your php code...';

&#96;&#96;&#96;

will turn into:
``` php
echo 'your php code...';
```

### Features & pull requests
Everyone can contribute to this project. Every pull request will be considered but it can also happen to be declined. 
To prevent unnecessary work, please consider to create a [feature issue](https://github.com/Webklex/php-imap/issues/new?template=feature_request.md) 
first, if you're planning to do bigger changes. Of course you can also create a new [feature issue](https://github.com/Webklex/php-imap/issues/new?template=feature_request.md)
if you're just wishing a feature ;)

>Off topic, rude or abusive issues will be deleted without any notice.

## Documentation
### [Client::class](src/Client.php)
| Method              | Arguments                                                                       | Return            | Description                                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------- | :---------------: | ----------------------------------------------------------------------------------------------------------------------------  |
| setConfig           | array $config                                                                   | self              | Set the Client configuration. Take a look at `config/imap.php` for more inspiration.                                          |
| getConnection       | resource $connection                                                            | resource          | Get the current imap resource                                                                                                 |
| setReadOnly         | bool $readOnly                                                                  | self              | Set read only property and reconnect if it's necessary.                                                                       |
| isReadOnly          |                                                                                 | bool              | Determine if connection is in read only mode.                                                                                 |
| isConnected         |                                                                                 | bool              | Determine if connection was established.                                                                                      |
| checkConnection     |                                                                                 |                   | Determine if connection was established and connect if not.                                                                   |
| connect             | int $attempts                                                                   |                   | Connect to server.                                                                                                            |
| disconnect          |                                                                                 |                   | Disconnect from server.                                                                                                       |
| getFolder           | string $folder_name, int $attributes = 32, int or null $delimiter               | Folder            | Get a Folder instance by name                                                                                                 |
| getFolders          | bool $hierarchical, string or null $parent_folder                               | FolderCollection  | Get folders list. If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.  |
| openFolder          | Folder $folder, integer $attempts                                               |                   | Open a given folder.                                                                                                          |
| createFolder        | string $name                                                                    | boolean           | Create a new folder.                                                                                                          |
| renameFolder        | string $old_name, string $new_name                                              | boolean           | Rename a folder. |
| deleteFolder        | string $name                                                                    | boolean           | Delete a folder. |
| getMessages         | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags      | MessageCollection | Get messages from folder.                                                                                                     |
| getUnseenMessages   | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags      | MessageCollection | Get Unseen messages from folder.                                                                                              |
| searchMessages      | array $where, Folder $folder, $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment, bool $fetch_flags | MessageCollection | Get specific messages from a given folder.                                                                                    |
| getQuota            |                                                                                 | array             | Retrieve the quota level settings, and usage statics per mailbox                                                              |
| getQuotaRoot        | string $quota_root                                                              | array             | Retrieve the quota settings per user                                                                                          |
| countMessages       |                                                                                 | int               | Gets the number of messages in the current mailbox                                                                            |
| countRecentMessages |                                                                                 | int               | Gets the number of recent messages in current mailbox                                                                         |
| getAlerts           |                                                                                 | array             | Returns all IMAP alert messages that have occurred                                                                            |
| getErrors           |                                                                                 | array             | Returns all of the IMAP errors that have occurred                                                                             |
| getLastError        |                                                                                 | string            | Gets the last IMAP error that occurred during this page request                                                               |
| expunge             |                                                                                 | bool              | Delete all messages marked for deletion                                                                                       |
| checkCurrentMailbox |                                                                                 | object            | Check current mailbox                                                                                                         |

### [Message::class](src/Message.php)
| Method          | Arguments                     | Return               | Description                            |
| --------------- | ----------------------------- | :------------------: | -------------------------------------- |
| parseBody       |                               | Message              | Parse the Message body                 |
| delete          |                               |                      | Delete the current Message             |
| restore         |                               |                      | Restore a deleted Message              |
| copy            | string $mailbox, int $options |                      | Copy the current Messages to a mailbox |
| move            | string $mailbox, int $options |                      | Move the current Messages to a mailbox |
| getContainingFolder | Folder or null $folder    | Folder or null       | Get the folder containing the message  |
| moveToFolder    | string $mailbox, int $options |                      | Move the Message into an other Folder  |
| setFlag         | string or array $flag         | boolean              | Set one or many flags                  |
| unsetFlag       | string or array $flag         | boolean              | Unset one or many flags                |
| hasTextBody     |                               |                      | Check if the Message has a text body   |
| hasHTMLBody     |                               |                      | Check if the Message has a html body   |
| getTextBody     |                               | string               | Get the Message text body              |
| getHTMLBody     |                               | string               | Get the Message html body              |
| getAttachments  |                               | AttachmentCollection | Get all message attachments            |
| hasAttachments  |                               | boolean              | Checks if there are any attachments present            |
| getClient       |                               | Client               | Get the current Client instance        |
| getUid          |                               | string               | Get the current UID                    |
| getFetchOptions |                               | string               | Get the current fetch option           |
| getMsglist      |                               | integer              | Get the current message list           |
| getHeaderInfo   |                               | object               | Get the current header_info object     |
| getHeader       |                               | string               | Get the current raw header             |
| getMessageId    |                               | integer              | Get the current message ID             |
| getMessageNo    |                               | integer              | Get the current message number         |
| getPriority     |                               | integer              | Get the current message priority       |
| getSubject      |                               | string               | Get the current subject                |
| getReferences   |                               | mixed                | Get any potentially present references |
| getDate         |                               | Carbon               | Get the current date object            |
| getFrom         |                               | array                | Get the current from information       |
| getTo           |                               | array                | Get the current to information         |
| getCc           |                               | array                | Get the current cc information         |
| getBcc          |                               | array                | Get the current bcc information        |
| getReplyTo      |                               | array                | Get the current reply to information   |
| getInReplyTo    |                               | string               | Get the current In-Reply-To            |
| getSender       |                               | array                | Get the current sender information     |
| getBodies       |                               | mixed                | Get the current bodies                 |
| getRawBody      |                               | mixed                | Get the current raw message body       |
| getFlags        |                               | FlagCollection       | Get the current message flags          |
| is              |                               | boolean              | Does this message match another one?   |

### [Folder::class](src/Folder.php)
| Method            | Arguments                                                                           | Return            | Description                                    |
| ----------------- | ----------------------------------------------------------------------------------- | :---------------: | ---------------------------------------------- |
| hasChildren       |                                                                                     | bool              | Determine if folder has children.              |
| setChildren       | array $children                                                                     | self              | Set children.                                  |
| getMessage        | integer $uid, integer or null $msglist, int or null fetch_options, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags | Message           | Get a specific message from folder.            |
| getMessages       | string $criteria, int or null $fetch_options, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags                                                  | MessageCollection | Get messages from folder.                      |
| getUnseenMessages | string $criteria, int or null $fetch_options, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags                                                  | MessageCollection | Get Unseen messages from folder.               |
| searchMessages    | array $where, int or null $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment, bool $fetch_flags                     | MessageCollection | Get specific messages from a given folder.     |
| delete            |                                                                                     |                   | Delete the current Mailbox                     |
| move              | string $mailbox                                                                     |                   | Move or Rename the current Mailbox             |
| getStatus         | integer $options                                                                    | object            | Returns status information on a mailbox        |
| appendMessage     | string $message, string $options, string $internal_date                             | bool              | Append a string message to the current mailbox |
| getClient         |                                                                                     | Client            | Get the current Client instance                |
| query             | string $charset = 'UTF-8'                                                           | WhereQuery        | Get the current Client instance                |
| messages          | string $charset = 'UTF-8'                                                           | WhereQuery        | Alias for Folder::query()                      |
| search            | string $charset = 'UTF-8'                                                           | WhereQuery        | Alias for Folder::query()                      |
      
### [Query::class](src/Query/WhereQuery.php)
| Method             | Arguments                         | Return            | Description                                    |
| ------------------ | --------------------------------- | :---------------: | ---------------------------------------------- |
| where              | mixed $criteria, $value = null    | WhereQuery        | Add new criteria to the current query |
| orWhere            | Closure $$closure                 | WhereQuery        | If supported you can perform extended search requests |
| andWhere           | Closure $$closure                 | WhereQuery        | If supported you can perform extended search requests |
| all                |                                   | WhereQuery        | Select all available messages |
| answered           |                                   | WhereQuery        | Select answered messages |
| answered           |                                   | WhereQuery        | Select answered messages |
| deleted            |                                   | WhereQuery        | Select deleted messages |
| new                |                                   | WhereQuery        | Select new messages |
| old                |                                   | WhereQuery        | Select old messages |
| recent             |                                   | WhereQuery        | Select recent messages |
| seen               |                                   | WhereQuery        | Select seen messages |
| unanswered         |                                   | WhereQuery        | Select unanswered messages |
| undeleted          |                                   | WhereQuery        | Select undeleted messages |
| unflagged          |                                   | WhereQuery        | Select unflagged messages |
| unseen             |                                   | WhereQuery        | Select unseen messages |
| unkeyword          | string $value                     | WhereQuery        | Select messages matching a given unkeyword |
| to                 | string $value                     | WhereQuery        | Select messages matching a given receiver (To:) |
| text               | string $value                     | WhereQuery        | Select messages matching a given text body |
| subject            | string $value                     | WhereQuery        | Select messages matching a given subject |
| since              | string $value                     | WhereQuery        | Select messages since a given date |
| on                 | string $value                     | WhereQuery        | Select messages on a given date |
| keyword            | string $value                     | WhereQuery        | Select messages matching a given keyword |
| from               | string $value                     | WhereQuery        | Select messages matching a given sender (From:) |
| flagged            | string $value                     | WhereQuery        | Select messages matching a given flag |
| cc                 | string $value                     | WhereQuery        | Select messages matching a given receiver (CC:) |
| body               | string $value                     | WhereQuery        | Select messages matching a given HTML body |
| before             | string $value                     | WhereQuery        | Select messages before a given date |
| bcc                | string $value                     | WhereQuery        | Select messages matching a given receiver (BCC:) |
| get                |                                   | MessageCollection | Fetch messages with the current query |
| limit              | integer $limit, integer $page = 1 | WhereQuery        | Limit the amount of messages being fetched |
| setFetchOptions    | boolean $fetch_options            | WhereQuery        | Set the fetch options |
| setFetchBody       | boolean $fetch_body               | WhereQuery        | Set the fetch body option |
| getFetchAttachment | boolean $fetch_attachment         | WhereQuery        | Set the fetch attachment option |
| setFetchFlags      | boolean $fetch_flags              | WhereQuery        | Set the fetch flags option |
| leaveUnread        |                                   | WhereQuery        | Don't mark all messages as "read" while fetching:  |
| markAsRead         |                                   | WhereQuery        | Mark all messages as "read" while fetching |
           
### [Attachment::class](src/Attachment.php)
| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getContent     |                                | string or null | Get attachment content                                 |     
| getMimeType    |                                | string or null | Get attachment mime type                               |     
| getExtension   |                                | string or null | Get a guessed attachment extension                     |     
| getName        |                                | string or null | Get attachment name                                    |        
| getType        |                                | string or null | Get attachment type                                    |        
| getDisposition |                                | string or null | Get attachment disposition                             | 
| getContentType |                                | string or null | Get attachment content type                            | 
| getImgSrc      |                                | string or null | Get attachment image source as base64 encoded data url |      
| save           | string $path, string $filename | boolean        | Save the attachment content to your filesystem         |      

### [MessageCollection::class](src/Support/MessageCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [FlagCollection::class](src/Support/FlagCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [AttachmentCollection::class](src/Support/AttachmentCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [FolderCollection::class](src/Support/FolderCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### Known issues
| Error                                                                     | Solution                                                   |
| ------------------------------------------------------------------------- | ---------------------------------------------------------- |
| Kerberos error: No credentials cache file found (try running kinit) (...) | Uncomment "DISABLE_AUTHENTICATOR" inside `config/imap.php` | 
| imap_fetchbody() expects parameter 4 to be long, string given (...)       | Make sure that `imap.options.fetch` is a valid integer     | 
| Use of undefined constant FT_UID - assumed 'FT_UID' (...)                 | Please take a look at [#14](https://github.com/Webklex/php-imap/issues/14) [#30](https://github.com/Webklex/php-imap/issues/30)     | 
| DateTime::__construct(): Failed to parse time string (...)                | Please report any new invalid timestamps to [#45](https://github.com/Webklex/php-imap/issues/45)  | 
| imap_open(): Couldn't open (...) Please log in your web browser: (...)    | In order to use IMAP on some services (such as Gmail) you need to enable it first. [Google help page]( https://support.google.com/mail/answer/7126229?hl=en) |
| imap_headerinfo(): Bad message number                                     | This happens if no Message number is available. Please make sure Message::parseHeader() has run before |

## Milestones & upcoming features
* Wiki!!

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email github@webklex.com instead of using the issue tracker.

## Credits

- [Webklex][link-author]
- [All Contributors][link-contributors]


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/Webklex/php-imap.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Webklex/php-imap/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/Webklex/php-imap.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Webklex/php-imap.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Webklex/php-imap.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Webklex/php-imap
[link-travis]: https://travis-ci.org/Webklex/php-imap
[link-scrutinizer]: https://scrutinizer-ci.com/g/Webklex/php-imap/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Webklex/php-imap
[link-downloads]: https://packagist.org/packages/Webklex/php-imap
[link-author]: https://github.com/webklex
[link-contributors]: https://github.com/Webklex/php-imap/graphs/contributors
[link-jetbrains]: https://www.jetbrains.com
