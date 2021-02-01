<?php

/**
 * @file
 * Contains the Repository class.
 */

 /**
  * Repository class extending Entity.
  */
class GithubRepository extends Entity {

  /**
   * Override defaultUri().
   *
   * @return array
   *   Return path array.
   */
  protected function defaultUri() {
    return array('path' => 'github.com/' . $this->remote_id);
  }

}
