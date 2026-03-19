document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('#pendingPayments tbody');
  const gcashModal = document.getElementById('gcashModal');
  const closeModal = document.getElementById('closeModal');
  const gcashForm = document.getElementById('gcashForm');
  const successModal = document.getElementById('successModal');
  const paymentDetails = document.getElementById('paymentDetails');
  const doneBtn = document.getElementById('doneBtn');

  const refundModal = document.getElementById('refundModal');
  const closeRefund = document.getElementById('closeRefund');
  const refundReasonInput = document.getElementById('refundReason');
  const submitRefund = document.getElementById('submitRefund');

  let refundTargetID = null;

  let bookings = JSON.parse(localStorage.getItem('bookings') || '[]');
  bookings = bookings.map(b => ({ refundRequested: b.refundRequested ?? false, ...b }));
  localStorage.setItem('bookings', JSON.stringify(bookings));

  function renderTable() {
    tableBody.innerHTML = '';
    bookings.forEach(b => {
      const tr = document.createElement('tr');

      let actionHTML = '';

      if (!b.paid) {
        actionHTML = `<button class="payBtn" data-id="${b.id}">Pay Now</button>`;
      } 
      else if (b.paid && !b.refundRequested) {
        actionHTML = `
          <div class="action-btns">
            <span class="done-label"><i class="fa-solid fa-circle-check"></i> Done</span>
            <button class="refundBtn" data-id="${b.id}">Refund</button>
          </div>`;
      } 
      else {
        actionHTML = `<span class="requested-badge">Refund Requested</span>`;
      }

      tr.innerHTML = `
        <td>${b.id}</td>
        <td>${b.name}</td>
        <td>${b.date}</td>
        <td>${b.start} - ${b.end}</td>
        <td>${b.purpose}</td>
        <td style="color:${b.paid ? 'green' : 'red'};">${b.paid ? 'Paid' : 'Pending'}</td>
        <td>${actionHTML}</td>
      `;
      tableBody.appendChild(tr);
    });
  }

  tableBody.addEventListener('click', e => {
    if (e.target.classList.contains('payBtn')) {
      gcashModal.style.display = 'flex';
      gcashForm.dataset.id = e.target.dataset.id;
    }
    if (e.target.classList.contains('refundBtn')) {
      refundTargetID = Number(e.target.dataset.id);
      refundModal.style.display = 'flex';
    }
  });

  submitRefund.addEventListener('click', () => {
    let reason = refundReasonInput.value.trim();
    if (reason === "") return alert("Please enter a reason.");
    
    bookings = bookings.map(b => b.id === refundTargetID ? { ...b, refundRequested: true, refundReason: reason } : b);
    localStorage.setItem('bookings', JSON.stringify(bookings));
    refundModal.style.display = 'none';
    refundReasonInput.value = "";
    renderTable();
  });

  closeRefund.addEventListener('click', () => refundModal.style.display = 'none');

  closeModal.addEventListener('click', () => gcashModal.style.display = 'none');

  gcashForm.addEventListener('submit', e => {
    e.preventDefault();
    const id = Number(gcashForm.dataset.id);
    const receipt = document.getElementById('receipt').files[0];
    const reference = document.getElementById('reference').value;

    bookings = bookings.map(b =>
      b.id === id ? { ...b, paid: true, receiptName: receipt.name, reference } : b
    );

    localStorage.setItem('bookings', JSON.stringify(bookings));

    const booking = bookings.find(b => b.id === id);
    paymentDetails.innerHTML = `
      <p><strong>Booking ID:</strong> ${booking.id}</p>
      <p><strong>Name:</strong> ${booking.name}</p>
      <p><strong>Status:</strong> Paid ✅</p>
    `;

    gcashModal.style.display = 'none';
    successModal.style.display = 'flex';
    gcashForm.reset();
    renderTable();
  });

  doneBtn.addEventListener('click', () => successModal.style.display = 'none');

  renderTable();
});
