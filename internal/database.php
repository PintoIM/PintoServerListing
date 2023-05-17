<?php
    /**
     * The database for a Pinto! server listing
     */
    class ServerListingDatabase {
        private string $server_ip;
        private int $server_port;
        private string $username;
        private string $password;
        private string $database;
        private string $tableName;
        private mysqli $connection;

        // TEXT + VARCHAR(15) + INT + INT + INT + DATETIME
        // name + ip + port + users + max_users + expire
        public function __construct(string $server_ip, int $server_port, string $username, 
            string $password, string $database, string $tableName) {
            $this->server_ip = $server_ip;
            $this->server_port = $server_port;
            $this->username = $username;
            $this->password = $password;
            $this->database = $database;
            $this->tableName = $tableName;
            $this->connection = new mysqli($this->server_ip, $this->username, 
                $this->password, $this->database, $this->server_port);

            if ($this->connection->connect_error) {
                throw new Exception("Unable to connect");
            }

            $this->createTable();
        }

        private function tableExists() {
            try {
                return $this->connection->query("DESCRIBE {$this->tableName}") != null;
            } catch (Exception $ex) {
                return false;
            }
        } 

        private function createTable() {
            if ($this->tableExists()) return false;
            $this->connection->query("CREATE TABLE {$this->tableName}
             (name TEXT, ip VARCHAR(15), port INT, users INT, max_users INT, expire DATETIME)");
        }

        /**
         * Deletes all expired servers
         */
        public function deleteExpired() {
            return $this->connection->query("DELETE FROM {$this->tableName} WHERE expire < UTC_TIMESTAMP()");
        }

        /**
         * Checks if the specified server has already been added
         * 
         * @param \PintoServer $server the server to check
         */
        public function isServerAdded(PintoServer $server) {
            $this->deleteExpired();
            $query = $this->connection->query("SELECT * FROM {$this->tableName} WHERE {$server->getSQLSelector()}");
            return $query->num_rows > 0;
        }

        /**
         * Adds the specified server to the database
         * @param \PintoServer $server the server to add 
         */
        public function addServer(PintoServer $server) {
            if ($this->isServerAdded($server)) return false;
            $statement = $this->connection->prepare("INSERT INTO {$this->tableName} VALUES (?, ?, ?, ?, ?, ?)");
            $statement->bind_param("ssiiis", $server->name, $server->ip, $server->port, $server->users, $server->maxUsers, $server->expire);
            return $statement->execute();
        }

        /**
         * Deletes the specified server from the database
         * 
         * @param \PintoServer $server the server to delete
         */
        public function removeServer(PintoServer $server) {
            if (!$this->isServerAdded($server)) return false;
            return $this->connection->query("DELETE FROM {$this->tableName} WHERE {$server->getSQLSelector()}");
        }

        /**
         * Updates the specified server in the database
         * 
         * @param \PintoServer $server the server to update
         */
        public function updateServer(PintoServer $server) {
            if (!$this->isServerAdded($server)) return false;
            $statement = $this->connection->prepare("UPDATE {$this->tableName}
             SET name=?, ip=?, port=?, users=?, max_users=?, expire=? WHERE {$server->getSQLSelector()}");
            $statement->bind_param("ssiiis", $server->name, $server->ip, $server->port, $server->users, $server->maxUsers, $server->expire);
            return $statement->execute();
        }

        /**
         * Fills the specified IP and port server with the appropriate information
         * 
         * @param \PintoServer $server the server to fill with information
         */
        public function getServer(PintoServer &$server) {
            if (!$this->isServerAdded($server)) return false;
            
            $result = $this->connection->query("SELECT * FROM {$this->tableName} WHERE {$server->getSQLSelector()}");
            $data = $result->fetch_assoc();

            $server->name = $data["name"];
            $server->users = intval($data["users"]);
            $server->maxUsers = intval($data["max_users"]);
            $server->expire = $data["expire"];

            return true;
        }

        /**
         * Gets all servers that are in the database
         */
        public function getServers() {
            $this->deleteExpired();
            
            $servers = array();
            $result = $this->connection->query("SELECT * FROM {$this->tableName} WHERE expire > UTC_TIMESTAMP()");

            $i = 0;
            while ($i < $result->num_rows) {
                $data = $result->fetch_assoc();
                $server = new PintoServer($data["name"], $data["ip"], intval($data["port"]),
                 intval($data["users"]), intval($data["max_users"]), $data["expire"]);
                array_push($servers, $server);
                $i++;
            }

            return $servers;
        }
    }

    /**
     * A Pinto! server listing 
     */
    class PintoServer {
        public string $name;
        public string $ip;
        public int $port;
        public int $users;
        public int $maxUsers;
        // Y-m-d H:i:s
        public string $expire;

        public function __construct(string $name, string $ip, int $port, int $users, int $maxUsers, string $expire) {
            $this->name = $name;
            $this->ip = $ip;
            $this->port = $port;
            $this->users = $users;
            $this->maxUsers = $maxUsers;
            $this->expire = $expire;
        }

        public function getSQLSelector() {
            return "ip='{$this->ip}' AND port={$this->port}";
        }

        public static function getForSQLServer(string $ip, int $port) {
            return new PintoServer("", $ip, $port, 0, 0, 0, "1970-01-01 00:00:00");
        }
    }

    function getDB() {
		// Replace this with your information
		// IP PORT USERNAME PASSWORD DATABASE TABLE
        return new ServerListingDatabase("127.0.0.1", 3306, "pintoservers", "password", "pintoservers", "servers");
    }
?>