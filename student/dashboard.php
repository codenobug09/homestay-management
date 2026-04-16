<?php
    session_start();
    include('../includes/dbconn.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chatbot_action'])) {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['chatbot_action'];

        if ($action === 'get_rooms') {
            $stmt = $mysqli->prepare("SELECT room_no, seater, fees, posting_date FROM rooms ORDER BY fees ASC");
            $stmt->execute();
            $res = $stmt->get_result();
            $rooms = [];
            while ($row = $res->fetch_assoc()) {
                $rooms[] = $row;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'rooms' => $rooms]);
            exit;
        }

        if ($action === 'recommend') {
            $budget = isset($_POST['budget']) ? (int) $_POST['budget'] : 0;
            $seater = isset($_POST['seater']) ? (int) $_POST['seater'] : 0;

            $stmt = $mysqli->prepare("SELECT room_no, seater, fees, posting_date FROM rooms");
            $stmt->execute();
            $res = $stmt->get_result();
            $candidates = [];
            while ($row = $res->fetch_assoc()) {
                $score = 0;
                if ($seater && (int)$row['seater'] === $seater) {
                    $score += 60;
                }
                $score -= abs($row['fees'] - $budget) / 10;
                if ($budget === 0 || $row['fees'] <= $budget) {
                    $score += 10;
                }
                $candidates[] = ['room' => $row, 'score' => $score];
            }
            usort($candidates, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $suggestions = array_map(function ($item) { return $item['room']; }, array_slice($candidates, 0, 3));
            echo json_encode(['success' => true, 'suggestions' => $suggestions]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown chatbot action']);
        exit;
    }

    include('../includes/check-login.php');
    check_login();
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- By LMC - LMC.com -->
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon.png">
    <title>Homestay Management System</title>
    <!-- Custom CSS -->
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link href="../dist/css/custom-colors-v2.css" rel="stylesheet">
    <style>
        #room-chatbot {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .chatbot-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 260px;
            min-height: 52px;
            padding: 0 18px;
            background: #1762a3;
            color: #fff;
            border: none;
            border-radius: 32px;
            box-shadow: 0 18px 36px rgba(23, 98, 163, 0.22);
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }

        .chatbot-toggle::before {
            content: '????';
            font-size: 18px;
        }

        .chatbot-panel {
            width: 360px;
            max-width: calc(100vw - 32px);
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 24px 60px rgba(31, 45, 61, .24);
            overflow: hidden;
            margin-top: 12px;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #1762a3 0%, #1d3f6f 100%);
            color: #fff;
            padding: 16px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .chatbot-title {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .chatbot-subtitle {
            font-size: 12px;
            opacity: 0.88;
            margin-top: 4px;
        }

        .chatbot-header-actions {
            display: flex;
            gap: 8px;
        }

        .chatbot-header-actions button {
            width: 32px;
            height: 32px;
            border-radius: 12px;
            border: none;
            background: rgba(255,255,255,0.18);
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        .chatbot-body {
            max-height: 320px;
            overflow-y: auto;
            padding: 16px;
            background: #f4f8ff;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chatbot-message {
            margin: 0;
            line-height: 1.6;
            padding: 12px 14px;
            border-radius: 18px;
            max-width: 85%;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .chatbot-message.bot {
            background: #ffffff;
            color: #1d2b42;
            align-self: flex-start;
            box-shadow: 0 5px 14px rgba(56, 87, 132, 0.08);
        }

        .chatbot-message.user {
            background: #1762a3;
            color: #fff;
            align-self: flex-end;
            box-shadow: 0 5px 14px rgba(23, 98, 163, 0.18);
        }

        .chatbot-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            padding: 14px 16px;
            background: #fff;
        }

        .chatbot-actions button {
            background: #f0f6ff;
            color: #1d3f6f;
            border: 1px solid #dbeaf8;
            border-radius: 14px;
            padding: 10px 12px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.15s ease;
        }

        .chatbot-actions button:hover {
            background: #e2efff;
            transform: translateY(-1px);
        }

        .chatbot-footer {
            padding: 14px 16px 18px;
            background: #fff;
            border-top: 1px solid #e7eef7;
            display: grid;
            gap: 10px;
        }

        .chatbot-footer input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid #d0d9e8;
            font-size: 14px;
            outline: none;
        }

        .chatbot-footer button {
            width: 100%;
            background: #1762a3;
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 12px;
            cursor: pointer;
            font-weight: 700;
        }

        .chatbot-preferences {
            padding: 14px 16px 18px;
            background: #fff;
            border-top: 1px solid #e7eef7;
            display: none;
            gap: 10px;
        }

        .chatbot-preferences.active {
            display: grid;
        }

        .chatbot-preferences label {
            font-size: 13px;
            color: #4b5f7d;
        }

        .chatbot-preferences input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #d0d9e8;
            font-size: 14px;
        }
    </style>
    
</head>

<body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar" data-navbarbg="skin6">
            <?php include '../includes/student-navigation.php'?>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <?php include '../includes/student-sidebar.php'?>
            </div>
            <!-- End Sidebar scroll-->
        </aside>
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">
            <!-- ============================================================== -->
            <!-- Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
            <div class="page-breadcrumb">
                <div class="row">
                    <div class="col-7 align-self-center">
                       <?php include '../includes/greetings.php'?>
                        <div class="d-flex align-items-center">
                            <!-- <nav aria-label="breadcrumb">
                                
                            </nav> -->
                        </div>
                    </div>
                    
                </div>
                <!-- By LMC - LMC.com -->
            </div>
            <!-- ============================================================== -->
            <!-- End Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                <!-- *************************************************************** -->
                <!-- Start First Cards -->
                <!-- *************************************************************** -->
                <div class="card-group">
                    <div class="card border-right">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div>
                                    <div class="d-inline-flex align-items-center">
                                        <h2 class="text-dark mb-1 font-weight-medium"><?php include 'counters/student-count.php'?></h2>
                                
                                    </div>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">Registered Students</h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="user-plus"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- By LMC - LMC.com -->
                    <div class="card border-right">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div>
                                    <h2 class="text-dark mb-1 w-100 text-truncate font-weight-medium"><?php include 'counters/room-count.php'?></h2>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">Total Rooms
                                    </h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="grid"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-right">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div>
                                    <div class="d-inline-flex align-items-center">
                                        <h2 class="text-dark mb-1 font-weight-medium"><?php include 'counters/booked-count.php'?></h2>
                                    </div>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">Booked Rooms</h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="book-open"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div>
                                    <h2 class="text-dark mb-1 font-weight-medium"><?php include 'counters/course-count.php'?></h2>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">Featured Courses</h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="globe"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- *************************************************************** -->
                <!-- End First Cards -->
                <!-- *************************************************************** -->
                
               
            </div>
            <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <?php include '../includes/footer.php' ?>
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->

    <div id="room-chatbot">
        <button id="chatbot-toggle" class="chatbot-toggle">Room Assistant</button>
        <div id="chatbot-panel" class="chatbot-panel d-none">
            <div class="chatbot-header">
                <div>
                    <div class="chatbot-title">Room Assistant</div>
                    <div class="chatbot-subtitle">Find the best room using your budget and preferences.</div>
                </div>
                <div class="chatbot-header-actions">
                    <button id="chatbot-clear" type="button" title="Clear conversation">???</button>
                    <button id="chatbot-close" type="button" title="Close chat">??</button>
                </div>
            </div>
            <div id="chatbot-body" class="chatbot-body">
                <div class="chatbot-message bot">Hello! I can help you choose the best room. Tap a quick action below, or send your preference directly.</div>
            </div>
            <div class="chatbot-actions" id="chatbot-actions">
                <button type="button" class="chatbot-action" data-action="show_rooms">Show available rooms</button>
                <button type="button" class="chatbot-action" data-action="recommend">Recommend best room</button>
            </div>
            <div class="chatbot-footer">
                <input id="chatbot-user-input" type="text" placeholder="Type a question, budget, or preferred seater..." />
                <button id="chatbot-send" type="button">Send message</button>
            </div>
            <div class="chatbot-preferences" id="chatbot-preferences">
                <label for="chatbot-seater">Preferred seater</label>
                <input id="chatbot-seater" type="number" min="1" placeholder="e.g. 1, 2, 3" />
                <label for="chatbot-budget">Monthly budget ($)</label>
                <input id="chatbot-budget" type="number" min="0" placeholder="e.g. 300" />
                <button id="chatbot-submit-preferences" type="button">Find best rooms</button>
            </div>
        </div>
    </div>

    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- apps -->
    <!-- apps -->
    <!-- By LMC - LMC.com -->
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="../dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <script src="../assets/extra-libs/c3/d3.min.js"></script>
    <script src="../assets/extra-libs/c3/c3.min.js"></script>
    <script src="../assets/libs/chartist/dist/chartist.min.js"></script>
    <script src="../assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
    <script src="../dist/js/pages/dashboards/dashboard1.min.js"></script>
    <script>
        function appendChatMessage(role, message) {
            var wrapper = $('<div class="chatbot-message"></div>');
            wrapper.addClass(role === 'user' ? 'user' : 'bot');
            wrapper.text(message);
            $('#chatbot-body').append(wrapper);
            $('#chatbot-body').scrollTop($('#chatbot-body')[0].scrollHeight);
        }

        function botReply(message) {
            appendChatMessage('bot', message);
        }

        function clearChat() {
            $('#chatbot-body').empty();
            botReply('Hello! I can help you choose the best room. Tap a quick action below, or send your preference directly.');
            $('#chatbot-actions').removeClass('d-none');
            $('#chatbot-preferences').removeClass('active');
            $('#chatbot-seater').val('');
            $('#chatbot-budget').val('');
            $('#chatbot-user-input').val('');
        }

        function setPreferenceMode() {
            $('#chatbot-actions').addClass('d-none');
            $('#chatbot-preferences').addClass('active');
            botReply('Great! Enter your preferred seater and monthly budget below, then tap Find best rooms.');
        }

        function renderRoomCards(rooms) {
            if (!rooms.length) {
                botReply('No rooms are available right now. Please check again later.');
                return;
            }
            var text = 'Available rooms:';
            rooms.forEach(function(room) {
                text += '\n??? Room ' + room.room_no + ' ??? ' + room.seater + ' seater, $' + room.fees + '/month';
            });
            appendChatMessage('bot', text);
        }

        function renderRoomList(rooms) {
            if (!rooms.length) {
                botReply('No available rooms found for the selected criteria.');
                return;
            }
            var lines = ['Best room matches for you:'];
            rooms.forEach(function(room) {
                lines.push('??? Room ' + room.room_no + ' ??? ' + room.seater + ' seater, $' + room.fees + '/month');
            });
            appendChatMessage('bot', lines.join('\n'));
        }

        function postChatAction(data, onSuccess) {
            $.post('dashboard.php', data, function(response) {
                if (response.success) {
                    onSuccess(response);
                } else {
                    botReply('Something went wrong. Please try again.');
                }
            }, 'json').fail(function() {
                botReply('Connection failed. Please try again.');
            });
        }

        $('#chatbot-toggle').on('click', function() {
            $('#chatbot-panel').toggleClass('d-none');
        });

        $('#chatbot-close').on('click', function() {
            $('#chatbot-panel').addClass('d-none');
        });

        $('#chatbot-clear').on('click', function() {
            clearChat();
        });

        $('.chatbot-action').on('click', function() {
            var action = $(this).data('action');
            appendChatMessage('user', $(this).text());
            if (action === 'show_rooms') {
                botReply('Fetching available rooms...');
                postChatAction({ chatbot_action: 'get_rooms' }, function(response) {
                    renderRoomCards(response.rooms);
                });
            } else if (action === 'recommend') {
                setPreferenceMode();
            }
        });

        $('#chatbot-send').on('click', function() {
            var text = $('#chatbot-user-input').val().trim();
            if (!text) {
                return;
            }
            appendChatMessage('user', text);
            $('#chatbot-user-input').val('');
            botReply('Thanks! Use the buttons or the preference form below to get the best room recommendation.');
        });

        $('#chatbot-submit-preferences').on('click', function() {
            var seater = parseInt($('#chatbot-seater').val(), 10) || 0;
            var budget = parseInt($('#chatbot-budget').val(), 10) || 0;
            if (!seater && !budget) {
                botReply('Please enter at least one preference so I can recommend a room.');
                return;
            }
            appendChatMessage('user', 'Seater: ' + (seater || 'any') + ', Budget: $' + (budget || 'any'));
            botReply('Finding the best match for your preferences...');
            postChatAction({ chatbot_action: 'recommend', seater: seater, budget: budget }, function(response) {
                renderRoomList(response.suggestions);
            });
        });
    </script>
</body>

</html>
