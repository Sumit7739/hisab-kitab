<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <script>
    // Sync theme on load
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark-theme");
    }
  </script>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
      height: 100vh;
      color: #333;
      /* display: flex; */
      /* flex-direction: column; */
      align-items: center;
    }

    .main {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .container {
      display: flex;
      flex-direction: column;
      align-items: center;
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

    nav {
      display: flex;
      justify-content: space-between;
    }


    .hamburge {
      position: absolute;
      top: 5px;
      right: 0;
      margin-right: 20px;
    }

    .hamburge i {
      color: #333;
      font-size: 24px;
    }

    /* add dark theme */
    body.dark-theme {
      background: #1a1a1a;
      color: #f0f0f0;
    }

    body.dark-theme header {
      background-color: #2c2c2c;
    }

    body.dark-theme .container {
      background-color: #2c2c2c;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    body.dark-theme .title {
      color: #f0f0f0;
    }

    body.dark-theme .subtitle {
      color: #bbb;
    }

    body.dark-theme .otp-input {
      background-color: #3a3a3a;
      border: 1px solid #555;
      color: rgb(255, 255, 255);
    }

    body.dark-theme input[type="text"] {
      color: #fff;
    }

    body.dark-theme .otp-input:focus {
      border-color: #0099ff;
      box-shadow: 0 0 5px rgba(0, 153, 255, 0.5);
    }

    body.dark-theme .submit-btn {
      background: #0056b3;
    }

    body.dark-theme .submit-btn:hover {
      background: #003d80;
      box-shadow: 0 4px 6px rgba(0, 123, 255, 0.4);
    }

    body.dark-theme .footer {
      color: #aaa;
    }

    body.dark-theme .footer a {
      color: #0099ff;
    }

    body.dark-theme .footer a:hover {
      color: #007acc;
    }


    body.dark-theme .logo {
      color: #f0f0f0;
    }

    body.dark-theme .hamburge i {
      color: #f0f0f0;
    }
  </style>
</head>

<body>
  <header>
    <nav>
      <div class="logo">Hisab-Kitab</div>
      <div class="hamburge">
        <a href="index.php"><i class="fa fa-arrow-left"></i></a>
      </div>
    </nav>
  </header>
  <?php

  include('../config.php');

  // Check if email is provided as a URL parameter
  if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
  ?>

    <div class="main">
      <div class="container">
        <div class="title">Enter OTP</div>
        <div class="subtitle">Enter the OTP shared by the user to securely connect and manage transactions.</div>
        <form action="process_join_otp.php" method="POST">
          <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
          <div class="otp-input-container">
            <input type="text" class="otp-input" id="otp" name="otp" placeholder="Enter OTP" required maxlength="10">
          </div>
          <button type="submit" class="submit-btn">Verify</button>
        </form>
        <div class="footer">
          Don't have the code? Ask the user to share it with you again.
        </div>
      </div>
    </div>
  <?php
  } else {
    echo 'User ID not provided.';
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

    // Apply saved theme preference
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark-theme");
      if (themeToggle) {
        themeToggle.classList.replace("fa-sun", "fa-moon");
      }
    }
  </script>

</body>

</html>