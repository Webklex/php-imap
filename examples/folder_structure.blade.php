<?php
/*
 * File: folder_structure.blade.php
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
 */

?>
<table>
    <thead>
    <tr>
        <th>Folder</th>
        <th>Unread messages</th>
    </tr>
    </thead>
    <tbody>
    <?php if($paginator->count() > 0): ?>
        <?php foreach($paginator as $oFolder): ?>
                <tr>
                    <td><?php echo $oFolder->name; ?></td>
                    <td><?php echo $oFolder->search()->unseen()->leaveUnread()->setFetchBody(false)->setFetchAttachment(false)->get()->count(); ?></td>
                </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="4">No folders found</td>
        </tr>
    <?php endif: ?>
    </tbody>
</table>

<?php echo $paginator->links(); ?>