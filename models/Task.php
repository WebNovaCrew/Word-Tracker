<?php
class Task
{
    private $conn;
    private $table_name = "tasks";

    public $id;
    public $plan_id;
    public $text;
    public $date;
    public $order_index;
    public $is_completed;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getTasksByPlan($plan_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE plan_id = ? ORDER BY order_index ASC, created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $plan_id);
        $stmt->execute();
        return $stmt;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (plan_id, text, date, order_index, is_completed) 
                  VALUES (:plan_id, :text, :date, :order_index, :is_completed)";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->text = htmlspecialchars(strip_tags($this->text));

        // Bind
        $stmt->bindParam(":plan_id", $this->plan_id);
        $stmt->bindParam(":text", $this->text);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":order_index", $this->order_index);
        $stmt->bindParam(":is_completed", $this->is_completed);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update()
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET text = :text, 
                      date = :date, 
                      order_index = :order_index, 
                      is_completed = :is_completed 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->text = htmlspecialchars(strip_tags($this->text));

        $stmt->bindParam(":text", $this->text);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":order_index", $this->order_index);
        $stmt->bindParam(":is_completed", $this->is_completed);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    public function updateOrder($tasks)
    {
        // Expects array of {id, order_index}
        $query = "UPDATE " . $this->table_name . " SET order_index = :order_index WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        foreach ($tasks as $task) {
            $stmt->bindParam(":order_index", $task['order_index']);
            $stmt->bindParam(":id", $task['id']);
            if (!$stmt->execute())
                return false;
        }
        return true;
    }
}
?>