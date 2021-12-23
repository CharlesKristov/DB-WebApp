<?php  
  class Student {
    private $db;

    public function __construct(PDO $db) {
      $this->db = $db;
    }

    public function getStudent($first_name, $last_name) {
      try {
        $statement = $this->db->prepare("SELECT * FROM `student` WHERE first_name = '{$first_name}' AND `last_name` = '{$last_name}'");
        $statement->execute();
      } catch(PDOException $e) {
        echo $e;
        return null;
      }
      return $statement->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function getClassrooms($student_id) {
      try {
        $statement = $this->db->prepare("SELECT * FROM `classroom_detail` WHERE `student_id` = {$student_id}");
        $statement->execute();
      } catch(PDOException $e) {
        echo $e;
        return null;
      }
      return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
  }
?>