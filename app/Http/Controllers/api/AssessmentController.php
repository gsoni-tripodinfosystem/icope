<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Module;
use App\Models\Answer;
use App\Models\User;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function getModules(Request $req,)
    {
        $email = $req->email;
        $module = Module::orderBy('id', 'desc');
        if ($req->moduleId) {
            $module = $module->where('id', $req->moduleId)->first();
        } else {
            $module = $module->get();
        }

        return response()->json(['status' => 'success', 'success' => true, 'message' => 'success', 'data' => $module], 200);
    }
    public function getAssessment(Request $req)
    {
        $assessment = Assessment::where('moduleId', $req->module_id)->get();
        return response()->json(['status' => 'success', 'success' => true, 'message' => 'success', 'data' => $assessment], 200);
    }
    public function getPrePostTest(Request $req)
    {
        $assessment = Assessment::where('is_first_question', 1)->get();
        return response()->json(['status' => 'success', 'success' => true, 'message' => 'success', 'data' => $assessment], 200);
    }
    public function submitAssessmentQuestions(Request $request)
    {
        $moduleIID = ($request->module_id);
        $deviceToken = $request->device_id;
        $assestments = Assestments::where('moduleId', $moduleIID)->get();
        $totalQuestion = 0;
        $CorrectAnswer = 0;
        foreach ($assestments as $assestment) {
            $qu = "question_" . $assestment->id;
            if ($request->$qu == $assestment->answer) {
                $CorrectAnswer++;
            }
            $totalQuestion++;
        }
        $Answer = Answer::where('userID', $deviceToken)->where('moduleId', $moduleIID)->first();
        if (!$Answer) {
            $Answer = new Answer();
        }

        $Answer->moduleId = $moduleIID;
        $Answer->userID = $deviceToken;   ///$this->request->data->userID;
        $Answer->answer = json_encode($request->all());
        $Answer->status = 1;
        if ($Answer->save()) {
            return response()->json(['status' => 'success', 'success' => true, 'message' => base64_encode('Thanks for submitting the assessment. You have answered ' . $CorrectAnswer . " correct answers out of " . $totalQuestion)], 200);
        } else {
            return response()->json(['status' => 'failed', 'success' => false, 'message' => 'something went wrong'], 200);
        }
    }
    public function submitQuestionnaireQuestions(Request $request)
    {
        $deviceToken = $request->device_id;
        $countryCode = $request->country_code;
        $questionnaireType = $request->questionnaireType;
        $questions = $request->question;

        $assestments = Assessment::where('is_first_question', '1')->get();
        $usr = User::where('email', base64_decode($deviceToken))->first();
        $moduleIID = ($request->module_id);

        $totalQuestion = 0;
        $CorrectAnswer = 0;
        foreach ($assestments as $assestment) {
            $qu = "question_" . $assestment->id;
            if ($request->$qu == $assestment->answer) {
                $CorrectAnswer++;
            }
            $totalQuestion++;
        }
        if ($questionnaireType == 'post') {
            $type_id = time() . '__' . $usr->id . '__' . time();
            $Answer = Answer::where('userID', $deviceToken)->where('moduleId', $moduleIID)->first();
            if (!$Answer) {
                $Answer = new Answer();
            }
            $Answer->type = 'questionnaire';
            $Answer->type_id = $type_id;
            $Answer->attempted_correct_questions = $CorrectAnswer;
            $Answer->attempted_total_questions = $totalQuestion;
            $Answer->countryCode = $request->countryCode;
            $Answer->userID = $usr->id;
            $Answer->answer = json_encode($request->all());
            $Answer->status = 1;
            $Answer->created = date('Y-m-d H:i:s');
            if ($Answer->save()) {
                return response()->json(['status' => 'success', 'success' => true, 'message' => base64_encode('Thanks for submitting the assessment. You have answered ' . $CorrectAnswer . " correct answers out of " . $totalQuestion)], 200);
            } else {
                return response()->json(['status' => 'failed', 'success' => false, 'message' => 'something went wrong'], 200);
            }
        }else{
            if($usr){
                $usr->is_pretest_completed = 1;
                if($usr->save()){
                    return response()->json(['status' => 'success', 'success' => true, 'message' => base64_encode('Thanks for submitting the assessment. You have answered ' . $CorrectAnswer . " correct answers out of " . $totalQuestion)], 200);
                } else {
                    return response()->json(['status' => 'failed', 'success' => false, 'message' => 'something went wrong'], 200);
                }
            }
        }
    }
    function searchContent(Request $request)
    {
        $response = array('status' => 'failed', 'message' => 'HTTP method not allowed');
        $dataArray = [];
        if ($request->param) {


            $keyword = @$request->param;
            if (!empty($keyword)) {
                $query = Module::where('status', 1)
                    ->orWhere('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%")
                    ->get();
                if ($query) {
                    foreach ($query as $key => $data) {
                        $dataArray[$key]['id'] = $data->id;
                        $dataArray[$key]['module_name'] = $data->name;
                        $title_count = substr_count($data->title, $keyword);
                        $description_count = substr_count($data->description, $keyword);
                        $dataArray[$key]['count'] = ((int) $title_count + (int) $description_count);
                    }
                    return response()->json(['status' => 'success', 'success' => true, 'message' => 'success', 'data' => $dataArray], 200);
                }
            }
        } else {
            return response()->json(['status' => 'success', 'success' => true, 'message' => 'Please enter params.'], 200);
        }
    }
}
