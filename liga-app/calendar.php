<?php
include __DIR__ . '/header.php';
require_once __DIR__ . '/db.php';
?>

<div class="calendar-container">
  <div id="calendar"></div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'cs',
      events: 'events.php',        // endpoint, viz níže
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek'
      },
      dateClick: function(info) {
        // při kliknutí na den přesměruj na rezervaci s předvyplněným datem
        window.location.href = `rezervace.php?datum=${info.dateStr}`;
      }
    });

    calendar.render();
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>
