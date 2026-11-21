<?php
public function testdb($group = 'master') {
    $db = $this->load->database($group, TRUE);
    if ($db->conn_id) {
        echo "Connected to $group!";
    } else {
        echo "Failed to connect $group!";
    }
}

?>


