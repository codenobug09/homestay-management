<?php
session_start();
include('includes/dbconn.php');
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOtpEmail($email, $otp)
{
    $mail = new PHPMailer(true);
    try {
        // Update these SMTP settings for your mail server
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bangcac24@gmail.com';
        $mail->Password = 'kogozhobmvxswwwv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'html';

        $mail->setFrom('bangcac24@gmail.com', 'Homestay Management');
        $mail->addAddress($email);
        $mail->Subject = 'OTP for Homestay Registration';
        $mail->isHTML(true);
        $mail->Body = '<p>Your OTP code is <strong>' . $otp . '</strong>.</p>' .
            '<p>Please enter this code to complete your registration.</p>';
        $mail->AltBody = 'Your OTP code is ' . $otp . '. Please enter this code to complete your registration.';

        $mail->send();
        return [
            'success' => true,
            'error' => '',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $mail->ErrorInfo ?: $e->getMessage(),
        ];
    }
}

$showVerification = false;
$successMessage = '';
$errorMessage = '';
$displayEmail = '';

if (isset($_POST['register'])) {
    $regno = trim($_POST['regno']);
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $gender = trim($_POST['gender']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $cpassword = trim($_POST['cpassword']);

    if ($password !== $cpassword) {
        $errorMessage = 'Password and Confirm Password do not match.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $query = 'SELECT count(*) FROM userregistration WHERE email=?';
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $errorMessage = 'This email is already registered. Please login or use a different email.';
        } else {
            $otp = random_int(100000, 999999);
            $_SESSION['register_data'] = [
                'regno' => $regno,
                'fname' => $fname,
                'mname' => $mname,
                'lname' => $lname,
                'gender' => $gender,
                'contact' => $contact,
                'email' => $email,
                'password' => md5($password),
            ];
            $_SESSION['register_otp'] = $otp;
            $_SESSION['register_otp_expires'] = time() + 600;

            $mailResult = sendOtpEmail($email, $otp);
            if ($mailResult['success']) {
                $showVerification = true;
                $displayEmail = $email;
            } else {
                $errorMessage = 'OTP email could not be sent. SMTP error: ' . $mailResult['error'];
            }
        }
    }
}

if (isset($_POST['verify'])) {
    if (empty($_SESSION['register_data']) || empty($_SESSION['register_otp'])) {
        $errorMessage = 'No registration data found. Please start again.';
    } else {
        $enteredOtp = trim($_POST['otp']);
        $sessionOtp = $_SESSION['register_otp'];
        $expiry = $_SESSION['register_otp_expires'] ?? 0;

        if (time() > $expiry) {
            $errorMessage = 'OTP expired. Please register again to receive a new code.';
            unset($_SESSION['register_data'], $_SESSION['register_otp'], $_SESSION['register_otp_expires']);
        } elseif ($enteredOtp !== (string)$sessionOtp) {
            $errorMessage = 'The OTP code is incorrect. Please try again.';
            $showVerification = true;
            $displayEmail = $_SESSION['register_data']['email'];
        } else {
            $data = $_SESSION['register_data'];
            $insert = 'INSERT INTO userregistration(regNo,firstName,middleName,lastName,gender,contactNo,email,password) VALUES(?,?,?,?,?,?,?,?)';
            $stmt = $mysqli->prepare($insert);
            $stmt->bind_param(
                'sssssiss',
                $data['regno'],
                $data['fname'],
                $data['mname'],
                $data['lname'],
                $data['gender'],
                $data['contact'],
                $data['email'],
                $data['password']
            );
            if ($stmt->execute()) {
                $successMessage = 'Registration completed successfully. You may now login.';
            } else {
                $errorMessage = 'Could not complete registration. Please try again.';
                $showVerification = true;
                $displayEmail = $data['email'];
            }
            $stmt->close();
            unset($_SESSION['register_data'], $_SESSION['register_otp'], $_SESSION['register_otp_expires']);
        }
    }
}

