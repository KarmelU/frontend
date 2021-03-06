<?php
/**
 * MySQL implementation
 *
 * This class defines the functionality defined by DatabaseInterface for a MySQL database.
 * It use EpiDatabase.
 * @author Hub Figuiere <hub@figuiere.net>
 */
class DatabaseMySql implements DatabaseInterface
{
  const currentSchemaVersion = 3;
  /**
    * Member variables holding the names to the SimpleDb domains needed and the database object itself.
    * @access private
    */
  private $errors = array(), $mySqlDb, $mySqlHost, $mySqlUser, $mySqlPassword, $mySqlTablePrefix;

  /**
    * Constructor
    *
    * @return void
    */
  public function __construct()
  {
    $mysql = getConfig()->get('mysql');
    EpiDatabase::employ('mysql', $mysql->mySqlDb, 
                        $mysql->mySqlHost, $mysql->mySqlUser, Utility::decrypt($mysql->mySqlPassword));
    foreach($mysql as $key => $value) {
      $this->{$key} = $value;
    }
  }

  /**
    * Delete an action from the database
    *
    * @param string $id ID of the action to delete
    * @return boolean
    */
  public function deleteAction($id)
  {
    $res = getDatabase()->execute("DELETE FROM `{$this->mySqlTablePrefix}action` WHERE id=:id", array(':id' => $id));
    return ($res == 1);
  }

  /**
    * Delete credential
    *
    * @return boolean
    */
  public function deleteCredential($id)
  {
    $res = getDatabase()->execute("DELETE FROM `{$this->mySqlTablePrefix}credential` WHERE id=:id", array(':id' => $id));
    return ($res == 1);
  }

  /**
    * Delete a group from the database
    *
    * @param string $id ID of the group to delete
    * @return boolean
    */
  public function deleteGroup($id)
  {
    $res = getDatabase()->execute("DELETE FROM `{$this->mySqlTablePrefix}group` WHERE id=:id", array(':id' => $id));
    return ($res == 1);
  }

  /**
    * Delete a photo from the database
    *
    * @param string $id ID of the photo to delete
    * @return boolean
    */
  public function deletePhoto($id)
  {
    $res = getDatabase()->execute("DELETE FROM `{$this->mySqlTablePrefix}photo` WHERE id=:id", array(':id' => $id));
    return ($res == 1);
  }

  /**
    * Delete a tag from the database
    *
    * @param string $id ID of the tag to delete
    * @return boolean
    */
  public function deleteTag($id)
  {
    $res = getDatabase()->execute("DELETE FROM `{$this->mySqlTablePrefix}tag` WHERE id=:id", array(':id' => $id));
    return ($res == 1);
  }

  /**
    * Delete a webhook from the database
    *
    * @param string $id ID of the webhook to delete
    * @return boolean
    */
  public function deleteWebhook($id)
  {
    // Gh-193
    return false;
  }

  /**
    * Get a list of errors
    *
    * @return array
    */
  public function errors()
  {
    // Gh-193
    return $this->errors;
  }

  /**
    * Retrieve a credential with $id
    *
    * @param string $id ID of the credential to get
    * @return mixed Array on success, FALSE on failure
    */
  public function getCredential($id)
  {
    $cred = getDatabase()->one("SELECT * FROM `{$this->mySqlTablePrefix}credential` WHERE id=:id",
                               array(':id' => $id));
    if(empty($cred))
    {
      return false;
    }
    return self::normalizeCredential($cred);
  }

  /**
    * Retrieve a credential by userToken
    *
    * @param string $userToken userToken of the credential to get
    * @return mixed Array on success, FALSE on failure
    */
  public function getCredentialByUserToken($userToken)
  {
    $cred = getDatabase()->one("SELECT * FROM `{$this->mySqlTablePrefix}credential` WHERE userToken=:userToken",
                               array(':userToken' => $userToken));
    if(empty($cred))
    {
      return false;
    }
    return self::normalizeCredential($cred);
  }

  /**
    * Retrieve credentials
    *
    * @return mixed Array on success, FALSE on failure
    */
  public function getCredentials()
  {
    $res = getDatabase()->all("SELECT * FROM `{$this->mySqlTablePrefix}credential` WHERE status=1");
    if($res === false)
    {
      return false;
    }
    $credentials = array();
    if(!empty($res))
    {
      foreach($res as $cred)
      {
        $credentials[] = self::normalizeCredential($cred);
      }
    }
    return $credentials;
  }

  /**
    * Retrieve group from the database specified by $id
    *
    * @param string $id id of the group to return
    * @return mixed Array on success, FALSE on failure 
    */
  public function getGroup($id = null)
  {
    $res = getDatabase()->one("SELECT * FROM `{$this->mySqlTablePrefix}group` WHERE `id`='{$id}'");
    if($res === false || empty($res))
    {
      return false;
    }

    return self::normalizeGroup($res);
  }

