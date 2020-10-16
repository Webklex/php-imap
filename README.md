# IMAP Library for PHP

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]][link-license]
[![Build Status][ico-build]][link-scrutinizer] 
[![Total Downloads][ico-downloads]][link-downloads]
[![Hits][ico-hits]][link-hits]


## Description
PHP-IMAP is a wrapper for common IMAP communication without the need to have the php-imap module installed / enabled.
The protocol is completely integrated and therefore supports IMAP IDLE operation and the "new" oAuth authentication 
process as well.
You can enable the `php-imap` module in order to handle edge cases, improve message decoding quality and is required if 
you want to use legacy protocols such as pop3.

Wiki: [webklex/php-imap/wiki](https://github.com/Webklex/php-imap/wiki)

Laravel wrapper: [webklex/laravel-imap](https://github.com/Webklex/laravel-imap)


## Table of Contents
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Basic usage example](#basic-usage-example)
    - [Folder / Mailbox](#folder--mailbox)
    - [oAuth](#oauth)
    - [Idle](#idle)
    - [Search](#search-for-messages)
    - [Counting messages](#counting-messages)
    - [Result limiting](#result-limiting)
    - [Pagination](#pagination)
    - [View examples](#view-examples)
    - [Fetch a specific message](#fetch-a-specific-message)
    - [Message flags](#message-flags)
    - [Attachments](#attachments)
    - [Advanced fetching](#advanced-fetching)
    - [Events](#events)
    - [Masking](#masking)
    - [Specials](#specials)
- [Support](#support)
- [Documentation](#documentation)
  - [Client::class](#clientclass)
  - [Message::class](#messageclass)
  - [Folder::class](#folderclass)
  - [Query::class](#queryclass)
  - [Attachment::class](#attachmentclass) 
  - [Mask::class](#maskclass) 
    - [MessageMask::class](#messagemaskclass) 
    - [AttachmentMask::class](#attachmentmaskclass) 
  - [MessageCollection::class](#messagecollectionclass) 
  - [AttachmentCollection::class](#attachmentcollectionclass) 
  - [FolderCollection::class](#foldercollectionclass) 
  - [FlagCollection::class](#flagcollectionclass) 
- [Known issues](#known-issues)
- [Milestones & upcoming features](#milestones--upcoming-features)
- [Security](#security)
- [Credits](#credits)
- [License](#license)


## Installation
1.) Install decoding modules:
```shell script
sudo apt-get install php*-mbstring php*-mcrypt && sudo apache2ctl graceful
```

1.1.) (optional) Install php-imap module if you are having encoding problems:
```shell script
sudo apt-get install php*-imap && sudo apache2ctl graceful
```

You might also want to check `phpinfo()` if the extensions are enabled.

2.) Now install the PHP-IMAP package by running the following command:
```shell script
composer require webklex/php-imap
```

3.) Create your own custom config file like [config/imap.php](src/config/imap.php):


## Configuration
Supported protocols:
- `imap` &mdash; Use IMAP [default]
- `legacy-imap` &mdash; Use the php imap module instead
- `pop3` &mdash; Use POP3
- `nntp` &mdash; Use NNTP

The following encryption methods are supported:
- `false` &mdash; Disable encryption 
- `ssl` &mdash; Use SSL
- `tls` &mdash; Use TLS
- `starttls` &mdash; Use STARTTLS (alias TLS) (legacy only)
- `notls` &mdash; Use NoTLS (legacy only)

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
     - `authentication` &mdash; imap authentication method. Use `oauth` to use oAuth for Google, etc.
 - `date_format` &mdash; The default date format is used to convert any given Carbon::class object into a valid date string. (`d-M-Y`, `d-M-y`, `d M y`)
 - `options` &mdash; additional fetch options
   - `delimiter` &mdash; you can use any supported char such as ".", "/", etc
   - `fetch` &mdash; `IMAP::FT_UID` (message marked as read by fetching the message body) or `IMAP::FT_PEEK` (fetch the message without setting the "seen" flag)
   - `fetch_body` &mdash; If set to `false` all messages will be fetched without the body and any potential attachments
   - `fetch_flags` &mdash;  If set to `false` all messages will be fetched without any flags
   - `message_key` &mdash; Message key identifier option
   - `fetch_order` &mdash; Message fetch order
   - `common_folders` &mdash; Default folder locations and paths assumed if none is provided
   - `open` &mdash; special configuration for imap_open()
     - `DISABLE_AUTHENTICATOR` &mdash; disable authentication properties.
   - `decoder` &mdash; Currently only the message and attachment decoder can be set
   - `events` &mdash; Default [event handling](#events) config
   - `masks` &mdash; Default [masking](#masking) config
     - `message` &mdash; Default message mask
     - `attachment` &mdash; Default attachment mask


## Usage
#### Basic usage example
This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not be
tested in real life and is only meant to gives an impression on how things work.

```php
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;

$cm = new ClientManager('path/to/config/imap.php');

// or use an array of options instead
$cm = new ClientManager($options = []);

/** @var \Webklex\PHPIMAP\Client $client */
$client = $cm->account('account_identifier');

// or create a new instance manually        
$client = $cm->make([
    'host'          => 'somehost.com',
    'port'          => 993,
    'encryption'    => 'ssl',
    'validate_cert' => true,
    'username'      => 'username',
    'password'      => 'password',
    'protocol'      => 'imap'
]);

//Connect to the IMAP Server
$client->connect();

//Get all Mailboxes
/** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
$folders = $client->getFolders();

//Loop through every Mailbox
/** @var \Webklex\PHPIMAP\Folder $folder */
foreach($folders as $folder){

    //Get all Messages of the current Mailbox $folder
    /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
    $messages = $folder->messages()->all()->get();
    
    /** @var \Webklex\PHPIMAP\Message $message */
    foreach($messages as $message){
        echo $message->getSubject().'<br />';
        echo 'Attachments: '.$message->getAttachments()->count().'<br />';
        echo $message->getHTMLBody();
        
        //Move the current Message to 'INBOX.read'
        if($message->moveToFolder('INBOX.read') == true){
            echo 'Message has ben moved';
        }else{
            echo 'Message could not be moved';
        }
    }
}
```


#### Folder / Mailbox
List all available folders:
```php
/** @var \Webklex\PHPIMAP\Client $client */

/** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
$folders = $client->getFolders();
```

Get a specific folder:
```php
/** @var \Webklex\PHPIMAP\Client $client */

/** @var \Webklex\PHPIMAP\Folder $folder */
$folder = $client->getFolder('INBOX.name');
```


#### oAuth
Please take a look at [the wiki article](https://github.com/Webklex/php-imap/wiki/Google-Mail---Gmail) for gmail / google mail setup.

Basic oAuth example:
```php
use Webklex\PHPIMAP\Clientmanager;

$cm = new Clientmanager();

/** @var \Webklex\PHPIMAP\Client $client */
$client = $cm->make([
    'host' => 'imap.gmail.com',
    'port' => 993,
    'encryption' => 'ssl',
    'validate_cert' => true,
    'username' => 'example@gmail.com',
    'password' => 'ACCESS-TOKEN',
    'authentication' => "oauth",
    'protocol' => 'imap'
]);

//Connect to the IMAP Server
$client->connect();
```


#### Idle
Every time a new message is received, the server will notify the client and return the new message.
```php
/** @var \Webklex\PHPIMAP\Folder $folder */
$folder->idle(function($message){
    echo $message->subject."\n";
});
```


#### Search for messages
Search for specific emails:
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

//Get all messages
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->all()->get();

//Get all messages from example@domain.com
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->from('example@domain.com')->get();

//Get all messages since march 15 2018
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->since('15.03.2018')->get();

//Get all messages within the last 5 days
$messages = $folder->query()->since(\Carbon\Carbon::now()->subDays(5))->get();

//Get all messages containing "hello world"
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->text('hello world')->get();

//Get all unseen messages containing "hello world"
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->unseen()->text('hello world')->get();

//Extended custom search query for all messages containing "hello world" 
//and have been received since march 15 2018
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->text('hello world')->since('15.03.2018')->get();
$messages = $folder->query()->Text('hello world')->Since('15.03.2018')->get();
$messages = $folder->query()->whereText('hello world')->whereSince('15.03.2018')->get();

// Build a custom search query
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()
->where([['TEXT', 'Hello world'], ['SINCE', \Carbon\Carbon::parse('15.03.2018')]])
->get();

//!EXPERIMENTAL!
//Get all messages NOT containing "hello world"
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->notText('hello world')->get();
$messages = $folder->query()->not_text('hello world')->get();
$messages = $folder->query()->not()->text('hello world')->get();

//Get all messages by custom search criteria
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->where(["CUSTOM_FOOBAR" => "fooBar"]])->get();
```

Available search aliases for a better code reading:
```php
// Folder::search() is just an alias for Folder::query()
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->search()->text('hello world')->since('15.03.2018')->get();

// Folder::messages() is just an alias for Folder::query()
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->messages()->text('hello world')->since('15.03.2018')->get();
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
- `NOT` &mdash; not matching
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
- https://gist.github.com/martinrusev/6121028


#### Result limiting
Limiting the request emails:
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

//Get all messages for page 2 since march 15 2018 where each page contains 10 messages
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->since('15.03.2018')->limit(10, 2)->get();
```


#### Counting messages
Count all available messages matching the current search criteria:
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

//Count all messages
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$count = $folder->query()->all()->count();

//Count all messages since march 15 2018
$count = $folder->query()->since('15.03.2018')->count();
```


#### Pagination
Paginate a query:
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $folder->query()->since('15.03.2018')->paginate();
```
Paginate a message collection:
```php
/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $messages->paginate();
```
View example for a paginated list:
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $folder->search()
->since(\Carbon\Carbon::now()->subDays(14))->get()
->paginate($perPage = 5, $page = null, $pageName = 'imap_blade_example');
```

```html
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
            <?php foreach($paginator as $message): ?>
            <tr>
                <td><?php echo $message->getUid(); ?></td>
                <td><?php echo $message->getSubject(); ?></td>
                <td><?php echo $message->getFrom()[0]->mail; ?></td>
                <td><?php echo $message->getAttachments()->count() > 0 ? 'yes' : 'no'; ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">No messages found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php echo $paginator->links(); ?>
```
> You can also paginate a Folder-, Attachment- or FlagCollection instance.


#### View examples
You can find a few blade examples under [/examples](examples).


#### Fetch a specific message
Get a specific message by uid (Please note that the uid is not unique and can change):
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

/** @var \Webklex\PHPIMAP\Message $message */
$message = $folder->query()->getMessage($msgn = 1);
```


#### Message flags
Flag or "unflag" a message:
```php
/** @var \Webklex\PHPIMAP\Message $message */
$message->setFlag(['Seen', 'Spam']);
$message->unsetFlag('Spam');
```

Mark all messages as "read" while fetching:
```php
/** @var \Webklex\PHPIMAP\Folder $oFolder */
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('Hello world')->markAsRead()->get();
```

Don't mark all messages as "read" while fetching:
```php
/** @var \Webklex\PHPIMAP\Folder $oFolder */
/** @var \Webklex\PHPIMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('Hello world')->leaveUnread()->get();
```


#### Attachments
Save message attachments:
```php
/** @var \Webklex\PHPIMAP\Message $message */

/** @var \Webklex\PHPIMAP\Support\AttachmentCollection $attachments */
$attachments = $message->getAttachments();

$attachments->each(function ($attachment) {
    /** @var \Webklex\PHPIMAP\Attachment $attachment */
    $attachment->save("/some/path/");
});
```


#### Advanced fetching
Fetch messages without body fetching (decrease load):
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->whereText('Hello world')->setFetchBody(false)->get();

/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->whereAll()->setFetchBody(false)->get();
```

Fetch messages without body, flag and attachment fetching (decrease load):
```php
/** @var \Webklex\PHPIMAP\Folder $folder */

/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->whereText('Hello world')
->setFetchFlags(false)
->setFetchBody(false)
->get();

/** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
$messages = $folder->query()->whereAll()
->setFetchFlags(false)
->setFetchBody(false)
->get();
```


#### Events
The following events are available:
- `Webklex\PHPIMAP\Events\MessageNewEvent($message)` &mdash; can get triggered by `Folder::idle`
- `Webklex\PHPIMAP\Events\MessageDeletedEvent($message)` &mdash; triggered by `Message::delete`
- `Webklex\PHPIMAP\Events\MessageRestoredEvent($message)` &mdash; triggered by `Message::restore`
- `Webklex\PHPIMAP\Events\MessageMovedEvent($old_message, $new_message)` &mdash; triggered by `Message::move`
- `Webklex\PHPIMAP\Events\MessageCopiedEvent($old_message, $new_message)` &mdash; triggered by `Message::copy`
- `Webklex\PHPIMAP\Events\FlagNewEvent($flag)` &mdash; triggered by `Message::setFlag`
- `Webklex\PHPIMAP\Events\FlagDeletedEvent($flag)` &mdash; triggered by `Message::unsetFlag`
- `Webklex\PHPIMAP\Events\FolderNewEvent($folder)` &mdash; can get triggered by `Client::createFolder`
- `Webklex\PHPIMAP\Events\FolderDeletedEvent($folder)` &mdash; triggered by `Folder::delete`
- `Webklex\PHPIMAP\Events\FolderMovedEvent($old_folder, $new_folder)` &mdash; triggered by `Folder::move`

Create and register your own custom event:
```php
class CustomMessageNewEvent extends Webklex\PHPIMAP\Events\MessageNewEvent {

    /**
     * Create a new event instance.
     * @var \Webklex\PHPIMAP\Message[] $messages
     * @return void
     */
    public function __construct($messages) {
        $this->message = $messages[0];
        echo "New message: ".$this->message->subject."\n";
    }
}

/** @var \Webklex\PHPIMAP\Client $client */
$client->setEvent("message", "new", CustomMessageNewEvent::class);
```
..or set it in your config file under `events.message.new`.


#### Masking
PHP-IMAP already comes with two default masks [MessageMask::class](#messagemaskclass) and [AttachmentMask::class](#attachmentmaskclass).

The masked instance has to be called manually and is designed to add custom functionality.

You can call the default mask by calling the mask method without any arguments.
```php
/** @var \Webklex\PHPIMAP\Message $message */
$mask = $message->mask();
```

There are several methods available to set the default mask:
```php
/** @var \Webklex\PHPIMAP\Client $client */
/** @var \Webklex\PHPIMAP\Message $message */

$message_mask = \Webklex\PHPIMAP\Support\Masks\MessageMask::class;

$client->setDefaultMessageMask($message_mask);
$message->setMask($message_mask);
$mask = $message->mask($message_mask);
```
The last one wont set the mask but generate a masked instance using the provided mask.

You could also set the default masks inside your `config/imap.php` file under `masks`.

You can also apply a mask on [attachments](#attachmentclass):
```php
/** @var \Webklex\PHPIMAP\Client $client */
/** @var \Webklex\PHPIMAP\Attachment $attachment */
$attachment_mask = \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class;

$client->setDefaultAttachmentMask($attachment_mask);
$attachment->setMask($attachment_mask);
$mask = $attachment->mask($attachment_mask);
```

If you want to implement your own mask just extend [MessageMask::class](#messagemaskclass), [AttachmentMask::class](#attachmentmaskclass)
or [Mask::class](#maskclass) and implement your desired logic:

```php
/** @var \Webklex\PHPIMAP\Message $message */
class CustomMessageMask extends \Webklex\PHPIMAP\Support\Masks\MessageMask {

    /**
     * New custom method which can be called through a mask
     * @return string
     */
    public function token(){
        return implode('-', [$this->message_id, $this->uid, $this->message_no]);
    }
}

$mask = $message->mask(CustomMessageMask::class);

echo $mask->token().'@'.$mask->uid;
```

Additional examples can be found here:
- [Custom message mask](https://github.com/Webklex/php-imap/blob/master/examples/custom_message_mask.php)
- [Custom attachment mask](https://github.com/Webklex/php-imap/blob/master/examples/custom_attachment_mask.php)


#### Specials
Find the folder containing a message:
```php
/** @var \Webklex\PHPIMAP\Message $message */
$folder = $message->getFolder();
```


## Support
If you encounter any problems or if you find a bug, please don't hesitate to create a new [issue](https://github.com/Webklex/php-imap/issues).
However please be aware that it might take some time to get an answer.
Off topic, rude or abusive issues will be deleted without any notice.

If you need **immediate** or **commercial** support, feel free to send me a mail at github@webklex.com. 


##### A little notice
If you write source code in your issue, please consider to format it correctly. This makes it so much nicer to read 
and people are more likely to comment and help :)

&#96;&#96;&#96; php

echo 'your php code...';

&#96;&#96;&#96;

will turn into:
```php
echo 'your php code...';
```


### Features & pull requests
Everyone can contribute to this project. Every pull request will be considered but it can also happen to be declined. 
To prevent unnecessary work, please consider to create a [feature issue](https://github.com/Webklex/php-imap/issues/new?template=feature_request.md) 
first, if you're planning to do bigger changes. Of course you can also create a new [feature issue](https://github.com/Webklex/php-imap/issues/new?template=feature_request.md)
if you're just wishing a feature ;)


## Documentation
### [Client::class](src/Client.php)
| Method                    | Arguments                                                                       | Return            | Description                                                                                                                   |
| ------------------------- | ------------------------------------------------------------------------------- | :---------------: | ----------------------------------------------------------------------------------------------------------------------------  |
| setConfig                 | array $config                                                                   | self              | Set the Client configuration. Take a look at `config/imap.php` for more inspiration.                                          |
| getConnection             | resource $connection                                                            | resource          | Get the current imap resource                                                                                                 |
| isConnected               |                                                                                 | bool              | Determine if connection was established.                                                                                      |
| checkConnection           |                                                                                 |                   | Determine if connection was established and connect if not.                                                                   |
| connect                   |                                                                                 |                   | Connect to server.                                                                                                            |
| reconnect                 |                                                                                 |                   | Terminate and reconnect to server.                                                                                                            |
| disconnect                |                                                                                 |                   | Disconnect from server.                                                                                                       |
| getFolder                 | string $folder_name, $delimiter = null | Folder            | Get a Folder instance by name or path                                                                                                |
| getFolders                | bool $hierarchical, string or null $parent_folder                               | FolderCollection  | Get folders list. If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.  |
| openFolder                | string or Folder $folder, integer $attempts                                     |                   | Open a given folder.                                                                                                          |
| createFolder              | string $name                                                                    | boolean           | Create a new folder.                                                                                                          |
| getQuota                  |                                                                                 | array             | Retrieve the quota level settings, and usage statics per mailbox                                                              |
| getQuotaRoot              | string $quota_root                                                              | array             | Retrieve the quota settings per user                                                                                          |
| expunge                   |                                                                                 | bool              | Delete all messages marked for deletion                                                                                       |
| setTimeout                | string or int $type, int $timeout                                               | boolean           | Set the timeout for certain imap operations: 1: Open, 2: Read, 3: Write, 4: Close                                             |
| getTimeout                | string or int $type                                                             | int               | Check current mailbox                                                                                                         |
| setDefaultMessageMask     | string $mask                                                                    | self              | Set the default message mask class                                                                                            |
| getDefaultMessageMask     |                                                                                 | string            | Get the current default message mask class name                                                                               | 
| setDefaultAttachmentMask  | string $mask                                                                    | self              | Set the default attachment mask class                                                                                         |
| getDefaultAttachmentMask  |                                                                                 | string            | Get the current default attachment mask class name                                                                            | 
| getFolderPath             |                                                                                 | string            | Get the current folder path                                                                                                   | 


### [Message::class](src/Message.php)
| Method          | Arguments                     | Return               | Description                            |
| --------------- | ----------------------------- | :------------------: | -------------------------------------- |
| parseBody       |                               | Message              | Parse the Message body                 |
| delete          | boolean $expunge              | boolean              | Delete the current Message             |
| restore         | boolean $expunge              | boolean              | Restore a deleted Message              |
| copy            | string $mailbox               | boolean              | Copy the current Messages to a mailbox |
| move            | string $mailbox, boolean $expunge  | boolean         | Move the current Messages to a mailbox |
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
| getMessageId    |                               | string               | Get the current message ID             |
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
| getStructure    |                               | object               | The raw message structure              |
| getFolder       |                               | Folder               | The current folder                     |
| mask            | string $mask = null           | Mask                 | Get a masked instance                  |
| setMask         | string $mask                  | Message              | Set the mask class                     |
| getMask         |                               | string               | Get the current mask class name        |


### [Folder::class](src/Folder.php)
| Method            | Arguments                                                                           | Return            | Description                                    |
| ----------------- | ----------------------------------------------------------------------------------- | :---------------: | ---------------------------------------------- |
| hasChildren       |                                                                                     | bool              | Determine if folder has children.              |
| setChildren       | array $children                                                                     | self              | Set children.                                  |
| delete            |                                                                                     |                   | Delete the current Mailbox                     |
| subscribe         |                                                                                     |                   | Subscribe to the current Mailbox               |
| unsubscribe       |                                                                                     |                   | Unsubscribe from the current Mailbox           |
| idle              | callable $callback(Message $new_message)                                            |                   | Idle the current folder                        |
| move              | string $mailbox                                                                     |                   | Move or Rename the current Mailbox             |
| rename            | string $mailbox                                                                     |                   | Move or Rename the current Mailbox             |
| getStatus         |                                                                                     | array             | Returns status information on the current mailbox |
| examine           |                                                                                     | array             | Returns status information on the current mailbox |
| appendMessage     | string $message, string $options, string $internal_date                             | bool              | Append a string message to the current mailbox |
| getClient         |                                                                                     | Client            | Get the current Client instance                |
| query             | string $charset = 'UTF-8'                                                           | WhereQuery        | Get the current Client instance                |
| messages          | string $charset = 'UTF-8'                                                           | WhereQuery        | Alias for Folder::query()                      |
| search            | string $charset = 'UTF-8'                                                           | WhereQuery        | Alias for Folder::query()                      |


### [Query::class](src/Query/WhereQuery.php)
| Method             | Arguments                         | Return            | Description                                    |
| ------------------ | --------------------------------- | :---------------: | ---------------------------------------------- |
| where              | mixed $criteria, $value = null    | WhereQuery        | Add new criteria to the current query |
| orWhere            | Closure $closure                  | WhereQuery        | If supported you can perform extended search requests |
| andWhere           | Closure $closure                  | WhereQuery        | If supported you can perform extended search requests |
| all                |                                   | WhereQuery        | Select all available messages |
| answered           |                                   | WhereQuery        | Select answered messages |
| deleted            |                                   | WhereQuery        | Select deleted messages |
| new                |                                   | WhereQuery        | Select new messages |
| not                |                                   | WhereQuery        | Not select messages |
| old                |                                   | WhereQuery        | Select old messages |
| recent             |                                   | WhereQuery        | Select recent messages |
| seen               |                                   | WhereQuery        | Select seen messages |
| unanswered         |                                   | WhereQuery        | Select unanswered messages |
| undeleted          |                                   | WhereQuery        | Select undeleted messages |
| unflagged          |                                   | WhereQuery        | Select unflagged messages |
| unseen             |                                   | WhereQuery        | Select unseen messages |
| noXSpam            |                                   | WhereQuery        | Select as no xspam flagged messages |
| isXSpam            |                                   | WhereQuery        | Select as xspam flagged messages |
| language           | string $value                     | WhereQuery        | Select messages matching a given language |
| unkeyword          | string $value                     | WhereQuery        | Select messages matching a given unkeyword |
| messageId          | string $value                     | WhereQuery        | Select messages matching a given message id |
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
| count              |                                   | integer           | Count all available messages matching the current search criteria |
| get                |                                   | MessageCollection | Fetch messages with the current query |
| limit              | integer $limit, integer $page = 1 | WhereQuery        | Limit the amount of messages being fetched |
| setFetchOptions    | boolean $fetch_options            | WhereQuery        | Set the fetch options |
| setFetchBody       | boolean $fetch_body               | WhereQuery        | Set the fetch body option |
| setFetchFlags      | boolean $fetch_flags              | WhereQuery        | Set the fetch flags option |
| leaveUnread        |                                   | WhereQuery        | Don't mark all messages as "read" while fetching:  |
| markAsRead         |                                   | WhereQuery        | Mark all messages as "read" while fetching |  
| paginate           | int $perPage = 5, $page = null, $pageName = 'imap_page' | LengthAwarePaginator | Paginate the current query. |
     
           
### [Attachment::class](src/Attachment.php)
| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getContent     |                                | string or null | Get attachment content                                 |     
| getMimeType    |                                | string or null | Get attachment mime type                               |     
| getExtension   |                                | string or null | Get a guessed attachment extension                     |     
| getId          |                                | string or null | Get attachment id                                      |        
| getName        |                                | string or null | Get attachment name                                    |        
| getContent     |                                | string or null | Get attachment content                                 |                
| setSize        |                                | int or null    | Get attachment size                                    |        
| getType        |                                | string or null | Get attachment type                                    |        
| getDisposition |                                | string or null | Get attachment disposition                             | 
| getContentType |                                | string or null | Get attachment content type                            | 
| save           | string $path, string $filename | boolean        | Save the attachment content to your filesystem         |    
| mask           | string $mask = null            | Mask           | Get a masked instance                                  |
| setMask        | string $mask                   | Attachment     | Set the mask class                                     |
| getMask        |                                | string         | Get the current mask class name                        |  


### [Mask::class](src/Support/Masks/Mask.php)
| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getParent      |                                | Masked parent  | Get the masked parent object                           |     
| getAttributes  |                                | array          | Get all cloned attributes                              |  
| __get          |                                | mixed          | Access any cloned parent attribute                     |  
| __set          |                                | mixed          | Set any cloned parent attribute                        |  
| __inherit      |                                | mixed          | All public methods of the given parent are callable    |


### [MessageMask::class](src/Support/Masks/MessageMask.php)
| Method                              | Arguments                              | Return         | Description                                |
| ----------------------------------- | -------------------------------------- | :------------: | ------------------------------------------ |
| getHtmlBody                         |                                        | string or null | Get HTML body                              |     
| getCustomHTMLBody                   | callable or bool $callback             | string or null | Get a custom HTML body                     |  
| getHTMLBodyWithEmbeddedBase64Images |                                        | string or null | Get HTML body with embedded base64 images  |  
| getHTMLBodyWithEmbeddedUrlImages    | string $route_name, array $params = [] | string or null | Get HTML body with embedded routed images  |  


### [AttachmentMask::class](src/Support/Masks/AttachmentMask.php)
| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getContentBase64Encoded     |                   | string or null | Get attachment content                                 |     
| getImageSrc    |                                | string or null | Get attachment mime type                               |    


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
| Kerberos error: No credentials cache file found (try running kinit) (...) | Uncomment "DISABLE_AUTHENTICATOR" inside and use the `legacy-imap` protocol |


## Change log
Please see [CHANGELOG][link-changelog] for more information what has changed recently.


## Security
If you discover any security related issues, please email github@webklex.com instead of using the issue tracker.


## Credits
- [Webklex][link-author]
- [All Contributors][link-contributors]


## License
The MIT License (MIT). Please see [License File][link-license] for more information.


[ico-version]: https://img.shields.io/packagist/v/Webklex/php-imap.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Webklex/php-imap/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/Webklex/php-imap.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Webklex/php-imap.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Webklex/php-imap.svg?style=flat-square
[ico-build]: https://img.shields.io/scrutinizer/build/g/Webklex/php-imap/master?style=flat-square
[ico-quality]: https://img.shields.io/scrutinizer/quality/g/Webklex/php-imap/master?style=flat-square
[ico-hits]: https://hits.webklex.com/svg/webklex/php-imap

[link-packagist]: https://packagist.org/packages/Webklex/php-imap
[link-travis]: https://travis-ci.org/Webklex/php-imap
[link-scrutinizer]: https://scrutinizer-ci.com/g/Webklex/php-imap/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Webklex/php-imap
[link-downloads]: https://packagist.org/packages/Webklex/php-imap
[link-author]: https://github.com/webklex
[link-contributors]: https://github.com/Webklex/php-imap/graphs/contributors
[link-license]: https://github.com/Webklex/php-imap/blob/master/LICENSE
[link-changelog]: https://github.com/Webklex/php-imap/blob/master/CHANGELOG.md
[link-jetbrains]: https://www.jetbrains.com
[link-hits]: https://hits.webklex.com
