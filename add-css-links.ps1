$files = @(
    'student/book-hostel.php',
    'student/acc-setting.php',
    'student/room-details.php',
    'student/profile.php',
    'student/log-activity.php',
    'student/chat.php',
    'admin/acc-setting.php',
    'admin/add-rooms.php',
    'admin/bookings.php',
    'admin/chat.php',
    'admin/edit-courses.php',
    'admin/add-courses.php',
    'admin/manage-courses.php',
    'admin/edit-room.php',
    'admin/manage-rooms.php',
    'admin/manage-students.php',
    'admin/profile.php',
    'admin/students-profile.php',
    'admin/view-students-acc.php',
    'admin/register-student.php'
)

foreach ($file in $files) {
    $content = Get-Content $file -Raw
    $isStudent = $file -like 'student/*' -or $file -like 'admin/*'
    $linkPath = if ($isStudent) { '../dist/css/custom-colors.css' } else { 'dist/css/custom-colors.css' }
    
    if ($content -notmatch 'custom-colors') {
        $newLink = '<link href="' + $linkPath + '" rel="stylesheet">'
        $styleLinkPattern = '<link href="[^"]*style\.min\.css"[^>]*>'
        $updated = $content -replace $styleLinkPattern, ('$0' + "`n    " + $newLink)
        Set-Content $file $updated -Encoding ASCII
        Write-Host "Updated: $file"
    }
}


