<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Stratum\Controller\BullhornController;
use \App\Http\Controllers\FormResponseController;
use \Stratum\Model\Candidate;
use \Stratum\Model\CorporateUser;
use App\Prospect;
use Log;
use Cache;
use Auth;

class CorporateUserController extends Controller
{
  public function index() {
      $frc = new FormResponseController();
      return $frc->index(0);

      /***
      $candidates = $this->load_candidates();
      $data['candidates'] = array();
      $prospect = new \App\Prospect();
      $cuser = $this->loadCorporateUser();
      $candidate = new Candidate();
      $candidate->set("owner", $cuser->get("id"));
      $candidate->set("id", 0);
      $data['candidate'] = $candidate;
      return view('formresponse')->with($data);
      ***/
  }

  public function load_candidates() {
      $candidates = null;
      $cuser = $this->loadCorporateUser();
      if ($cuser->get("id")==0) {
          return array();
      }
      $candidates = $cuser->getAssocCandidates();
      if (!$candidates) {
          Log::debug("Finding associated candidates");
          $bc = new BullhornController();
          $candidates = $bc->findAssocCandidates($cuser);
          //candidates are a hashMap of the step names in the workflow
          //and the associated Candidate records
          //need to shorten these up
          $candidates = $this->replace_key_function($candidates, 'Reg Form Sent', 'RFS');
          $candidates = $this->replace_key_function($candidates, 'Form Completed', 'FC');
          $candidates = $this->replace_key_function($candidates, 'Interview Done', 'IC');
          //Log::debug($candidates);
          if ($candidates != null) {
              Log::debug("Storing candidates with corporate user");
              $cuser->setAssocCandidates($candidates);
          }
          $id = $cuser->get("id");
          Log::debug("Putting corporate user ".$id." into cache with loaded candidates");
          Cache::add("user".$id, $cuser, 60);
      }
      return $candidates;
  }

  public function flushCandidatesFromCache() {
      Cache::flush();
  }

  public function flushCandidateStatusFromCache() {
      $cuser = $this->loadCorporateUser();
      $id = $cuser->get("id");
      Log::debug("Removing corporate user ".$id." from cache");
      Cache::forget("user".$id);

  }

  public function refresh() {
      $this->flushCandidatesFromCache();
      return $this->index();
  }

  private function loadCorporateUser() {
      $user = Auth::user();
      $id = $user->bullhorn_id;
      Log::debug("User has ID ".$id);
      $cuser = null;
      if ($id == 0) {
          if (Cache::has("user".$id)) {
              Log::debug("Loading corporate user from cache: ".$id);
              $cuser = Cache::get("user".$id);
              return $cuser;
          }
      }
      if (!$id) {
          //load by name
          $name = $user->name;
          Log::debug("User has name ".$name);
          $bc = new BullhornController();
          $cuser = $bc->findCorporateUserByName($name);
          $theId = $cuser->get("id");
          if ($theId) {
              $user->bullhorn_id=$theId;
              $user->save();
          }
          Cache::add("user".$theId, $cuser, 60);
      } else {
          //we have a bullhorn id
          $cuser = null;
          if (Cache::has("user".$id)) {
              Log::debug("Loading corporate user from cache: ".$id);
              $cuser = Cache::get("user".$id);
          } else {
              //load the corporate user data from Bullhorn
              $cuser = new \Stratum\Model\CorporateUser();
              $cuser->set("id", $id);
              $bc = new BullhornController();
              $cuser = $bc->loadCorporateUser($cuser);
          }
      }
      if ($user->bullhorn_id==0) {
          $cuser = new \Stratum\Model\CorporateUser();
          $cuser->setName($user->name);
          $cuser->set("id", 0);
      }
      return $cuser;
  }

  private function replace_key_function($array, $key1, $key2) {
      if ($array) {
          $keys = array_keys($array);
          $index = array_search($key1, $keys);

          if ($index !== false) {
              $keys[$index] = $key2;
              $array = array_combine($keys, $array);
          }
      }
      return $array;
  }

}