  /**
    * Retrieve groups from the database optionally filter by member (email)
    *
    * @param string $email email address to filter by
    * @return mixed Array on success, NULL on empty, FALSE on failure 
    */
  public function getGroups($email = null)
  {
    if(empty($email))
    {
      $res = getDatabase()->all("SELECT * FROM `{$this->mySqlTablePrefix}group` WHERE `id` IS NOT NULL ORDER BY `name`");
    }
    else
    {
      $res = getDatabase()->all("SELECT * FROM `{$this->mySqlTablePrefix}group` WHERE members in ('{$email}') AND `id` IS NOT NULL ORDER BY `id`");
    }

    if($res !== false)
    {
      $groups = array();
      if(!empty($res))
      {
        foreach($res as $group)
        {
          $groups[] = self::normalizeGroup($group);
        }
      }
      return $groups;
    }

    return false;
  }

  /**
    * Get a photo specified by $id
    *
    * @param string $id ID of the photo to retrieve
    * @return mixed Array on success, FALSE on failure
    */
  public function getPhoto($id)
  {
    $photo = getDatabase()->one("SELECT * FROM `{$this->mySqlTablePrefix}photo` WHERE id=:id", array(':id' => $id));
    if(empty($photo))
      return false;
    return self::normalizePhoto($photo);
  }

  /**
    * Retrieve the next and previous photo surrounding photo with $id
    *
    * @param string $id ID of the photo to get next and previous for
    * @return mixed Array on success, FALSE on failure
    */
  public function getPhotoNextPrevious($id, $filterOpts = null)
  {
    $buildQuery = self::buildQuery($filterOpts, null, null);
    $photo = $this->getPhoto($id);
    if(!$photo)
      return false;

    $photo_prev = getDatabase()->one("SELECT * FROM `{$this->mySqlTablePrefix}photo` {$buildQuery['where']} AND dateTaken> :dateTaken AND dateTaken IS NOT NULL ORDER BY dateTaken ASC LIMIT 1", array(':dateTaken' => $photo['dateTaken']));
    $photo_next = getDatabase()->one("SELECT * FROM `{$this->mySqlTablePrefix}photo` {$buildQuery['where']} AND dateTaken< :dateTaken AND dateTaken IS NOT NULL ORDER BY dateTaken DESC LIMIT 1", array(':dateTaken' => $photo['dateTaken']));

    $ret = array();
    if($photo_prev)
      $ret['previous'] = self::normalizePhoto($photo_prev);
    if($photo_next)
      $ret['next'] = self::normalizePhoto($photo_next);

    return $ret;
  }

  /**
    * Retrieve a photo from the database and include the actions on the photo.
    * Actions are stored in a separate domain so the calls need to be made in parallel
    *
    * @param string $id ID of the photo to retrieve
    * @return mixed Array on success, FALSE on failure
    */
  public function getPhotoWithActions($id)
  {
    $photo = $this->getPhoto($id);
    $photo['actions'] = array();
    if($photo)
    {
      $actions = getDatabase()->all("SELECT * FROM `{$this->mySqlTablePrefix}action` WHERE targetType='photo' AND targetId=:id",
      	       array(':id' => $id));
      if(!empty($actions))
      {
        foreach($actions as $action)
           $photo['actions'][] = $action;
      }
    }
    return $photo;
  }

  /**
    * Get a list of a user's photos filtered by $filter, $limit and $offset
    *
    * @param array $filters Filters to be applied before obtaining the result
    * @return mixed Array on success, FALSE on failure
    */
  public function getPhotos($filters = array(), $limit, $offset = null)
  {
    $query = self::buildQuery($filters, $limit, $offset);

    $photos = getDatabase()->all("SELECT * FROM `{$this->mySqlTablePrefix}photo` {$query['where']} {$query['sortBy']} {$query['limit']} {$query['offset']}");
    if(empty($photos))
      return false;
    for($i = 0; $i < count($photos); $i++)
    {
      $photos[$i] = self::normalizePhoto($photos[$i]);
    }
    $result = getDatabase()->one("SELECT COUNT(*) FROM `{$this->mySqlTablePrefix}photo` {$query['where']}");
    if(!empty($result))
    {
      $photos[0]['totalRows'] = $result['COUNT(*)'];
    }
    return $photos;
  }

  /**
    * Get a webhook specified by $id
    *
    * @param string $id ID of the webhook to retrieve
    * @return mixed Array on success, FALSE on failure
    */
  public function getWebhook($id)
  {
    // See Gh-193
    return false;
  }

