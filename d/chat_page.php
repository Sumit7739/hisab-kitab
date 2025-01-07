<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hisab</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        /* General Body and Section Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #B4C5DB;
        }

        section {
            margin: 20px;
        }

        /* Navbar Styles */
        .nav {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #056068fd;
            padding: 10px 20px;
            border-radius: 0 0 10px 10px;
            color: white;
        }

        .nav .userinfo {
            display: flex;
            align-items: center;
        }

        .nav .username {
            font-weight: bold;
            margin-left: 10px;
        }

        .nav i {
            font-size: 1.5rem;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 10px;
        }

        /* Transactions Skeleton */
        .transaction {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .transaction .box {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .transaction .box .transaction-info {
            display: flex;
            flex-direction: column;
        }

        .transaction .box .transaction-info p {
            margin: 5px 0;
        }

        .transaction .box .transaction-info p.amount {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .transaction .box .transaction-info p.time {
            font-size: 0.9rem;
            color: #888;
        }

        /* Popups Styles */
        /* Popups Styles */
        .popup {
            display: block;
            position: fixed;
            bottom: -100%;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            transition: bottom 0.5s ease, opacity 0.5s ease;
            /* Added opacity transition */
        }

        .popup.show {
            display: flex;
            bottom: 0;
            opacity: 1;
            /* Make the popup fully visible */
        }

        .popup.hide {
            opacity: 0;
            transition: bottom 0.5s ease, opacity 0.5s ease;
            /* Set opacity to 0 when hiding */
        }

        .popup-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }

        .popup-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }

        .popup h2 {
            margin-bottom: 20px;
            text-align: center;
        }

        .popup form {
            display: flex;
            flex-direction: column;
            /* align-items: center; */
        }

        .popup label {
            font-size: 20px;
            margin-bottom: 5px;
            margin-left: 10px;
            display: block;
            color: #555;
        }

        .popup input {
            width: 98%;
            padding: 15px;
            font-size: 14px;
            margin-top: 5px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            transition: border 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .popup input:focus {
            border-color: #007bff;
        }

        .popup button {
            margin-top: 20px;
            padding: 15px 20px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 20px;
        }

        .popup button.debits {
            background-color: #ff00009a;
        }

        .popup button.credits {
            background-color: #0373109a;
        }

        .popup button:hover {
            opacity: 0.9;
        }

        /* Buttons for the transactions */
        .addTransactions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .addTransactions button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .addTransactions button#debit-button {
            background-color: #f44336;
            /* Red for debit */
            color: white;
        }

        .addTransactions button#credit-button {
            background-color: #4CAF50;
            /* Green for credit */
            color: white;
        }

        .addTransactions button:hover {
            opacity: 0.9;
        }

        /* Bottom Buttons */
        .addTransactions {
            position: absolute;
            bottom: 20px;
            width: 90%;
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .addTransactions button {
            width: 45%;
            padding: 15px 20px;
            /* background-color: #f4b400; */
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .addTransactions button.debits {
            background-color: #ff00009a;
        }

        .addTransactions button.credits {
            background-color: #0373109a;
        }
    </style>
</head>

<body>
    <div class="nav">
        <a href="index.php"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="userinfo">
            <p class="username">Test</p>
        </div>
        <a href="usersettings.php"><i class="fa-solid fa-gear"></i></a>
    </div>

    <section>
        <div class="msg"></div>
        <div class="transaction"></div>

        <!-- Transaction boxes -->
        <div class="transaction">
            <div class="box">
                <div class="transaction-info">
                    <p class="name">John Doe</p>
                    <p class="amount">₹ 500</p>
                    <p class="time">2025-01-07 14:30:00</p>
                </div>
                <div class="transaction-status">
                    <p>Status</p>
                </div>
            </div>
            <div class="box">
                <div class="transaction-info">
                    <p class="name">Jane Smith</p>
                    <p class="amount">₹ -200</p>
                    <p class="time">2025-01-07 15:00:00</p>
                </div>
                <div class="transaction-status">
                    <p>Status</p>
                </div>
            </div>
        </div>

        <!-- Debit Popup -->
        <div class="popup debit-popup">
            <div class="popup-content">
                <span class="popup-close">&times;</span>
                <h2>Enter Debit Amount</h2>
                <form action="your_php_script.php" method="POST">
                    <label for="debit-amount">Amount (₹):</label>
                    <input type="number" id="debit-amount" name="debit-amount" required>

                    <label for="debit-description">Description:</label>
                    <input type="text" id="debit-description" name="debit-description">

                    <label for="debit-date">Date:</label>
                    <input type="date" id="debit-date" name="debit-date">

                    <button type="submit" class="debits">Submit Debit</button>
                </form>
            </div>
        </div>

        <!-- Credit Popup -->
        <div class="popup credit-popup">
            <div class="popup-content">
                <span class="popup-close">&times;</span>
                <h2>Enter Credit Amount</h2>
                <form action="your_php_script.php" method="POST">
                    <label for="credit-amount">Amount (₹):</label>
                    <input type="number" id="credit-amount" name="credit-amount" required>

                    <label for="credit-description">Description:</label>
                    <input type="text" id="credit-description" name="credit-description">

                    <label for="credit-date">Date:</label>
                    <input type="date" id="credit-date" name="credit-date">

                    <button type="submit" class="credits">Submit Credit</button>
                </form>
            </div>
        </div>

        <!-- Bottom Buttons -->
        <div class="addTransactions">
            <button type="button" id="debit-button" class="debits">You Gave ₹</button>
            <button type="button" id="credit-button" class="credits">You Got ₹</button>
        </div>
    </section>

    <script>
        // Get the debit and credit popup elements
        const debitPopup = document.querySelector('.debit-popup');
        const creditPopup = document.querySelector('.credit-popup');

        // Get the buttons that trigger the popups
        const debitButton = document.getElementById('debit-button');
        const creditButton = document.getElementById('credit-button');

        // Get the close buttons for both popups
        const closeDebitPopup = document.querySelector('.debit-popup .popup-close');
        const closeCreditPopup = document.querySelector('.credit-popup .popup-close');

        // Function to show the popup by adding the 'show' class
        function showPopup(popup) {
            popup.classList.add('show');
            popup.classList.remove('hide'); // Remove the 'hide' class when showing the popup
        }

        // Function to hide the popup by adding the 'hide' class
        function hidePopup(popup) {
            popup.classList.add('hide'); // Add the 'hide' class to trigger opacity transition
            setTimeout(function() {
                popup.classList.remove('show');
            }, 500); // Wait for the opacity transition to finish before removing 'show'
        }

        // Event listeners to open the popups
        debitButton.addEventListener('click', function() {
            showPopup(debitPopup);
        });

        creditButton.addEventListener('click', function() {
            showPopup(creditPopup);
        });

        // Event listeners to close the popups when clicking the close button
        closeDebitPopup.addEventListener('click', function() {
            hidePopup(debitPopup);
        });

        closeCreditPopup.addEventListener('click', function() {
            hidePopup(creditPopup);
        });

        // Event listener to close the popup if the user clicks outside the popup content
        window.addEventListener('click', function(event) {
            if (event.target === debitPopup) {
                hidePopup(debitPopup);
            }
            if (event.target === creditPopup) {
                hidePopup(creditPopup);
            }
        });
    </script>
</body>

</html>