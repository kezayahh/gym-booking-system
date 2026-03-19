// Booking Management JS Script

document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.querySelector(".search-bar input");
  const bookingBoxes = document.querySelectorAll(".booking-box");

  // Search feature: filter bookings by booking ID or Facility Name
  searchInput.addEventListener("input", () => {
    const query = searchInput.value.toLowerCase();

    bookingBoxes.forEach(box => {
      const bookingId = box.querySelector(".booking-id").textContent.toLowerCase();
      const facility = box.querySelector("table td:nth-child(4)").textContent.toLowerCase();

      if (bookingId.includes(query) || facility.includes(query)) {
        box.style.display = "";
      } else {
        box.style.display = "none";
      }
    });
  });

  // Logout button behavior
  const logoutBtn = document.querySelector(".logout-btn");
  logoutBtn.addEventListener("click", () => {
    if (confirm("Are you sure you want to log out?")) {
      window.location.href = "index.html";
    }
  });
});