  /**
    * Get all webhooks for a user
    *
    * @return mixed Array on success, FALSE on failure
    */
  public function getWebhooks($topic = null)
  {
    // See Gh-193
    return false;
  }

  private function buildQuery($filters, $limit, $offset)
  {
    // TODO: support logic for multiple conditions
    $where = '';
    $sortBy = 'ORDER BY dateTaken DESC';
    if(!empty($filters) && is_array($filters))
    {
      foreach($filters as $name => $value)
      {
        switch($name)
        {
          case 'tags':
            if(!is_array($value))
              $value = (array)explode(',', $value);
            $where = $this->buildWhere($where, ' MATCH(tags) AGAINST(\'+",' . implode('," +"', $value) . ',"\' IN BOOLEAN MODE)');
            break;
          case 'page':
            if($value > 1)
            {
              $value = min($value, 40); // 40 pages at max of 2,500 recursion limit means 100k photos
              $offset = ($limit * $value) - $limit;
            }
            break;
          case 'sortBy':
            $sortBy = 'ORDER BY ' . str_replace(',', ' ', $value);
            $field = substr($value, 0, strpos($value, ','));
            $where = $this->buildWhere($where, "{$field} is not null");
            break;
        }
      }
    }

    $limit_sql = '';
    if($limit)
      $limit_sql = "LIMIT {$limit}";

    $offset_sql = '';
    if($offset)
      $offset_sql = "OFFSET {$offset}";

    $ret = array('where' => $where, 'sortBy' => $sortBy, 'limit' => $limit_sql, 'offset' => $offset_sql);
    return $ret;
  }

  /**
    * Get a tag
    * Consistent read set to false
    *
    * @param string $tag tag to be retrieved
    * @return mixed Array on success, FALSE on failure
    */
  public function getTag($tag)
  {
    $tag = getDatabase()->one('SELECT * FROM `{$this->mySqlTablePrefix}tag` WHERE id=:id', array(':id' => $tag));
    // TODO this should be in the normalize method
    if($tag['params'])
      $tag = array_merge($tag, json_decode($tag['params'], 1));
    unset($tag['params']);
    return $tag;
  }

  /**
    * Get tags filtered by $filter
    * Consistent read set to false
    *
    * @param array $filters Filters to be applied to the list
    * @return mixed Array on success, FALSE on failure
    */
  public function getTags($filter = array())
  {
    $tags = getDatabase()->all("SELECT * FROM `{$this->mySqlTablePrefix}tag` WHERE `count` IS NOT NULL AND `count` > '0' AND id IS NOT NULL ORDER BY id");
    foreach($tags as $key => $tag)
    {
      // TODO this should be in the normalize method
      if($tag['params'])
        $tags[$key] = array_merge($tag, json_decode($tag['params'], 1));
      unset($tags[$key]['params']);
    }
    return $tags;
  }

  /**
    * Get the user record entry.
    *
    * @return mixed Array on success, NULL if user record is empty, FALSE on error
    */
  public function getUser()
  {
    $res = getDatabase()->one("SELECT * FROM `{$this->mySqlTablePrefix}user` WHERE id='1'");
    if($res)
    {
      return self::normalizeUser($res);
    }
    return false;
  }

