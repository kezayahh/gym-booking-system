document.addEventListener('DOMContentLoaded', function () {
  const monthYear = document.getElementById('monthYear');
  const calendar = document.getElementById('calendar');
  const prevMonth = document.getElementById('prevMonth');
  const nextMonth = document.getElementById('nextMonth');

  let date = new Date();
  let currentMonth = date.getMonth();
  let currentYear = date.getFullYear();

  // Example booked days
  const bookedDates = ["2025-11-17", "2025-11-20", "2025-11-25"];

  function renderCalendar(month, year) {
    calendar.innerHTML = "";
    const firstDay = new Date(year, month).getDay();
    const daysInMonth = 32 - new Date(year, month, 32).getDate();

    monthYear.textContent = `${new Date(year, month).toLocaleString('default', { month: 'long' })} ${year}`;

    for (let i = 0; i < firstDay; i++) {
      const emptyCell = document.createElement('div');
      calendar.appendChild(emptyCell);
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const dayCell = document.createElement('div');
      const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

      if (bookedDates.includes(dateString)) {
        dayCell.classList.add('booked');
        dayCell.title = "Booked";
      } else {
        dayCell.classList.add('available');
        dayCell.title = "Available";
      }

      dayCell.textContent = day;
      calendar.appendChild(dayCell);
    }
  }

  renderCalendar(currentMonth, currentYear);

  prevMonth.addEventListener('click', () => {
    currentMonth--;
    if (currentMonth < 0) {
      currentMonth = 11;
      currentYear--;
    }
    renderCalendar(currentMonth, currentYear);
  });

  nextMonth.addEventListener('click', () => {
    currentMonth++;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear++;
    }
    renderCalendar(currentMonth, currentYear);
  });

  // ===== MODAL FUNCTIONALITY =====
  const modal = document.getElementById("modal");
  const modalTitle = document.getElementById("modalTitle");
  const modalMessage = document.getElementById("modalMessage");
  const closeModal = document.getElementById("closeModal");

  function openModal(title, message) {
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modal.style.display = "flex";
  }

  closeModal.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) modal.style.display = "none";
  });

  // CARD CLICK ACTIONS
  document.querySelectorAll(".card")[0].addEventListener("click", () => {
    openModal("Your Bookings", "Here you can view all your confirmed and pending bookings.");
  });

  document.querySelectorAll(".card")[1].addEventListener("click", () => {
    openModal("Check Availability", "Click any green date in the calendar to check availability.");
  });

  document.querySelectorAll(".card")[2].addEventListener("click", () => {
    openModal("Track Status", "Track payment and approval status in real-time.");
  });

  document.querySelectorAll(".card")[3].addEventListener("click", () => {
    openModal("Cancel / Reschedule", "You can request cancellation or rescheduling before event date.");
  });
});
