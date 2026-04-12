<!-- Sidebar navigation-->
<?php
$adminUnreadCount = 0;
if (isset($mysqli)) {
    $tableCheck = $mysqli->query("SHOW TABLES LIKE 'messages'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $result = $mysqli->query("SELECT COUNT(*) AS unread FROM messages WHERE receiver_role='admin' AND receiver_id=0 AND is_read=0");
        if ($result) {
            $row = $result->fetch_assoc();
            $adminUnreadCount = (int) ($row['unread'] ?? 0);
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
                            
        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="register-student.php"
        aria-expanded="false"><i class="fas fa-user-plus"></i><span
        class="hide-menu">Register Student</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="view-students-acc.php"
        aria-expanded="false"><i class="fas fa-user-circle"></i><span
        class="hide-menu">View Student Acc.</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="bookings.php"
        aria-expanded="false"><i class="fas fa-h-square"></i><span
        class="hide-menu">Book Homestay</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="manage-students.php"
        aria-expanded="false"><i class="fas fa-users"></i><span
        class="hide-menu">Homestay Students</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="manage-rooms.php"
        aria-expanded="false"><i class="fas fa-bed"></i><span
        class="hide-menu">Manage Rooms</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="manage-courses.php"
        aria-expanded="false"><i class="fas fa-book"></i><span
        class="hide-menu">Manage Courses</span></a></li>

        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="chat.php"
        aria-expanded="false"><i class="fas fa-comments"></i><span
        class="hide-menu">Student Chat<?php if ($adminUnreadCount > 0) { ?> <span class="badge badge-pill badge-danger ml-2"><?php echo $adminUnreadCount; ?></span><?php } ?></span></a></li>
                           
    </ul>
</nav>
<!-- End Sidebar navigation -->