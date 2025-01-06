<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
      height: 100vh;
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .container {
      width: 90%;
      max-width: 400px;
      text-align: center;
      margin-top: 50px;
      padding: 20px;
      background-color: #fbfbfb;
      border-radius: 15px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .title {
      font-size: 28px;
      font-weight: 500;
      margin-bottom: 10px;
    }

    .subtitle {
      font-size: 14px;
      color: #555;
      margin-bottom: 20px;
    }

    .otp-input-container {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .otp-input {
      width: 100%;
      height: 60px;
      font-size: 24px;
      font-weight: 500;
      text-align: center;
      border: 1px solid #ddd;
      border-radius: 10px;
      outline: none;
      transition: border 0.3s ease, box-shadow 0.3s ease;
      box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .otp-input:focus {
      border-color: #007bff;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .submit-btn {
      width: 100%;
      padding: 12px 20px;
      background: #007bff;
      color: #fff;
      border: none;
      border-radius: 25px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.3s ease, box-shadow 0.3s ease;
    }

    .submit-btn:hover {
      background: #0056b3;
      box-shadow: 0 4px 6px rgba(0, 123, 255, 0.4);
    }

    .footer {
      font-size: 12px;
      color: #777;
      margin-top: 20px;
    }

    .footer a {
      color: #007bff;
      text-decoration: none;
    }

    .footer a:hover {
      color: #0056b3;
    }
  </style>
</head>

<body>
  <?php

  include('config.php');

  // Check if email is provided as a URL parameter
  if (isset($_GET['email'])) {
    $email = $_GET['email'];
  ?>

    <div class="container">
      <div class="title">Enter OTP</div>
      <div class="subtitle">We've sent a 6-digit code to your registered email to change password.</div>
      <form action="process_pass_otp.php" method="POST">
        <input type="hidden" name="email" value="<?php echo $email; ?>">
        <div class="otp-input-container">
          <input type="text" class="otp-input" id="otp" name="otp" placeholder="Enter OTP" required maxlength="6">
        </div>
        <button type="submit" class="submit-btn">Verify</button>
      </form>
      <div class="footer">
        Didn't receive the code? <a href="#">Resend OTP</a>
      </div>
    </div>
  <?php
  } else {
    echo 'Invalid request.';
  }

  $conn->close();
  ?>
  <script>
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        const value = e.target.value;
        if (value.length === 1 && index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && input.value === '' && index > 0) {
          inputs[index - 1].focus();
        }
      });
    });
  </script>
</body>

</html>