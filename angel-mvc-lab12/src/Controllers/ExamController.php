<?php 

namespace App\Controllers;

use App\Models\User;
use App\Models\Question;
use App\Models\UserAnswer;
use FPDF;

class ExamController extends BaseController
{
    public function registrationForm()
    {
        $this->initializeSession();
        return $this->render('registration-form');
    }

    public function register()
    {
        $this->initializeSession();
        $data = $_POST;

        // Save the registration to database
        $userObj = new User();
        $user_id = $userObj->save($data);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['complete_name'] = $data['complete_name'];
        $_SESSION['email'] = $data['email'];

        header("Location: /login"); // Redirect to login after registration
        exit;
    }

    public function loginForm()
    {
        return $this->render('login-form');
    }

    public function login()
    {
        $this->initializeSession();
        $email = $_POST['email'];
        $password = $_POST['password'];

        $userObj = new User();
        $user = $userObj->verifyLogin($email, $password);

        if ($user) {
            // Set session variables if login is successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['complete_name'] = $user['complete_name'];
            $_SESSION['email'] = $user['email'];

            // Check if there is a redirect URL
            if (isset($_SESSION['redirect_to'])) {
                $redirectUrl = $_SESSION['redirect_to'];
                unset($_SESSION['redirect_to']); // Clear the redirect after using it
                header("Location: " . $redirectUrl);
            } else {
                header("Location: /exam"); // Default redirect
            }
            exit;
        } else {
            // Redirect back to login form on failure
            header("Location: /login?error=invalid_credentials");
            exit;
        }
    }

    public function exam()
    {
        $this->initializeSession();

        // Restrict access to authenticated users only
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        $item_number = 1;

        if (isset($_POST['item_number']) && isset($_POST['answer'])) {
            array_push($_SESSION['answers'], $_POST['answer']);
            $_SESSION['item_number'] = $_POST['item_number'] + 1;
        }

        if (!isset($_SESSION['item_number'])) {
            $_SESSION['item_number'] = $item_number;
            $_SESSION['answers'] = [false];
        } else {
            $item_number = $_SESSION['item_number'];
        }

        $questionObj = new Question();
        $question = $questionObj->getQuestion($item_number);

        if (is_null($question) || !$question) {
            $user_id = $_SESSION['user_id'];
            $json_answers = json_encode($_SESSION['answers']);

            $userAnswerObj = new UserAnswer();
            $userAnswerObj->save($user_id, $json_answers);
            $score = $questionObj->computeScore($_SESSION['answers']);
            $items = $questionObj->getTotalQuestions();
            $userAnswerObj->saveAttempt($user_id, $items, $score);

            header("Location: /result");
            exit;
        }

        $question['choices'] = json_decode($question['choices']);
        return $this->render('exam', $question);
    }

    public function result()
    {
        $this->initializeSession();
        $data = $_SESSION;
        $questionObj = new Question();
        $data['questions'] = $questionObj->getAllQuestions();
        $answers = $_SESSION['answers'];
        foreach ($data['questions'] as &$question) {
            $question['choices'] = json_decode($question['choices']);
            $question['user_answer'] = $answers[$question['item_number']];
        }
        $data['total_score'] = $questionObj->computeScore($_SESSION['answers']);
        $data['question_items'] = $questionObj->getTotalQuestions();

        session_destroy();

        return $this->render('result', $data);
    }

    public function listExaminees()
    {
        session_start();

        // Verify that the user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_to'] = '/examinees'; // Save intended destination
            header("Location: /login");
            exit;
        }

        // Fetch the examination attempts
        $userAnswerObj = new UserAnswer();
        $attempts = $userAnswerObj->getAllAttempts();

        // Render the examinees view with the fetched attempts
        return $this->render('examinees', ['attempts' => $attempts]);
    }

    public function exportAttemptToPDF($id)
    {
        // Fetch the attempt details
        $userAnswerObj = new UserAnswer();
        $attemptDetails = $userAnswerObj->getAttemptDetails($id);
        
        if (!$attemptDetails) {
            echo "Attempt not found.";
            return;
        }

        // Generate PDF using FPDF
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Set title and details
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Exam Attempt Report', 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Attempt Date: ' . $attemptDetails['attempt_date'], 0, 1);
        $pdf->Cell(0, 10, 'Examinee Name: ' . $attemptDetails['complete_name'], 0, 1);
        $pdf->Cell(0, 10, 'Examinee Email: ' . $attemptDetails['email'], 0, 1);
        $pdf->Cell(0, 10, 'Total Items: ' . $attemptDetails['items'], 0, 1);
        $pdf->Cell(0, 10, 'Total Score: ' . $attemptDetails['total_score'], 0, 1);

        $pdf->Output();
        exit;
    }
}
