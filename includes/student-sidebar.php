<!-- Sidebar navigation-->
<?php
$studentUnreadCount = 0;
if (isset($mysqli) && isset($_SESSION['id'])) {
    $studentId = (int) $_SESSION['id'];
    $tableCheck = $mysqli->query("SHOW TABLES LIKE 'messages'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM messages WHERE receiver_role='student' AND receiver_id=? AND is_read=0");
        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->bind_result($studentUnreadCount);
            $stmt->fetch();
            $stmt->close();
        }
    }
}
?>
<nav class="sidebar-nav">

    <ul id="sidebarnav">
    
        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="dashboard.php"
        aria-expanded="false"><i data-feather="home" class="feather-icon"></i><span
         class="hide-menu">Dashboard</span></a></li>

        <li class="list-divider"></li>

        <li class="nav-small-cap"><span class="hide-menu">Features</span></li>
                            
        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="book-hostel.php"
        aria-expanded="false"><i class="fas fa-h-square"></i><span
        class="hide-menu">Book Homestay</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="chat.php"
        aria-expanded="false"><i class="fas fa-comments"></i><span
        class="hide-menu">Chat with Admin<?php if ($studentUnreadCount > 0) { ?> <span class="badge badge-pill badge-danger ml-2"><?php echo $studentUnreadCount; ?></span><?php } ?></span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="room-details.php"
        aria-expanded="false"><i class="fas fa-bed"></i><span
        class="hide-menu">My Room Details</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="log-activity.php"
        aria-expanded="false"><i class="fas fa-cogs"></i><span
        class="hide-menu">Log Activities</span></a></li>
                           
    </ul>
</nav>
<!-- End Sidebar navigation -->