  /**
    * Update the information for an existing credential.
    * This method overwrites existing values present in $params.
    *
    * @param string $id ID of the credential to update.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function postCredential($id, $params)
  {
    $params = self::prepareCredential($params);
    $bindings = array();
    if(isset($params['::bindings']))
    {
      $bindings = $params['::bindings'];
    }
    $stmt = self::sqlUpdateExplode($params, $bindings);
    $bindings[':id'] = $id;

    $result = getDatabase()->execute("UPDATE `{$this->mySqlTablePrefix}credential` SET {$stmt} WHERE id=:id", $bindings);

    return ($result == 1);
  }

  /**
    * Update the information for an existing credential.
    * This method overwrites existing values present in $params.
    *
    * @param string $id ID of the credential to update.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function postGroup($id, $params)
  {
    $params = self::prepareGroup($id, $params);
    $bindings = array();
    if(isset($params['::bindings']))
    {
      $bindings = $params['::bindings'];
    }
    $stmt = self::sqlUpdateExplode($params, $bindings);
    $bindings[':id'] = $id;

    $result = getDatabase()->execute("UPDATE `{$this->mySqlTablePrefix}group` SET {$stmt} WHERE id=:id", $bindings);

    return ($result == 1);
  }

  /**
    * Update the information for an existing photo.
    * This method overwrites existing values present in $params.
    *
    * @param string $id ID of the photo to update.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function postPhoto($id, $params)
  {
    $params = self::preparePhoto($id, $params);
    unset($params['id']);

    foreach($params as $key => $val)
    {
      if(preg_match('/^path\d+x\d+/', $key))
      {
        $versions[$key] = $val;
        unset($params[$key]);
      }
    }

    if(!empty($params))
    {
      $bindings = $params['::bindings'];
      $stmt = self::sqlUpdateExplode($params, $bindings);
      $bindings[':id'] = $id;
      $res = getDatabase()->execute("UPDATE `{$this->mySqlTablePrefix}photo` SET {$stmt} WHERE id=:id", $bindings);
    }
    if(!empty($versions))
      $resVersions = $this->postVersions($id, $versions);
    return (isset($res) && $res == 1) || (isset($resVersions) && $resVersions);
  }

  /**
    * Update a single tag.
    * The $params should include the tag in the `id` field.
    * [{id: tag1, count:10, longitude:12.34, latitude:56.78},...]
    *
    * @param array $params Tags and related attributes to update.
    * @return boolean
    */
  public function postTag($id, $params)
  {
    if(!isset($params['id']))
    {
      $params['id'] = $id;
    }
    $params = self::prepareTag($params);
    if(isset($params['::bindings']))
      $bindings = $params['::bindings'];
    else
      $bindings = array();

    $stmtIns = self::sqlInsertExplode($params, $bindings);
    $stmtUpd = self::sqlUpdateExplode($params, $bindings);

    $result = getDatabase()->execute("INSERT INTO `{$this->mySqlTablePrefix}tag` ({$stmtIns['cols']}) VALUES ({$stmtIns['vals']}) ON DUPLICATE KEY UPDATE {$stmtUpd}", $bindings);
    return ($result !== false);
  }

  /**
    * Update multiple tags.
    * The $params should include the tag in the `id` field.
    * [{id: tag1, count:10, longitude:12.34, latitude:56.78},...]
    *
    * @param array $params Tags and related attributes to update.
    * @return boolean
    */
  public function postTags($params)
  {
    if(empty($params))
      return true;
    foreach($params as $tagObj)
    {
      $res = $this->postTag($tagObj['id'], $tagObj);
    }
    return $res;
  }

 /**
    * Update counts for multiple tags by incrementing or decrementing.
    * The $params should include the tag in the `id` field.
    * [{id: tag1, count:10, longitude:12.34, latitude:56.78},...]
    *
    * @param array $params Tags and related attributes to update.
    * @return boolean
    */
  public function postTagsCounter($params)
  {
    $tagsToUpdate = $tagsFromDb = array();
    foreach($params as $tag => $changeBy)
      $tagsToUpdate[$tag] = $changeBy;
    $justTags = array_keys($tagsToUpdate);

    // TODO call getTags instead
    $in_stmt = implode("','", $justTags);
    $res = getDatabase()->all("SELECT * FROM `{$this->mySqlTablePrefix}tag` WHERE id IN (':in')", array (':in' => $in_stmt) );
    if(!empty($res))
    {
      foreach($res as $val)
        $tagsFromDb[] = self::normalizeTag($val);
    }

    // track the tags which need to be updated
    // start with ones which already exist in the database and increment them accordingly
    $updatedTags = array();
    foreach($tagsFromDb as $key => $tagFromDb)
    {
      $thisTag = $tagFromDb['id'];
      $changeBy = $tagsToUpdate[$thisTag];
      $updatedTags[] = array('id' => $thisTag, 'count' => $tagFromDb['count']+$changeBy);
      // unset so we can later loop over tags which didn't already exist
      unset($tagsToUpdate[$thisTag]);
    }
    // these are new tags
    foreach($tagsToUpdate as $tag => $count)
      $updatedTags[] = array('id' => $tag, 'count' => $count);
    return $this->postTags($updatedTags);
  }

