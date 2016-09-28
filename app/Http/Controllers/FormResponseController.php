<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CandidateController as CanCon;
use \Stratum\Controller\FormController;
use \Stratum\Controller\CandidateController;
use \Stratum\Model\FormResult;
use \Stratum\Model\Candidate;
use Storage;
use Log;
use Auth;
use Mail;

class FormResponseController extends Controller
{
    public function index($id) {
        $cuc = new CorporateUserController();
        $controller = new \Stratum\Controller\FormController();
        $ccontroller = new \Stratum\Controller\CandidateController();
        //$entityBody = Storage::disk('local')->get($id.'.txt');
        $form = $controller->setupForm();
        $formResult = new \Stratum\Model\FormResult();
        $formResult->set("form", $form);
        $candidate = new \Stratum\Model\Candidate();
        $candidate->set("id", $id);
        //expand/collapse all button
        $data['form'] = $form;
        $data['formResult'] = $formResult;
        $data['candidate'] = $candidate;
        $data['candidates'] = $cuc->load_candidates();
        $data['page_title'] = "Form Response";
        return view('formresponse')->with($data);
    }



    public function confirmValues(Request $request) {
        $id = $request->input("id");
        $fc = new \Stratum\Controller\FormController();
        $cc = new \Stratum\Controller\CandidateController();
        $cuc = new CorporateUserController();
        $form = $fc->setupForm();
        $formResult = new \Stratum\Model\FormResult();
        $formResult->set("form", $form);
        $candidate = new \Stratum\Model\Candidate();
        $candidate = $cc->populateFromRequest($candidate, $request->all(), $formResult);

        //$data['message'] = 'Debugging only, nothing uploaded to Bullhorn';

        $bc = new \Stratum\Controller\BullhornController();
        $retval = $bc->submit($candidate);
        if (array_key_exists("errorMessage", $retval)) {
            $data['errormessage']['message'] = $retval['errorMessage'];
            $data['errormessage']['errors'] = $retval['errors'];
            $data['message'] = "Problem uploading data";
        } else {
            $data['message'] = "Data Uploaded";
        }
        $data['candidates'] = $cuc->load_candidates();
        $data['thecandidate'] = $candidate;
        $fc = new \Stratum\Controller\FormController();
        $data['form'] = $fc->setupForm();

        return view('candidate')->with($data);
    }
}
