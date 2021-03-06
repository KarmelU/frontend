<div class="owner-edit">
  <h3>This photo belongs to you. You can edit it.</h3>
  <div>
    <div class="detail-form">
      <form method="post" action="<?php Url::photoUpdate($photo['id']); ?>">
        <input type="hidden" name="crumb" value="<?php Utility::safe($crumb); ?>">
        <label>Title</label>
        <input type="text" name="title" placeholder="A title to describe your photo" value="<?php Utility::safe($photo['title']); ?>">

        <label>Description</label>
        <textarea name="description" placeholder="A description of the photo (typically longer than the title)"><?php Utility::safe($photo['description']); ?></textarea>

        <label>Tags</label>
        <input type="text" name="tags" placeholder="A comma separated list of tags" value="<?php Utility::safe(implode(',', $photo['tags'])); ?>">

        <label>Latitude</label>
        <input type="text" name="latitude" placeholder="A latitude value for the location of this photo (i.e. 49.7364565)" value="<?php Utility::safe($photo['latitude']); ?>">

        <label>Longitude</label>
        <input type="text" name="longitude" placeholder="A longitude value for the location of this photo (i.e. 181.34523224)" value="<?php Utility::safe($photo['longitude']); ?>">

        <label>Permission</label>
        <div>
          <input type="radio" name="permission" value="0" <?php if($photo['permission'] == 0) { ?> checked="checked" <?php } ?>>
          Private
        </div>
        <div>
          <input type="radio" name="permission" value="1" <?php if($photo['permission'] == 1) { ?> checked="checked" <?php } ?>>
          Public
        </div>

        <?php if(count($groups) > 0) { ?>
          <label>Groups</label>
          <?php foreach($groups as $group) { ?>
            <div>
              <input type="checkbox" name="groups[]" value="<?php Utility::safe($group['id']); ?>" <?php if(isset($photo['groups']) && in_array($group['id'], $photo['groups'])) { ?> checked="checked" <?php } ?>>
              <?php Utility::licenseLong($group['name']); ?>
            </div>
          <?php } ?>
        <?php } ?>

        <label>License</label>
        <?php foreach($licenses as $code => $license) { ?>
          <div>
            <input type="radio" name="license" value="<?php Utility::safe($code); ?>" <?php if($license['selected']) { ?> checked="checked" <?php } ?>>
            <?php Utility::licenseLong($code); ?>
          </div>
        <?php } ?>

        <button type="submit">Update photo</button>
      </form>
    </div>
  </div>
  <div class="delete">
    <form method="post" action="<?php Url::photoDelete($photo['id']); ?>">
      <input type="hidden" name="crumb" value="<?php echo $crumb; ?>">
      <button type="submit" class="delete photo-delete-click">Delete this photo</button>
    </form>
  </div>
  <br clear="all">
</div>