if (!empty($_SESSION['register_data']) && !$showVerification && empty($successMessage) && isset($_SESSION['register_otp'])) {
    $showVerification = true;
    $displayEmail = $_SESSION['register_data']['email'];
}
?>
<!DOCTYPE html>
<html dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon.png">
    <title>Homestay Management System - Register</title>
    <link href="dist/css/style.min.css" rel="stylesheet">
    <link href="dist/css/custom-colors-v2.css" rel="stylesheet">
    <script type="text/javascript">
    function valid() {
        if(document.registration.password.value != document.registration.cpassword.value) {
            alert("Password and Re-Type Password Field do not match !!");
            document.registration.cpassword.focus();
            return false;
        }
        return true;
    }
    function checkAvailability() {
        document.getElementById('user-availability-status').innerHTML = 'Checking...';
        jQuery.ajax({
            url: 'check-availability.php',
            data: 'emailid=' + encodeURIComponent(document.getElementById('email').value),
            type: 'POST',
            success: function(data) {
                document.getElementById('user-availability-status').innerHTML = data;
            },
            error: function() {
                document.getElementById('user-availability-status').innerHTML = 'Check failed. Please try again.';
            }
        });
    }
    </script>
</head>

<body>
    <div class="main-wrapper">
        <div class="preloader">
            <div class="lds-ripple">
                <div class="lds-pos"></div>
                <div class="lds-pos"></div>
            </div>
        </div>
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center position-relative"
            style="background:url(assets/images/big/auth-bg.jpg) no-repeat center center;">
            <div class="auth-box row" style="margin: 0 50px; width: 100%;">
                <div class="col-lg-7 col-md-5 modal-bg-img" style="background-image: url(assets/images/Homestay-img.jpg);">
                </div>
                <div class="col-lg-5 col-md-7 bg-white">
                    <div class="p-3">
                        <div class="text-center">
                            <img src="assets/images/big/icon.png" alt="wrapkit">
                        </div>
                        <h2 class="mt-3 text-center">Customer Registration</h2>
                        <?php if (!empty($successMessage)): ?>
                            <div class="alert alert-success" role="alert"><?php echo $successMessage; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-danger" role="alert"><?php echo $errorMessage; ?></div>
                        <?php endif; ?>

                        <?php if ($showVerification): ?>
                            <form class="mt-4" method="POST">
                                <div class="form-group">
                                    <label class="text-dark" for="verify-email">Email</label>
                                    <input class="form-control" id="verify-email" type="email" value="<?php echo htmlentities($displayEmail); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="text-dark" for="otp">OTP Code</label>
                                    <input class="form-control" name="otp" id="otp" type="text" placeholder="Enter OTP" required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" name="verify" class="btn btn-block btn-dark">VERIFY OTP</button>
                                </div>
                                <div class="col-lg-12 text-center mt-3">
                                    <a href="index.php" class="text-danger">Back to Login</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <form class="mt-4" method="POST" name="registration" onsubmit="return valid();">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="regno">Registration Number</label>
                                            <input class="form-control" name="regno" id="regno" type="text" placeholder="Enter your registration number" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="fname">First Name</label>
                                            <input class="form-control" name="fname" id="fname" type="text" placeholder="Enter your first name" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="mname">Middle Name</label>
                                            <input class="form-control" name="mname" id="mname" type="text" placeholder="Enter your middle name" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="lname">Last Name</label>
                                            <input class="form-control" name="lname" id="lname" type="text" placeholder="Enter your last name" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="gender">Gender</label>
                                            <select class="form-control" name="gender" id="gender" required>
                                                <option value="">Choose...</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="contact">Contact Number</label>
                                            <input class="form-control" name="contact" id="contact" type="text" placeholder="Enter your contact number" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="email">Email</label>
                                            <input class="form-control" name="email" id="email" type="email" placeholder="Enter your email" required onblur="checkAvailability();">
                                            <span id="user-availability-status" class="small text-muted"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="password">Password</label>
                                            <input class="form-control" name="password" id="password" type="password" placeholder="Enter your password" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label class="text-dark" for="cpassword">Confirm Password</label>
                                            <input class="form-control" name="cpassword" id="cpassword" type="password" placeholder="Confirm your password" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center flex-column flex-sm-row gap-2 mt-2">
                                            <button type="submit" name="register" class="btn btn-dark px-5">REGISTER</button>
                                            <a style="margin-left: 20px;" href="index.php" class="text-danger">Already have an account? Login</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script>
        $(".preloader").fadeOut();
    </script>
</body>

</html>

