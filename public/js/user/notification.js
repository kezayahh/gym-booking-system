document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("notificationsContainer");
  const modal = document.getElementById("bookingModal");
  const closeModal = document.getElementById("closeModal");

  const modalId = document.getElementById("modalId");
  const modalDate = document.getElementById("modalDate");
  const modalTime = document.getElementById("modalTime");
  const modalPurpose = document.getElementById("modalPurpose");
  const modalStatus = document.getElementById("modalStatus");
  const modalAmount = document.getElementById("modalAmount");

  let bookings = JSON.parse(localStorage.getItem("bookings") || "[]");

  if (bookings.length === 0) {
    container.innerHTML = `
      <div class="notification-card">
        <h3>No Notifications</h3>
        <p>You currently have no booking or payment notifications.</p>
      </div>
    `;
    return;
  }

  bookings.forEach((b) => {
    const card = document.createElement("div");
    card.classList.add("notification-card");

    if (b.paid) {
      card.innerHTML = `
        <h3>Booking Confirmed!</h3>
        <p>Your reservation for <strong>${b.purpose}</strong> on <strong>${b.date}</strong> from 
        <strong>${b.start} - ${b.end}</strong> has been successfully confirmed.</p>
        <button onclick="viewBooking('${b.id}')">View Booking Details</button>
      `;
    } else {
      card.classList.add("reminder");
      card.innerHTML = `
        <h3>Payment Reminder</h3>
        <p>Your upcoming booking for <strong>${b.purpose}</strong> on <strong>${b.date}</strong> from 
        <strong>${b.start} - ${b.end}</strong> is still pending payment.</p>
        <p><strong>Amount Due:</strong> ₱350</p>
        <button onclick="goToPayment()">Pay Now</button>
      `;
    }

    container.appendChild(card);
  });

  // Close modal when X clicked
  closeModal.onclick = () => (modal.style.display = "none");

  // Close modal on background click
  window.onclick = (e) => {
    if (e.target === modal) modal.style.display = "none";
  };

  // Make viewBooking accessible
  window.viewBooking = (id) => {
    const booking = bookings.find((b) => b.id == id);
    if (!booking) return;

    modalId.textContent = booking.id;
    modalDate.textContent = booking.date;
    modalTime.textContent = `${booking.start} - ${booking.end}`;
    modalPurpose.textContent = booking.purpose;
    modalStatus.textContent = booking.paid ? "Confirmed" : "Pending";
    modalAmount.textContent = booking.paid ? "₱350 (Paid)" : "₱350 (Unpaid)";

    modal.style.display = "block";
  };
});

function goToPayment() {
  window.location.href = "payment.html";
}