  /**
    * Update the information for the user record.
    * This method overwrites existing values present in $params.
    *
    * @param string $id ID of the user to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function postUser($id, $params)
  {
    $stmt = self::sqlUpdateExplode($params);
    $res = getDatabase()->execute("UPDATE `{$this->mySqlTablePrefix}user` SET {$stmt} WHERE id=:id", array(':id' => $id));
    return $res = 1;
  }

  /**
    * Update the information for the webhook record.
    *
    * @param string $id ID of the webhook to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function postWebhook($id, $params)
  {
    // See Gh-193
    return false;
  }

  /**
    * Add a new action to the database
    * This method does not overwrite existing values present in $params - hence "new action".
    *
    * @param string $id ID of the action to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function putAction($id, $params)
  {
    $stmt = self::sqlInsertExplode($params);
    $result = getDatabase()->execute("INSERT INTO `{$this->mySqlTablePrefix}action` (id,{$stmt['cols']}) VALUES (:id,{$stmt['vals']})", array(':id' => $id));
    return ($result !== false);
  }

  /**
    * Add a new credential to the database
    * This method does not overwrite existing values present in $params - hence "new credential".
    *
    * @param string $id ID of the credential to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function putCredential($id, $params)
  {
    if(!isset($params['id']))
      $params['id'] = $id;
    $params = self::prepareCredential($params);
    $stmt = self::sqlInsertExplode($params);
    $result = getDatabase()->execute("INSERT INTO `{$this->mySqlTablePrefix}credential` ({$stmt['cols']}) VALUES ({$stmt['vals']})");

    return ($result !== false);
  }

  /**
    * Alias of postGroup
    */
  public function putGroup($id, $params)
  {
    if(!isset($params['id']))
      $params['id'] = $id;
    $params = self::prepareGroup($id, $params);
    $stmt = self::sqlInsertExplode($params);
    $result = getDatabase()->execute("INSERT INTO `{$this->mySqlTablePrefix}group` ({$stmt['cols']}) VALUES ({$stmt['vals']})");

    return ($result !== false);
  }

  /**
    * Add a new photo to the database
    * This method does not overwrite existing values present in $params - hence "new photo".
    *
    * @param string $id ID of the photo to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function putPhoto($id, $params)
  {
    $params = self::preparePhoto($id, $params);
    $bindings = $params['::bindings'];
    $stmt = self::sqlInsertExplode($params, $bindings);
    $result = getDatabase()->execute("INSERT INTO `{$this->mySqlTablePrefix}photo` ({$stmt['cols']}) VALUES ({$stmt['vals']})", $bindings);
    return ($result !== false);
  }

  /**
    * Add a new tag to the database
    * This method does not overwrite existing values present in $params - hence "new user".
    *
    * @param string $id ID of the user to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function putTag($id, $params)
  {
    if(!isset($params['count']))
      $count = 0;
    else
      $count = max(0, intval($params['count']));
    return $this->postTag($id, $params);
  }

  /**
    * Add a new user to the database
    * This method does not overwrite existing values present in $params - hence "new user".
    *
    * @param string $id ID of the user to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function putUser($id, $params)
  {
    $stmt = self::sqlInsertExplode($params);
    $result = getDatabase()->execute("INSERT INTO `{$this->mySqlTablePrefix}user` (id,{$stmt['cols']}) VALUES (:id,{$stmt['vals']})", array(':id' => $id));
    return ($result != -1);
  }

  /**
    * Add a new webhook to the database
    *
    * @param string $id ID of the webhook to update which is always 1.
    * @param array $params Attributes to update.
    * @return boolean
    */
  public function putWebhook($id, $params)
  {
    // See Gh-193
    return false;
  }

  /**
    * Initialize the database by creating the database and tables needed.
    * This is called from the Setup controller.
    *
    * @return boolean
    */
  public function initialize()
  {
    $version = $this->checkDbVersion();
    if($version == 0)
    {
      $this->createSchema();
    }
    else if($version < self::currentSchemaVersion)
    {
      return $this->upgradeFrom($version);
    }
    return true;
  }

  /**
    * Utility function to help build the WHERE clause for SELECT statements.
    * (Taken from DatabaseSimpleDb)
    * TODO possibly put duplicate code in a utility class
    *
    * @param string $existing Existing where clause.
    * @param string $add Clause to add.
    * @return string
    */
  private function buildWhere($existing, $add)
  {
    if(empty($existing))
      return "where {$add} ";
    else
      return "{$existing} and {$add} ";
  }

  /**
    * Get all the versions of a given photo
    * TODO this can be eliminated once versions are in the json field
    *
    * @param string $id Id of the photo of which to get versions of
    * @return array Array of versions
    */
  private function getPhotoVersions($id)
  {
    $versions = getDatabase()->all("SELECT `key`,path FROM `{$this->mySqlTablePrefix}photoVersion` WHERE id=:id",
                 array(':id' => $id));
    if(empty($versions))
      return false;
    return $versions;
  }

  /**
   * Explode params associative array into SQL update statement lists
   * Return a string
   * TODO, have this work with PDO named parameters
   */
  private function sqlUpdateExplode($params, $bindings = array())
  {
    $stmt = '';
    foreach($params as $key => $value)
    {
      if($key == '::bindings')
        continue;
      if(!empty($stmt)) {
        $stmt .= ",";
      }
      if(!empty($bindings) && !empty($bindings[$value]))
        $stmt .= "{$key}={$value}";
      else
        $stmt .= "{$key}='{$value}'";
    }
    return $stmt;
  }

