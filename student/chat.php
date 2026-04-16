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
    <meta name="description" content="Student chat with admin">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon.png">
    <title>Homestay Chat with Admin</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <style>
        .chat-room {
            min-height: 420px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .chat-window {
            flex: 1;
            padding: 20px;
            border-radius: 20px;
            background: #f7fbff;
            box-shadow: 0 18px 45px rgba(15, 83, 160, .08);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message-row {
            max-width: 80%;
            display: inline-flex;
            flex-direction: column;
            gap: 6px;
            word-break: break-word;
        }

        .message-row.admin {
            align-self: flex-start;
        }

        .message-row.student {
            align-self: flex-end;
        }

        .message-box {
            padding: 14px 18px;
            border-radius: 18px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .message-row.admin .message-box {
            background: #ffffff;
            color: #22304f;
            box-shadow: 0 10px 24px rgba(37, 93, 214, 0.08);
        }

        .message-row.student .message-box {
            background: #1762a3;
            color: #ffffff;
        }

        .message-meta {
            font-size: 11px;
            color: #6c7a93;
        }

        .chat-action-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
        }

        .chat-action-bar input {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #d9e5f5;
            padding: 14px 16px;
            font-size: 0.95rem;
            outline: none;
        }

        .chat-action-bar button {
            min-width: 130px;
            border-radius: 12px;
            border: none;
            background: #1762a3;
            color: #fff;
            padding: 14px 18px;
            font-weight: 700;
        }

        .chat-status {
            padding: 12px 16px;
            border-radius: 14px;
            background: #eaf3ff;
            color: #264a7f;
            margin-bottom: 10px;
            font-size: 0.95rem;
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
            <?php include '../includes/student-navigation.php'?>
        </header>
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <?php include '../includes/student-sidebar.php'?>
            </div>
        </aside>
        <div class="page-wrapper">
            <div class="page-breadcrumb">
                <div class="row align-items-center">
                    <div class="col-7">
                        <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Chat with Admin</h4>
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
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body chat-room">
                                <div class="chat-status">You can send a private message to admin here. Messages are saved for your session.</div>
                                <div id="chat-window" class="chat-window">
                                    <div class="message-row admin">
                                        <div class="message-box">Hello! Admin is available to help you. Send your question and wait for a reply.</div>
                                        <div class="message-meta">Admin • now</div>
                                    </div>
                                </div>
                                <div class="chat-action-bar">
                                    <input id="chat-input" type="text" placeholder="Type your message to admin..." autocomplete="off" />
                                    <button id="chat-send" type="button">Send</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../includes/footer.php' ?>
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
        function renderMessage(role, text, time) {
            const wrapper = $('<div/>', { class: 'message-row ' + role });
            const box = $('<div/>', { class: 'message-box', text: text });
            const meta = $('<div/>', { class: 'message-meta', text: time });
            wrapper.append(box, meta);
            $('#chat-window').append(wrapper);
            $('#chat-window').scrollTop($('#chat-window')[0].scrollHeight);
        }

        function loadConversation() {
            $.post('../chat-handler.php', {
                action: 'load_messages',
                sender_role: 'student',
                conversation_with: 0
            }, function (result) {
                if (!result.success) {
                    console.error('Failed to load messages:', result.message);
                    return;
                }
                $('#chat-window').empty();
                if (!result.messages || result.messages.length === 0) {
                    renderMessage('admin', 'Admin is ready to chat. Send your first message now.', 'Just now');
                    return;
                }
                result.messages.forEach(function (msg) {
                    const role = msg.sender_role === 'admin' ? 'admin' : 'student';
                    const text = msg.message;
                    const time = msg.created_at ? msg.created_at : '';
                    renderMessage(role, text, time);
                });
            }).fail(function(xhr, status, error) {
                console.error('AJAX error loading messages:', error);
                renderMessage('admin', 'Network error loading messages.', 'Error');
            });
        }

        function sendMessage() {
            const message = $('#chat-input').val().trim();
            if (!message) {
                return;
            }
            $('#chat-input').val('');
            renderMessage('student', message, 'Sending...');
            $.post('../chat-handler.php', {
                action: 'send_message',
                sender_role: 'student',
                recipient_role: 'admin',
                recipient_id: 0,
                message: message
            }, function (result) {
                if (!result.success) {
                    renderMessage('admin', 'Unable to send your message. Try again: ' + (result.message || 'unknown error'), 'Error');
                }
                loadConversation();
            }).fail(function(xhr, status, error) {
                console.error('AJAX error sending message:', error);
                renderMessage('admin', 'Network error sending message.', 'Error');
            });
        }

        $(function () {
            loadConversation();
            $('#chat-send').on('click', sendMessage);
            $('#chat-input').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });
            setInterval(loadConversation, 10000);
        });
    </script>
</body>

</html>
