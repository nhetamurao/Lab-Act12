<?php

namespace App\Models;

use App\Models\BaseModel;
use \PDO;

class UserAnswer extends BaseModel
{
    protected $user_id;
    protected $answers;

    public function save($user_id, $answers)
    {
        $this->user_id = $user_id;
        $this->answers = $answers;

        $sql = "INSERT INTO users_answers
                SET
                    user_id=:user_id,
                    answers=:answers";        
        $statement = $this->db->prepare($sql);
        $statement->execute([
            'user_id' => $user_id,
            'answers' => $answers
        ]);
    
        return $statement->rowCount();
    }

    public function saveAttempt($user_id, $exam_items, $score)
    {
        $sql = "INSERT INTO exam_attempts
                SET
                    user_id=:user_id,
                    exam_items=:exam_items,
                    score=:score";   
        $statement = $this->db->prepare($sql);
        $statement->execute([
            'user_id' => $user_id,
            'exam_items' => $exam_items,
            'score' => $score
        ]);
    }

    public function getAllAttempts()
    {
        $sql = "
            SELECT ea.attempt_date, u.complete_name, ea.exam_items AS items, ea.score AS total_score, ea.id AS attempt_id
            FROM exam_attempts ea
            JOIN users u ON ea.user_id = u.id
            ORDER BY ea.attempt_date DESC
        ";
        
        $statement = $this->db->prepare($sql);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttemptDetails($id)
    {
        $sql = "
            SELECT ea.attempt_date, u.complete_name, u.email, ea.exam_items AS items, ea.score AS total_score
            FROM exam_attempts ea
            JOIN users u ON ea.user_id = u.id
            WHERE ea.id = :id
        ";
        
        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
}