  /**
   * Explode params associative array into SQL insert statement lists
   * Return an array with 'cols' and 'vals'
   */
  private function sqlInsertExplode($params, $bindings = array())
  {
    $stmt = array('cols' => '', 'vals' => '');
    foreach($params as $key => $value)
    {
      if($key == '::bindings')
        continue;
      if(!empty($stmt['cols']))
        $stmt['cols'] .= ",";
      if(!empty($stmt['vals']))
        $stmt['vals'] .= ",";
      $stmt['cols'] .= $key;
      if(!empty($bindings) && !empty($bindings[$value]))
        $stmt['vals'] .= "{$value}";
      else
        $stmt['vals'] .= "'{$value}'";
    }
    return $stmt;
  }

  /**
    * Normalizes data from MySql into schema definition
    *
    * @param SimpleXMLObject $raw An action from SimpleDb in SimpleXML.
    * @return array
    */
  private function normalizeAction($raw)
  {
    // TODO shouldn't we require and use this method?
  }

  /**
    * Normalizes data from simpleDb into schema definition
    * TODO this should eventually translate the json field
    *
    * @param SimpleXMLObject $raw A photo from SimpleDb in SimpleXML.
    * @return array
    */
  private function normalizePhoto($photo)
  {
    $photo['appId'] = getConfig()->get('application')->appId;

    $versions = $this->getPhotoVersions($photo['id']);
    if($versions && !empty($versions)) 
    {
      foreach($versions as $version) 
      {
        $photo[$version['key']] = $version['path'];
      }
    }

    $photo['tags'] = explode(",", $photo['tags']);

    $exif_array = (array)json_decode($photo['exif']);
    $photo = array_merge($photo, $exif_array);
    unset($photo['exif']);

    return $photo;
  }

  /**
    * Normalizes data from simpleDb into schema definition
    * TODO this should eventually translate the json field
    *
    * @param SimpleXMLObject $raw A tag from SimpleDb in SimpleXML.
    * @return array
    */
  private function normalizeTag($raw)
  {
    return $raw;
  }

  /**
    * Normalizes data from simpleDb into schema definition
    * TODO this should eventually translate the json field
    *
    * @param SimpleXMLObject $raw A tag from SimpleDb in SimpleXML.
    * @return array
    */
  private function normalizeUser($raw)
  {
    return $raw;
  }

  private function normalizeCredential($raw)
  {
    if(isset($raw['permissions']) && !empty($raw['permissions']))
      $raw['permissions'] = (array)explode(',', $raw['permissions']);

    return $raw;
  }


  /**
    * Normalizes data from simpleDb into schema definition
    *
    * @param SimpleXMLObject $raw An action from SimpleDb in SimpleXML.
    * @return array
    */
  private function normalizeGroup($raw)
  {
    if(isset($raw['members']) && !empty($raw['members']))
      $raw['members'] = (array)explode(',', $raw['members']);

    return $raw;
  }

  /**
    * Formats a photo to be updated or added to the database.
    * Primarily to properly format tags as an array.
    *
    * @param string $id ID of the photo.
    * @param array $params Parameters for the photo to be normalized.
    * @return array
    */
  private function preparePhoto($id, $params)
  {
    $bindings = array();
    $params['id'] = $id;
    if(isset($params['tags']) && is_array($params['tags']))
      $params['tags'] = implode(',', $params['tags']) ;

    $exif_keys = array('exifOrientation' => 0,
                       'exifCameraMake' => 0,
                       'exifCameraModel' => 0,
                       'exifExposureTime' => 0,
                       'exifFNumber' => 0,
                       'exifMaxApertureValue' => 0,
                       'exifMeteringMode' => 0,
                       'exifFlash' => 0,
                       'exifFocalLength' => 0,
                       'exifISOSpeed' => 0,
                       'gpsAltitude' => 0,
                       'latitude' => 0,
                       'longitude' => 0);

    $exif_array = array_intersect_key($params, $exif_keys);
    if(!empty($exif_array))
    {
      foreach(array_keys($exif_keys) as $key)
      {
        unset($params[$key]);
      }
      $bindings[':exif'] = json_encode($exif_array);
      $params['exif'] = ':exif';
    }
    if(!empty($params['title']))
    {
      $bindings[':title'] = $params['title'];
      $params['title'] = ':title';
    }
    if(!empty($params['description']))
    {
      $bindings[':description'] = $params['description'];
      $params['description'] = ':description';
    }
    if(!empty($params['tags']))
    {
      $bindings[':tags'] = $params['tags'];
      $params['tags'] = ':tags';
    }
    if(!empty($bindings))
    {
      $params['::bindings'] = $bindings;
    }
    return $params;
  }

