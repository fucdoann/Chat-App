<?php
    class groupChat{
        private $group_name;
        private $host_user_id;
        private $group_id;
        protected $connect;
        public function __construct()
	        {
            require_once("Database_connection.php");

            $database_object = new Database_connection;

            $this->connect = $database_object->connect();
        }
        public function getGroupId(){
            return $this->group_id;
        }
        public function setGroupId($group_id){
            $this->group_id = $group_id;
        }
        public function getHostUserId(){
            return $this->host_user_id;
        }
        public function setHostUserId($host_user_id){
            $this->host_user_id = $host_user_id;
        }
        public function getGroupName(){
            return $this->group_name;
        }
        public function setGroupName($group_name){
            $this->group_name = $group_name;
        }
        public function getAllGroupChat(){
            $query = "
            SELECT DISTINCT group_id FROM users_group 
            WHERE user_id = :user_id
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':user_id',$this->host_user_id);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }
        public function createChatGroup(){
            $query = "
            INSERT INTO group_chat(group_name,host_user_id)
            VALUES(:group_name,:host_user_id)
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':group_name',$this->group_name);
            $statement->bindParam(':host_user_id',$this->host_user_id);
            $statement->execute();
            $group_id = $this->connect->lastInsertId();
            $this->setGroupId($group_id);
            $query = "
            INSERT INTO users_group(user_id,group_id)
            VALUES(:host_user_id,:group_id)
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':group_id',$this->group_id);
            $statement->bindParam(':host_user_id',$this->host_user_id);
            $statement->execute();

        }
        public function setGroupChatNameById(){
            $query = "
            SELECT * FROM group_chat
            WHERE group_id = :group_id
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':group_id',$this->group_id);
            $statement->execute();
            $group = $statement->fetch(PDO::FETCH_ASSOC);
            $group_name = $group['group_name'];
            $this->setGroupName($group_name);
        }
        public function getHostUserIdByGroupId(){
            $query="
            SELECT * FROM group_chat
            WHERE group_id = :group_id
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':group_id',$this->group_id);
            $statement->execute();
            $group = $statement->fetch(PDO::FETCH_ASSOC);
            $host_user_id = $group['host_user_id'];
            return $host_user_id;
        }
        public function getAllUserByGroupId(){
            $query="
            SELECT * FROM chat_user_table
            INNER JOIN users_group 
            ON chat_user_table.user_id = users_group.user_id
            WHERE users_group.group_id = :group_id
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':group_id',$this->group_id);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        public function getAllNotUserByGroupId(){
            $query="
            SELECT * FROM chat_user_table
            WHERE user_id NOT IN
            (SELECT user_id FROM users_group WHERE group_id=:group_id)
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':group_id',$this->group_id);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }
        public function addMember($user_id){
            $query="
            INSERT INTO users_group(user_id,group_id)
            VALUES (:user_id,:group_id)
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':user_id',$user_id);
            $statement->bindParam(':group_id',$this->group_id);
            $statement->execute();
        }
        public function deleteMember($user_id){
            $query="
            DELETE FROM users_group
            WHERE user_id = :user_id AND group_id = :group_id
            ";
            $statement = $this->connect->prepare($query);
            $statement->bindParam(':user_id',$user_id);
            $statement->bindParam(':group_id',$this->group_id);
            $statement->execute();
        }





    }
?>