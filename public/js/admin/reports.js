// PDF & Excel Export
document.getElementById("exportPDF").addEventListener("click", async () => {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  // Convert logo to Base64
  const getBase64Image = (imgUrl) => {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.crossOrigin = "Anonymous";
      img.src = imgUrl;
      img.onload = () => {
        const canvas = document.createElement("canvas");
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0);
        resolve(canvas.toDataURL("image/jpeg"));
      };
      img.onerror = reject;
    });
  };

  try {
    const logoBase64 = await getBase64Image("images/logo.jpg");

    // Add logo
    doc.addImage(logoBase64, "JPEG", 14, 10, 25, 25);

    // Add title
    doc.setFontSize(16);
    doc.setFont("helvetica", "bold");
    doc.text("City Gymnasium - Reports", 50, 25);

    // Add table
    doc.autoTable({
      html: "#reportTable",
      startY: 45,
      theme: "grid",
      headStyles: { fillColor: [12, 74, 82], textColor: 255 },
      styles: { font: "helvetica", fontSize: 10 },
      margin: { left: 14, right: 14 }
    });

    // Save PDF
    doc.save("CityGymnasium_Reports.pdf");
  } catch (error) {
    console.error("Error generating PDF:", error);
    alert("Failed to generate PDF. Check the logo path.");
  }
});

// Excel Export
document.getElementById("exportExcel").addEventListener("click", () => {
  const table = document.getElementById("reportTable");
  const wb = XLSX.utils.table_to_book(table, { sheet: "Reports" });
  XLSX.writeFile(wb, "CityGymnasium_Reports.xlsx");
});