  /** Prepare tags to store in the database
   */
  private function prepareTag($params)
  {
    $bindings = array();
    if(!empty($params['id']))
    {
      $bindings[':id'] = $params['id'];
      $params['id'] = ':id';
    }
    if(!empty($bindings))
    {
      $params['::bindings'] = $bindings;
    }
    return $params;    
  }
  /** Prepare credential to store in the database
   */
  private function prepareCredential($params)
  {
    if(isset($params['permissions']))
      $params['permissions'] = implode(',', $params['permissions']);

    return $params;
  }

  /**
    * Formats a group to be updated or added to the database.
    * Primarily to properly format members as an array.
    *
    * @param string $id ID of the group.
    * @param array $params Parameters for the group to be normalized.
    * @return array
    */
  private function prepareGroup($id, $params)
  {
    if(!isset($params['members']))
      $params['members'] = '';
    elseif(is_array($params['members']))
      $params['members'] = implode(',', $params['members']);
    return $params;
  }

  /**
    * Inserts a new version of photo with $id and $versions
    * TODO this should be in a json field in the photo table
    *
    * @param string $id ID of the photo.
    * @param array $versions Versions to the photo be inserted
    * @return array
    */
  private function postVersions($id, $versions)
  {
    foreach($versions as $key => $value)
    {
      // TODO this is gonna fail if we already have the version -- hfiguiere
      // Possibly use REPLACE INTO? -- jmathai
      $result = getDatabase()->execute("INSERT INTO {$this->mySqlTablePrefix}photoVersion (id, `key`, path) VALUES('{$id}', '{$key}', '{$value}')");
    }
    // TODO, what type of return value should we have here -- jmathai
    return ($result != 1);
  }

