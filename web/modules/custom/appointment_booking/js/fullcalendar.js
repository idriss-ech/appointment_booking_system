(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.appointmentCalendar = {
    attach: function (context, settings) {
      const calendarEl = document.getElementById('fullcalendar');
      if (calendarEl && !calendarEl.hasAttribute('data-processed')) {
        calendarEl.setAttribute('data-processed', 'true');

        try {
          if (typeof FullCalendar === 'undefined') {
            console.error('FullCalendar library not loaded!');
            calendarEl.innerHTML = '<div class="messages messages--error">Calendar could not be loaded. Please check console for errors.</div>';
            return;
          }

          // Récupérer les heures de travail et les créneaux indisponibles
          const businessHours = drupalSettings.adviserWorkingHours;
          const unavailableSlots = drupalSettings.adviserUnavailableSlots;
          const existingAppointments = drupalSettings.existingAppointments || [];
          const userSlotEvent = drupalSettings.userSlotEvent || null;
           
          // Convertir les créneaux indisponibles en événements FullCalendar
          const unavailableEvents = unavailableSlots.map(slot => ({
            daysOfWeek: [slot.daysOfWeek],
            startTime: slot.startTime,
            endTime: slot.endTime,
            title: 'adviser unavailable',
            display: 'background',
            color: '#cccccc',
            textColor: '#000000',
            className: 'fc-unavailable',
          }));

          // Prepare existing appointments as events for the calendar
          const existingEvents = existingAppointments.map(appointment => ({
            start: appointment.start,
            end: appointment.end,
            title: appointment.title,
            color: appointment.color,
            textColor: appointment.textColor,
          }));

          // Prepare the user-selected slot event (if available)
          const userSlotEvents = userSlotEvent ? [{
            start: userSlotEvent.start,
            end: userSlotEvent.end,
            title: userSlotEvent.title,
            color: userSlotEvent.color,
            textColor: userSlotEvent.textColor,
          }] : [];

          // Initialiser le calendrier
          const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            initialDate: new Date(),
            validRange: {
              start: new Date(),
            },
            headerToolbar: {
              left: 'prev,next',
              center: 'title',
              right: ''
            },
            plugins: ['timeGrid', 'interaction'],
            titleFormat: { year: 'numeric', month: 'long', day: 'numeric' },
            firstDay: 1,
            height: 'auto',
            hiddenDays: [0], // Masquer le dimanche
            allDaySlot: false,
            minTime: '08:00:00',
            maxTime: '18:00:00',
            slotDuration: '00:30:00',
            selectable: true,
            businessHours: businessHours,
            events: [
              ...unavailableEvents,
              ...existingEvents,
              ...userSlotEvents,  // Add user selected slot if available
            ],
            selectAllow: function (selectInfo) {
              const start = new Date(selectInfo.start);
              const end = new Date(selectInfo.end);
              const isSameDay = start.getDate() === end.getDate() &&
                start.getMonth() === end.getMonth() &&
                start.getFullYear() === end.getFullYear();

              if (!isSameDay) {
                return false; 
              }

              const dayOfWeek = start.getDay();
              const businessHoursForDay = businessHours.find(hours => hours.daysOfWeek.includes(dayOfWeek));
            
              if (!businessHoursForDay) {
                return false;
              }

              const startTime = businessHoursForDay.startTime;
              const endTime = businessHoursForDay.endTime;

              const startTimeDate = new Date(start);
              startTimeDate.setHours(parseInt(startTime.split(':')[0]), parseInt(startTime.split(':')[1]), 0);
            
              const endTimeDate = new Date(start);
              endTimeDate.setHours(parseInt(endTime.split(':')[0]), parseInt(endTime.split(':')[1]), 0);

              if (selectInfo.start < startTimeDate || selectInfo.end > endTimeDate) {
                return false;
              }

              const isAvailable = !unavailableSlots.some(slot => {
                const slotStart = new Date(selectInfo.start);
                const slotEnd = new Date(selectInfo.end);
                slotStart.setHours(parseInt(slot.startTime.split(':')[0]), parseInt(slot.startTime.split(':')[1]), 0);
                slotEnd.setHours(parseInt(slot.endTime.split(':')[0]), parseInt(slot.endTime.split(':')[1]), 0);
                return selectInfo.start > slotEnd && selectInfo.end < slotStart;
              });
            
              return isAvailable; 
            },
            select: function (info) {
              const startDateTime = info.startStr;
              const endDateTime = info.endStr;
              document.getElementById('selected-start-date').value = startDateTime;
              document.getElementById('selected-end-date').value = endDateTime;
            },
          });

          calendar.render();
        } catch (error) {
          console.error('Error initializing calendar:', error);
          calendarEl.innerHTML = '<div class="messages messages--error">Error initializing calendar. Please check console for details.</div>';
        }
      }
    }
  };
})(jQuery, Drupal, drupalSettings);

(function (Drupal) {
  Drupal.behaviors.timezoneDetection = {
    attach: function (context, settings) {
      // Detect the user's time zone
      const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

      // Set the detected time zone in the hidden field
      const timezoneField = document.getElementById('user-timezone');
      if (timezoneField) {
        timezoneField.value = userTimeZone;
      }
    }
  };
})(Drupal);

