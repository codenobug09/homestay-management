<?php
session_start();
include('../includes/dbconn.php');
include('../includes/check-login.php');
check_login();
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Admin chat with students">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon.png">
    <title>Admin Chat</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link href="../dist/css/custom-colors-v2.css" rel="stylesheet">
    <style>
        .chat-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
        }

        .student-list {
            background: #fff;
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 22px 60px rgba(31, 45, 61, .12);
            min-height: 500px;
        }

        .student-item {
            cursor: pointer;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 10px;
            border: 1px solid #eef2f8;
            transition: background .2s ease, border-color .2s ease;
        }

        .student-item.active,
        .student-item:hover {
            background: #ebf3ff;
            border-color: #d4e0ff;
        }

        .student-name {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .student-email {
            font-size: 12px;
            color: #65748b;
        }

        .chat-window {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 22px 60px rgba(31, 45, 61, .12);
            padding: 16px;
            min-height: 500px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #eef2f8;
            margin-bottom: 14px;
        }

        .chat-header h5 {
            margin: 0;
        }

        .chat-feed {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-right: 4px;
        }

        .message-block {
            max-width: 70%;
            display: flex;
            flex-direction: column;
            gap: 6px;
            word-break: break-word;
        }

        .message-block.student {
            align-self: flex-start;
        }

        .message-block.admin {
            align-self: flex-end;
            text-align: right;
        }

        .message-text {
            border-radius: 18px;
            padding: 14px 18px;
            font-size: 0.95rem;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .message-block.student .message-text {
            background: #f3f7ff;
            color: #1f2f43;
        }

        .message-block.admin .message-text {
            background: #1762a3;
            color: #fff;
        }

        .message-time {
            font-size: 12px;
            color: #7c89a8;
        }

        .chat-input-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            margin-top: 16px;
        }

        .chat-input-row textarea {
            resize: none;
            border-radius: 14px;
            border: 1px solid #d9e5f5;
            padding: 14px 16px;
            min-height: 80px;
            font-size: .95rem;
            outline: none;
        }

        .chat-input-row button {
            border-radius: 14px;
            border: none;
            background: #1762a3;
            color: #fff;
            padding: 0 26px;
            font-weight: 700;
        }

        .empty-state {
            color: #5f748d;
            padding: 28px;
            text-align: center;
            margin-top: 40px;
            background: #f7f9fe;
            border-radius: 18px;
        }
    </style>
</head>

<body>
    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
    <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
        <header class="topbar" data-navbarbg="skin6">
            <?php include 'includes/navigation.php'?>
        </header>
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <?php include 'includes/sidebar.php'?>
            </div>
        </aside>
        <div class="page-wrapper">
            <div class="page-breadcrumb">
                <div class="row align-items-center">
                    <div class="col-7">
                        <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Student Chat</h4>
                        <div class="d-flex align-items-center">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0 pl-0 bg-transparent">
                                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-muted">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Chat</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="chat-layout">
                            <div class="student-list" id="student-list">
                                <h5>Students</h5>
                                <div id="students"></div>
                            </div>
                            <div class="chat-window">
                                <div class="chat-header">
                                    <div>
                                        <h5 id="chat-title">Select a student</h5>
                                        <p id="chat-subtitle" class="mb-0 text-muted">Choose a student from the left panel to start chatting.</p>
                                    </div>
                                </div>
                                <div id="chat-feed" class="chat-feed"></div>
                                <div id="chat-input-panel" class="chat-input-row d-none">
                                    <textarea id="admin-message" placeholder="Type a reply..."></textarea>
                                    <button id="send-chat">Send</button>
                                </div>
                                <div id="empty-chat" class="empty-state">No student selected. Pick a student to open the conversation.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../includes/footer.php'?>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script>
        let selectedStudentId = 0;

        function setActiveStudent(element, studentId, name) {
            selectedStudentId = studentId;
            $('#students .student-item').removeClass('active');
            $(element).addClass('active');
            $('#chat-title').text(name);
            $('#chat-subtitle').text('Keep your conversation with this student here.');
            $('#empty-chat').addClass('d-none');
            $('#chat-input-panel').removeClass('d-none');
            $('#chat-feed').empty();
            loadMessages();
        }

        function renderChatMessage(role, text, time) {
            const block = $('<div/>', { class: 'message-block ' + role });
            const bubble = $('<div/>', { class: 'message-text', text: text });
            const meta = $('<div/>', { class: 'message-time', text: time });
            block.append(bubble, meta);
            $('#chat-feed').append(block);
        }

        function loadStudents() {
            $.post('../chat-handler.php', { action: 'get_students' }, function (result) {
                if (!result.success) {
                    console.error('Failed to load students:', result.message);
                    return;
                }
                $('#students').empty();
                if (!result.students || result.students.length === 0) {
                    $('#students').append($('<div/>', { class: 'alert alert-info mt-3', text: 'No students with messages yet.' }));
                    return;
                }
                result.students.forEach(function (student) {
                    const item = $('<div/>', { class: 'student-item', 'data-id': student.id });
                    const nameRow = $('<div/>', { class: 'student-name', text: (student.name || student.email).trim() });
                    if (student.unread_count && student.unread_count > 0) {
                        nameRow.append($('<span/>', {
                            class: 'badge badge-pill badge-danger ml-2',
                            text: student.unread_count
                        }));
                    }
                    item.append(nameRow);
                    item.append($('<div/>', { class: 'student-email', text: student.email }));
                    if (student.last_message) {
                        item.append($('<div/>', { class: 'student-email', text: student.last_message }).css('margin-top', '6px'));
                    }
                    item.on('click', function () {
                        setActiveStudent(this, student.id, (student.name || student.email).trim());
                    });
                    $('#students').append(item);
                });
            }).fail(function(xhr, status, error) {
                console.error('AJAX error loading students:', error);
                $('#students').append($('<div/>', { class: 'alert alert-danger mt-3', text: 'Error loading students.' }));
            });
        }

        function loadMessages() {
            if (!selectedStudentId) return;
            $.post('../chat-handler.php', {
                action: 'load_messages',
                sender_role: 'admin',
                conversation_with: selectedStudentId
            }, function (result) {
                if (!result.success) {
                    console.error('Failed to load messages:', result.message);
                    $('#chat-feed').empty().append($('<div/>', { class: 'empty-state', text: 'Error loading messages: ' + result.message }));
                    return;
                }
                $('#chat-feed').empty();
                if (!result.messages || result.messages.length === 0) {
                    $('#chat-feed').append($('<div/>', { class: 'empty-state', text: 'No conversation exists yet. Send the first reply.' }));
                    return;
                }
                result.messages.forEach(function (msg) {
                    const role = msg.sender_role === 'admin' ? 'admin' : 'student';
                    renderChatMessage(role, msg.message, msg.created_at || '');
                });
                $('#chat-feed').scrollTop($('#chat-feed')[0].scrollHeight);
            }).fail(function(xhr, status, error) {
                console.error('AJAX error loading messages:', error);
                $('#chat-feed').empty().append($('<div/>', { class: 'empty-state text-danger', text: 'Network error loading messages.' }));
            });
        }

        function sendMessage() {
            const message = $('#admin-message').val().trim();
            if (!message || !selectedStudentId) return;
            $('#admin-message').val('');
            $.post('../chat-handler.php', {
                action: 'send_message',
                sender_role: 'admin',
                recipient_role: 'student',
                recipient_id: selectedStudentId,
                message: message
            }, function (result) {
                if (!result.success) {
                    alert(result.message || 'Unable to send message.');
                    return;
                }
                loadMessages();
            }, 'json');
        }

        $(function () {
            loadStudents();
            $('#send-chat').on('click', sendMessage);
            $('#admin-message').on('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            setInterval(function () {
                if (selectedStudentId) {
                    loadMessages();
                }
            }, 12000);
        });
    </script>
</body>

</html>