  /**
   * Check the Db Version.
   * 
   * This shouldn't fail unless you don't have database access
   * But if the DB is empty (ie don't have the tables), it returns 0
   * @return the numeric version. 0 means no DB
   */
  private function checkDbVersion()
  {
    $version = 0;
    $result = getDatabase()->one("SHOW TABLES LIKE '{$this->mySqlTablePrefix}admin'");
    if(!empty($result))
    {
      $result = getDatabase()->one("SELECT value FROM {$this->mySqlTablePrefix}admin WHERE `key`='version'");
      if(!empty($result) && isset($result['value']))
      {
        $version = $result['value'];
      }
    }
    return $version;
  }
  /**
   * Upgrade from a version to the current schema
   *
   * @param int $version version of the database found
   * @return false if failed
   */
  private function upgradeFrom($version)
  {
    if($version < 1)
      return false;
    switch($version) {      	
    case 1:
      // after version 1 we added credential
      //
      $result = getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}credential`"
        . "(`id` varchar(255) NOT NULL UNIQUE,"
	. "`name` varchar(255) DEFAULT NULL,"
	. "`image` text DEFAULT NULL,"
	. "`clientSecret` varchar(255) DEFAULT NULL,"
	. "`userToken` varchar(255) DEFAULT NULL,"
	. "`userSecret` varchar(255) DEFAULT NULL,"
	. "`permissions` varchar(255) DEFAULT NULL,"
	. "`verifier` varchar(255) DEFAULT NULL,"
	. "`type` varchar(100) DEFAULT NULL,"
	. "`status` int DEFAULT 0,"
	. "PRIMARY KEY(`id`)"
	. ") ENGINE=InnoDB DEFAULT CHARSET=utf8");
      if($result === false) {
        return false;
      }
    case 2:
      // after version 2 we added group
      getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}group`"
        . "(`id` varchar(255) NOT NULL UNIQUE,"
	. "`appId` varchar(255),"
	. "`name` varchar(255),"
	. "`members` TEXT,"
	. "PRIMARY KEY(`id`)"
	. ") ENGINE=InnoDB DEFAULT CHARSET=utf8");
      getDatabase()->execute("UPDATE `{$this->mySqlTablePrefix}admin`"
        . "SET `value`='" . self::currentSchemaVersion ."' WHERE `key`='version'");
      // OTHER
    case 3:
      break;
    }
    return true;
  }

  /**
    * Create the database schema
    * Make sure to enforce the version
    * @return true on success.
    */
  private function createSchema()
  {
    getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}admin`"
        . "(`key` varchar(255) NOT NULL,"
        . "`value` varchar(255) NOT NULL)"
        . "ENGINE=InnoDB DEFAULT CHARSET=utf8");
    getDatabase()->execute("INSERT INTO `{$this->mySqlTablePrefix}admin`"
        . "(`key`, `value`) VALUES('version', '" . self::currentSchemaVersion . "')");

    getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}photo`"
        . "(`id` varchar(255) NOT NULL,"
	. "`appId` varchar(255) NOT NULL,"
	. "`host` varchar(255) DEFAULT NULL,"
	. "`title` varchar(255) DEFAULT NULL,"
	. "`description` text,"
	. "`key` varchar(255) DEFAULT NULL,"
	. "`hash` varchar(255) DEFAULT NULL,"
	. "`size` int(11) DEFAULT NULL,"
	. "`width` int(11) DEFAULT NULL,"
	. "`height` int(11) DEFAULT NULL,"
	. "`exif` text,"
	. "`views` int(11) DEFAULT NULL,"
	. "`status` int(11) DEFAULT NULL,"
	. "`permission` int(11) DEFAULT NULL,"
	. "`license` varchar(255) DEFAULT NULL,"
	. "`dateTaken` int(11) DEFAULT NULL,"
	. "`dateTakenDay` int(11) DEFAULT NULL,"
	. "`dateTakenMonth` int(11) DEFAULT NULL,"
	. "`dateTakenYear` int(11) DEFAULT NULL,"
	. "`dateUploaded` int(11) DEFAULT NULL,"
	. "`dateUploadedDay` int(11) DEFAULT NULL,"
	. "`dateUploadedMonth` int(11) DEFAULT NULL,"
	. "`dateUploadedYear` int(11) DEFAULT NULL,"
	. "`pathOriginal` varchar(1000) DEFAULT NULL,"
	. "`pathBase` varchar(1000) DEFAULT NULL,"
	. "`tags` text,"
	. "PRIMARY KEY (`id`),"
	. "UNIQUE KEY `id` (`id`),"
	. "FULLTEXT (`tags`)"
	. ") ENGINE=MyISAM DEFAULT CHARSET=utf8");
    getDatabase()->execute("CREATE TABLE IF NOT EXISTS "
        . "`{$this->mySqlTablePrefix}photoVersion`"
        . "(`id` varchar(255),"
        . "`key` varchar(255),"
        . "`path` varchar(1000),"
        . "PRIMARY KEY(`id`,`key`)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8");
    getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}tag`"
        . "(`id` varchar(255) NOT NULL,"
	. "`count` int(11) DEFAULT NULL,"
	. "`params` text NOT NULL,"
	. "PRIMARY KEY(`id`),"
	. "UNIQUE KEY `id` (`id`)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8");
    getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}user`"
        . "(`id` varchar(255) NOT NULL UNIQUE,"
        . "`lastPhotoId` varchar(255),"
        . "`lastActionId` varchar(255),"
        . "PRIMARY KEY(`id`)"
	. ") ENGINE=InnoDB DEFAULT CHARSET=utf8");
    getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}credential`"
        . "(`id` varchar(255) NOT NULL UNIQUE,"
	. "`name` varchar(255) DEFAULT NULL,"
	. "`image` text DEFAULT NULL,"
	. "`clientSecret` varchar(255) DEFAULT NULL,"
	. "`userToken` varchar(255) DEFAULT NULL,"
	. "`userSecret` varchar(255) DEFAULT NULL,"
	. "`permissions` varchar(255) DEFAULT NULL,"
	. "`verifier` varchar(255) DEFAULT NULL,"
	. "`type` varchar(100) DEFAULT NULL,"
	. "`status` int DEFAULT 0,"
	. "PRIMARY KEY(`id`)"
	. ") ENGINE=InnoDB DEFAULT CHARSET=utf8");
    getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}group`"
        . "(`id` varchar(255) NOT NULL UNIQUE,"
	. "`appId` varchar(255),"
	. "`name` varchar(255),"
	. "`members` TEXT,"
	. "PRIMARY KEY(`id`)"
	. ") ENGINE=InnoDB DEFAULT CHARSET=utf8");
    getDatabase()->execute("CREATE TABLE IF NOT EXISTS `{$this->mySqlTablePrefix}action`"
        . "(`id` varchar(255) NOT NULL UNIQUE,"
	. "`appId` varchar(255),"
	. "`targetId` varchar(255),"
	. "`targetType` varchar(255),"
	. "`email` varchar(255),"
	. "`name` varchar(255),"
	. "`avatar` varchar(255),"
	. "`website` varchar(255),"
	. "`targetUrl` varchar(1000),"
	. "`permalink` varchar(1000),"
	. "`type` varchar(255),"
	. "`value` varchar(255),"
	. "`datePosted` varchar(255),"
	. "`status` integer,"
        . "PRIMARY KEY(`id`)"
	. ") ENGINE=InnoDB DEFAULT CHARSET=utf8");

    getDatabase()->execute("INSERT IGNORE INTO `{$this->mySqlTablePrefix}user` ("
        . "`id` ,"
	. "`lastPhotoId` ,"
	. "`lastActionId`"
	. ")"
	. "VALUES ("
	. "'1', NULL , NULL"
	. ");");

    return true;
  }
}
