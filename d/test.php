<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate and Fetch OTP</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Generate and Fetch OTP</h1>

    <!-- Input field for connection ID -->
    <div>
        <label for="connectionId">Connection ID:</label>
        <input type="text" id="connectionId" placeholder="Enter Connection ID">
    </div>

    <!-- Button to generate OTP -->
    <div>
        <button id="generateOtpBtn">Generate OTP</button>
    </div>

    <!-- Display generated OTP -->
    <div>
        <label for="otp">Generated OTP:</label>
        <input type="text" id="otp" readonly>
    </div>

    <!-- Display OTP status -->
    <div>
        <label for="status">Status:</label>
        <input type="text" id="status" readonly>
    </div>

    <!-- Button to fetch OTP status -->
    <div>
        <button id="fetchOtpBtn">Fetch OTP Status</button>
    </div>

    <script>
        $(document).ready(function () {
            // Generate OTP button click handler
            $('#generateOtpBtn').click(function () {
                const connectionId = $('#connectionId').val();

                if (!connectionId) {
                    alert('Please enter a valid Connection ID.');
                    return;
                }

                // AJAX request to generate OTP
                $.ajax({
                    url: 'generate_otp.php',
                    type: 'POST',
                    data: { connection_id: connectionId },
                    success: function (response) {
                        if (response.success) {
                            $('#otp').val(response.otp); // Display the generated OTP
                            $('#status').val('OTP Generated'); // Update status
                        } else {
                            $('#status').val('Error: ' + response.message); // Display error
                        }
                    },
                    error: function () {
                        $('#status').val('An error occurred while generating OTP.');
                    }
                });
            });

            // Fetch OTP button click handler
            $('#fetchOtpBtn').click(function () {
                const connectionId = $('#connectionId').val();

                if (!connectionId) {
                    alert('Please enter a valid Connection ID.');
                    return;
                }

                // AJAX request to fetch OTP and status
                $.ajax({
                    url: 'fetch_otp_status.php',
                    type: 'GET',
                    data: { connection_id: connectionId },
                    success: function (response) {
                        if (response.success) {
                            $('#otp').val(response.otp); // Display fetched OTP
                            $('#status').val(response.connection_status); // Display fetched status
                        } else {
                            $('#status').val('Error: ' + response.message); // Display error
                        }
                    },
                    error: function () {
                        $('#status').val('An error occurred while fetching OTP status.');
                    }
                });
            });
        });
    </script>
</body>
</html>
