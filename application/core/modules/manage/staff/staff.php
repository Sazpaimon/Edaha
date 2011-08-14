<?php
/*
 * This file is part of kusaba.
 *
 * kusaba is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * kusaba is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *  
 * You should have received a copy of the GNU General Public License along with
 * kusaba; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
/*
 * Staff module
 * Last Updated: $Date: $
 
 * @author    $Author: $
 
 * @package   kusaba
 
 * @version   $Revision: $
 *
 */
class manage_core_staff_staff extends kxCmd {

  /**
   * Determines which function to run
   */
  public function exec(kxEnv $environment) {
    switch ( (isset($_GET['do'])) ? $_GET['do'] : '' ) {
      case 'groups':
        
        break;
        
      case 'log':
        $this->_viewLog();
        break;
        
      default:
        $this->_show();
        break;
    }    
  }
  
  /**
   * Generates and displayed the moderator log
   */
  private function _viewLog() {
    $dwoo_data = array();
    
    if ($this->request['view']) {
      // Get actions of a specific moderator
      kxForm::addRule('view','numeric')
            ->check();
            
      $modlog = $this->db->select("modlog")
                         ->fields("modlog");
      $modlog->innerJoin("staff", "s", "user = user_name");
      $modlog = $modlog->condition("user_id",$this->request['view'])
                       ->orderBy("timestamp","DESC")
                       ->execute()
                       ->fetchAll();
    } else {
      // Get 5 most recent modlog actions
      $modlog = $this->db->select("modlog")
                          ->fields("modlog")
                          ->orderBy("timestamp","DESC")
                          ->range(0,5)
                          ->execute()
                          ->fetchAll();
    }
    $staff = $this->db->select("staff")
                      ->fields("staff")
                      ->orderBy("user_type")
                      ->orderBy("user_add_time")                      
                      ->execute()
                      ->fetchAll();

    // Fetch total actions of each user and add it to the staff array
    foreach ($staff as $user) {
      if (empty($results) || !($results instanceof kxDBStatementInterface)) {
        $results = $this->db->select("modlog")
                            ->where("user = ?")
                            ->countQuery()
                            ->build();
      }
      $results->execute(array($user->user_name));
      $user->total_actions = $results->fetchColumn();
    }
    
    $dwoo_data['modlog'] = $modlog;
    $dwoo_data['staff']  = $staff;
        kxTemplate::output("manage/staff_log", $dwoo_data);
  }

  /**
   * Allows adding, editing, and deleting of staff members
   */
  private function _show() {
    $dwoo_data = array();
    if ($_GET['act'] == 'add' && $_POST) {
      // Adds a new staff member
      kxForm::addRule('username','required')
            ->addRule('pwd1','required')
            ->addRule('pwd1','value',true,$this->request['pwd2'])
            ->addRule('type','numeric')
            ->check();
            
      $results = $this->db->select("staff")
                          ->fields("staff")
                          ->condition("user_name",$this->request['username'])
                          ->countQuery()
                          ->execute()
                          ->fetchField();
      if ($results == 0) {
        $salt = substr(md5(time().kxEnv::Get('kx:misc:randomseed')), -rand(3,6)); //$this->_createSalt(); TODO: Decide hashing algorithm, replace current
        $this->db->insert("staff")
                 ->fields(array(
                   'user_name' => $this->request['username'],
                   'user_password' => md5($this->request['pwd1'] . $salt),
                   'user_salt' => $salt,
                   'user_type' => intval($this->request['type']),
                   'user_add_time' => time()
                 ))
                 ->execute();
        $dwoo_data['notice_type'] = 'success';
        $dwoo_data['notice'] = _gettext('User added successfully');
      } else {
        // User with that username already exists
        $dwoo_data['notice_type'] = 'error';
        $dwoo_data['notice'] = _gettext('A user with that username already exists');
      }
    } elseif ($_GET['act'] == 'edit') {
      // Edits a user's information
      kxForm::addRule('id','numeric')->check();
      $user = $this->db->select("staff")
                       ->fields("staff", array("user_id", "user_name", "user_salt", "user_type"))
                       ->condition("user_id", $this->request['id'])
                       ->execute()
                       ->fetch();
                       
      if ($_POST) {
        kxForm::addRule('pwd1','value',true,$this->request['pwd2'])
              ->addRule('type','numeric')
              ->check();
        
        $values = array('user_type' => $this->request['type']);
        if (!empty($this->request['pwd1'])) {
          $values['user_password'] = md5($this->request['pwd1'] . $user['user_salt']);
        }
        $this->db->update("staff")
                 ->fields($values)
                 ->condition("user_id", $this->request['id'])
                 ->execute();
                 
        $dwoo_data['notice_type'] = 'success';
        $dwoo_data['notice'] = _gettext('User info updated!');
      }
      $dwoo_data['user'] = $user;
    } elseif ($_GET['act'] == 'del') {
      // Deletes a user
      kxForm::addRule('id','numeric')->check();
      $user_exists = $this->db->select("staff")
                              ->condition("user_id", $this->request['id'])
                              ->countQuery()
                              ->execute()
                              ->fetchField();
      
      if ($user_exists) {
        $this->db->delete("staff")
                 ->condition("user_id", $this->request['id'])
                 ->execute();
        $dwoo_data['notice_type'] = 'success';
        $dwoo_data['notice'] = _gettext('User successfully deleted!');
      } else {
        $dwoo_data['notice_type'] = 'error';
        $dwoo_data['notice'] = _gettext('A user with that ID does not exist');
      }
    }
    
    $staff = $this->db->select("staff")
                      ->fields("staff")
                      ->orderBy("user_type")
                      ->orderBy("user_add_time")
                      ->execute()
                      ->fetchAll();
                      
    $dwoo_data['staffmembers'] = $staff;

        kxTemplate::output("manage/staff_show", $dwoo_data);
  }
}
?>
