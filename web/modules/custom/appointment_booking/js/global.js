(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.appointmentBooking = {
    attach: function (context, settings) {
      // Handle selections (agencies, appointment types, advisers)
      once('selection-options', '.agency-option, .appointment-type-option, .adviser-option', context).forEach(function (option) {
        option.addEventListener('click', function () {
          // Determine the type of option clicked
          const fieldId = this.classList.contains('agency-option') ? 'agency' :
                          this.classList.contains('appointment-type-option') ? 'appointment_type' :
                          this.classList.contains('adviser-option') ? 'adviser' : null;

          if (!fieldId) return;

          // Remove selected class from all options in the same group
          document.querySelectorAll(`.${this.classList[0]}`).forEach(el => el.classList.remove('selected'));

          // Add selected class to the clicked option
          this.classList.add('selected');

          // Get the data-id attribute
          const dataId = this.getAttribute('data-id');
          if (!dataId) {
            console.error('No data-id attribute found on selected element');
            return;
          }

          // Find the input field and update its value
          const inputField = document.querySelector(`input[name="${fieldId}"]`);
          if (inputField) {
            inputField.value = dataId;
          } else {
            console.error(`Input field not found: ${fieldId}`);
          }
        });
      });

      // Helper function to set selected class based on input value
      const setSelectedClass = (inputId, optionClass) => {
        const input = document.getElementById(inputId);
        if (!input) return;

        const selectedId = input.value;
        if (!selectedId) return;

        document.querySelectorAll(`.${optionClass}`).forEach(item => {
          if (item.getAttribute('data-id') === selectedId) {
            item.classList.add('selected');
          }
        });
      };

      // Set selected class for agencies, appointment types, and advisers
      setSelectedClass('agency', 'agency-option');
      setSelectedClass('appointment_type', 'appointment-type-option');
      setSelectedClass('adviser', 'adviser-option');

      // Handle the Next button click to ensure values are included in the AJAX submission
      once('booking-next-button', 'input[name="next"]', context).forEach(function (nextButton) {
        nextButton.addEventListener('click', function () {
          const form = this.closest('form');
          if (!form) return;

          // Helper function to update form input value
          const updateFormInput = (optionClass, inputName) => {
            const selectedOption = form.querySelector(`.${optionClass}.selected`);
            const inputField = form.querySelector(`input[name="${inputName}"]`);
            if (selectedOption && inputField) {
              const dataId = selectedOption.getAttribute('data-id');
              if (dataId) inputField.value = dataId;
            }
          };

          // Update form values for agencies, appointment types, and advisers
          updateFormInput('agency-option', 'agency');
          updateFormInput('appointment-type-option', 'appointment_type');
          updateFormInput('adviser-option', 'adviser');
        });
      });
    }
  };
})(Drupal, once);
