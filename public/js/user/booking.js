document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('bookingForm');
  const confirmation = document.getElementById('confirmation');
  const paymentBtnContainer = document.getElementById('paymentBtnContainer');
  const proceedPayment = document.getElementById('proceedPayment');

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const name = document.getElementById('name').value.trim();
    const date = document.getElementById('date').value;
    const start = document.getElementById('startTime').value;
    const end = document.getElementById('endTime').value;
    const purpose = document.getElementById('purpose').value.trim();

    if (!name || !date || !start || !end || !purpose) {
      alert('Please complete all fields before reserving.');
      return;
    }

    if (start >= end) {
      alert('End time must be after start time.');
      return;
    }

    let bookings = JSON.parse(localStorage.getItem('bookings') || '[]');

    // Prevent duplicate bookings
    const duplicate = bookings.some(b =>
      b.name === name && b.date === date && b.start === start && b.end === end && !b.paid
    );

    if (duplicate) {
      alert('You have already reserved this slot. Check your pending payments.');
      return;
    }

    // Add new booking
    bookings.push({
      id: Date.now(),
      name,
      date,
      start,
      end,
      purpose,
      status: 'approved',
      paid: false
    });

    localStorage.setItem('bookings', JSON.stringify(bookings));

    confirmation.style.display = 'block';
    confirmation.innerHTML = `
      <p><strong>✅ Reservation Pending Payment</strong></p>
      <p><strong>Name:</strong> ${name}</p>
      <p><strong>Date:</strong> ${date}</p>
      <p><strong>Time:</strong> ${start} - ${end}</p>
      <p><strong>Purpose:</strong> ${purpose}</p>
      <p style="color:#3086FE;">Please complete your payment to confirm your booking.</p>
    `;

    paymentBtnContainer.style.display = 'block';
  });

  proceedPayment.addEventListener('click', () => {
    window.location.href = 'payment.html';
  });
});
