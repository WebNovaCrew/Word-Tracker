<?php
class Plan
{
    private $conn;
    private $table_name = "plans";

    // Object properties
    public $id;
    public $user_id;
    public $name;
    public $content_type;
    public $activity_type;
    public $start_date;
    public $end_date;
    public $goal_amount;
    public $strategy;
    public $intensity;
    public $created_at;
    public $updated_at;
    public $is_archived;
    public $status;
    public $color_code;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get community plans
    public function getCommunityPlans($limit = 20)
    {
        $query = "SELECT p.*, u.username 
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.id
                  ORDER BY p.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Get plan progress history
    public function getPlanProgressHistory($plan_id)
    {
        $query = "SELECT date, target, logged as actual_count 
                  FROM plan_days 
                  WHERE plan_id = :plan_id 
                  ORDER BY date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plan_id', $plan_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user's plans
    public function getUserPlans($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Get user's plans with progress summary
    public function getPlansWithProgress()
    {
        $query = "SELECT p.*, 
                  COALESCE((SELECT SUM(logged) FROM plan_days WHERE plan_id = p.id), 0) as completed_amount
                  FROM " . $this->table_name . " p
                  WHERE user_id = :user_id
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    // Get paginated plans with filter
    public function getPlansPaginated($user_id, $page, $limit, $is_archived = 0)
    {
        $offset = ($page - 1) * $limit;

        // Handle explicit archive parameter
        $archiveClause = "is_archived = :is_archived";
        // If we want to show 'active' plans, we usually mean NOT archived.

        $query = "SELECT p.*, 
                  COALESCE((SELECT SUM(logged) FROM plan_days WHERE plan_id = p.id), 0) as completed_amount
                  FROM " . $this->table_name . " p
                  WHERE user_id = :user_id AND " . $archiveClause . "
                  ORDER BY created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':is_archived', $is_archived, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function countPlans($user_id, $is_archived = 0)
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_archived = :is_archived";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':is_archived', $is_archived, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get single plan
    public function getPlanById($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->name = $row['name'];
            $this->content_type = $row['content_type'];
            $this->activity_type = $row['activity_type'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->goal_amount = $row['goal_amount'];
            $this->strategy = $row['strategy'];
            $this->intensity = $row['intensity'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Create plan
    public function create($schedule = [])
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (user_id, name, content_type, activity_type, start_date, end_date, goal_amount, strategy, intensity)
                  VALUES (:user_id, :name, :content_type, :activity_type, :start_date, :end_date, :goal_amount, :strategy, :intensity)";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":content_type", $this->content_type);
        $stmt->bindParam(":activity_type", $this->activity_type);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":goal_amount", $this->goal_amount);
        $stmt->bindParam(":strategy", $this->strategy);
        $stmt->bindParam(":intensity", $this->intensity);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();

            // Insert schedule if provided
            if (!empty($schedule)) {
                $this->insertSchedule($schedule);
            }

            return true;
        }

        return false;
    }

    // Insert schedule
    private function insertSchedule($schedule)
    {
        $query = "INSERT INTO plan_days (plan_id, date, target, logged) 
                  VALUES (:plan_id, :date, :target, 0)";
        $stmt = $this->conn->prepare($query);

        foreach ($schedule as $day) {
            $stmt->bindParam(':plan_id', $this->id);
            $stmt->bindParam(':date', $day['date']);
            $stmt->bindParam(':target', $day['target']);
            $stmt->execute();
        }
    }

    // Update plan
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET name = :name,
                      content_type = :content_type,
                      activity_type = :activity_type,
                      goal_amount = :goal_amount,
                      strategy = :strategy,
                      intensity = :intensity
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":content_type", $this->content_type);
        $stmt->bindParam(":activity_type", $this->activity_type);
        $stmt->bindParam(":goal_amount", $this->goal_amount);
        $stmt->bindParam(":strategy", $this->strategy);
        $stmt->bindParam(":intensity", $this->intensity);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Delete plan
    public function delete($id = null)
    {
        if ($id) {
            $this->id = $id;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    // Archive or Unarchive plan
    public function archive($id, $isArchived = true)
    {
        $status = $isArchived ? 'archived' : 'active';
        $archivedVal = $isArchived ? 1 : 0;

        $query = "UPDATE " . $this->table_name . " SET is_archived = :is_archived, status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':is_archived', $archivedVal, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateProgress($plan_id, $date, $count)
    {
        // Build query
        $query = "UPDATE plan_days 
                  SET logged = :count 
                  WHERE plan_id = :plan_id AND date = :date";

        $stmt = $this->conn->prepare($query);

        // Bind params
        $stmt->bindParam(':count', $count);
        $stmt->bindParam(':plan_id', $plan_id);
        $stmt->bindParam(':date', $date);

        // Execute
        if ($stmt->execute()) {
            // Check if any row was affected
            if ($stmt->rowCount() == 0) {
                // Try insert if row doesn't exist (e.g. extending plan)
                $query = "INSERT INTO plan_days (plan_id, date, target, logged) VALUES (:plan_id, :date, 0, :count)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':count', $count);
                $stmt->bindParam(':plan_id', $plan_id);
                $stmt->bindParam(':date', $date);
                return $stmt->execute();
            }
            return true;
        }
        return false;
    }

    public function updateColor($id, $color)
    {
        $query = "UPDATE " . $this->table_name . " SET color_code = :color WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $color = htmlspecialchars(strip_tags($color));

        $stmt->bindParam(':color', $color);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
}