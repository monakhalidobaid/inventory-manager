document.addEventListener("DOMContentLoaded", () => {
  const reportOptions = document.querySelectorAll(".report-option");
  const excelBtn = document.querySelector(".filter-actions button:nth-child(2)");
  const resetBtn = document.querySelector(".filter-actions button:nth-child(1)");
  const dateInputs = document.querySelectorAll('.filter-group input[type="date"]');
  const dateFromInput = dateInputs[0];
  const dateToInput = dateInputs[1];
  const tableBody = document.querySelector("#itemTable tbody");

  let selectedReportType = document.querySelector(".report-option.active")?.dataset.report || "";

  // ===== Handle report type selection =====
  reportOptions.forEach(option => {
    option.addEventListener("click", () => {
      reportOptions.forEach(o => o.classList.remove("active"));
      option.classList.add("active");
      selectedReportType = option.dataset.report;
      console.log(`Selected report: ${selectedReportType}`);
    });
  });

  // ===== Validate date format =====
  function isValidDateFormat(dateStr) {
    return /^\d{4}-\d{2}-\d{2}$/.test(dateStr);
  }

  // ===== Get report name =====
  function getReportName(reportType) {
    const names = {
      'assets_hardware': 'Hardware Assets Report',
      'assets_software': 'Software Assets Report',
      'expired_warranty': 'Expired Warranties Report',
      'under_warranty': 'Under Warranty Report'
    };
    return names[reportType] || reportType;
  }

  // ===== Load recent reports =====
  async function loadRecentReports() {
    try {
      const response = await fetch('/inventory_manager/api/report/get_recent_reports.php');
      const data = await response.json();

      if (data.status === "success") {
        tableBody.innerHTML = "";
       
        if (data.reports.length === 0) {
          tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center">No recent reports</td></tr>`;
          return;
        }

        data.reports.forEach(report => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td>${report.report_name}</td>
            <td>${report.generation_date}</td>
            <td>${report.generated_by}</td>
          `;
          tableBody.appendChild(tr);
        });
      }
    } catch (error) {
      console.error("Failed to load recent reports:", error);
    }
  }

  // ===== Export to Excel =====
  function exportToExcel() {
    if (!selectedReportType) {
      showToast("Please select a report type first.", "error");
      return;
    }

    const fromDate = dateFromInput.value;
    const toDate = dateToInput.value;

    // Validate date format
    if (fromDate && !isValidDateFormat(fromDate)) {
      showToast("Invalid 'From' date format. Use YYYY-MM-DD.", "error");
      return;
    }

    if (toDate && !isValidDateFormat(toDate)) {
      showToast("Invalid 'To' date format. Use YYYY-MM-DD.", "error");
      return;
    }

    // Check logical range
    if (fromDate && toDate && new Date(fromDate) > new Date(toDate)) {
      showToast("'From' date cannot be after 'To' date.", "error");
      return;
    }

    // Show loading message
    showToast("Generating Excel file...", "success");

    // Build URL with parameters
    const url = `/inventory_manager/api/report/export_to_excel.php?reportType=${selectedReportType}&from=${fromDate}&to=${toDate}`;
    
    // Create a temporary link and trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = ''; // Browser will use filename from Content-Disposition header
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Reload recent reports after a short delay
    setTimeout(() => {
      loadRecentReports();
      showToast("Excel file downloaded successfully!", "success");
    }, 1000);
  }

  // ===== Excel Button Click =====
  excelBtn.addEventListener("click", exportToExcel);

  // ===== Reset Button Click =====
  resetBtn.addEventListener("click", () => {
    dateFromInput.value = "";
    dateToInput.value = "";
    showToast("Filters have been reset.", "success");
    console.log("Filters reset.");
  });

  // Load recent reports on page load
  loadRecentReports();
});