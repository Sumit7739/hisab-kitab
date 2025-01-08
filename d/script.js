const debitPopup = document.querySelector('.debit-popup');
        const creditPopup = document.querySelector('.credit-popup');
        const debitButton = document.getElementById('debit-button');
        const creditButton = document.getElementById('credit-button');
        const closeDebitPopup = document.querySelector('.debit-popup .popup-close');
        const closeCreditPopup = document.querySelector('.credit-popup .popup-close');

        const showPopup = (popup) => {
            popup.classList.add('show');
            popup.classList.remove('hide');
        };

        const hidePopup = (popup) => {
            popup.classList.add('hide');
            setTimeout(() => {
                popup.classList.remove('show');
            }, 300); // Adjust timeout to match CSS transition duration
        };

        debitButton.addEventListener('click', () => showPopup(debitPopup));
        creditButton.addEventListener('click', () => showPopup(creditPopup));

        closeDebitPopup.addEventListener('click', () => hidePopup(debitPopup));
        closeCreditPopup.addEventListener('click', () => hidePopup(creditPopup));

        window.addEventListener('click', (event) => {
            if (event.target === debitPopup) hidePopup(debitPopup);
            if (event.target === creditPopup) hidePopup(creditPopup);
        });


        // Get modal and icon elements
        var modal = document.getElementById("settingsModal");
        var gearIcon = document.getElementById("gearIcon");
        var closeBtn = document.getElementsByClassName("close-btn")[0];

        // Get the elements for OTP generation
        var generateOtpBtn = document.getElementById("generateOtpBtn");
        var otpField = document.getElementById("otp");
        var statusText = document.getElementById("statusText");

        // Open the modal when the gear icon is clicked
        gearIcon.onclick = function() {
            modal.style.display = "block";
        }

        // Close the modal when the close button is clicked
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        // Close the modal when the user clicks outside of the modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }