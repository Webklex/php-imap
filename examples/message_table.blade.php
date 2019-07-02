<?php
/*
 * File: message_table.blade.php
 * Category: View
 * Author: M.Goldenbaum
 * Created: 15.09.18 19:53
 * Updated: -
 *
 * Description:
 *  -
 */

/**
 * @var \Webklex\PHPIMAP\Support\FolderCollection $paginator
 * @var \Webklex\PHPIMAP\Folder $oFolder
 * @var \Webklex\PHPIMAP\Message $oMessage
 */

?>
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
    <?php endforeach; ?>
    <?php else: ?>
    <tr>
        <td colspan="4">No messages found</td>
    </tr>
    <?php endif; ?>
    </tbody>
</table>

<?php echo $paginator->links(); ?>