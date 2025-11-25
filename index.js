// Function to show specific section based on button clicked
function showSection(sectionId) {
    // Hide all sections first
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => section.style.display = 'none');

    // Display the selected section
    const targetSection = document.getElementById(sectionId);
    targetSection.style.display = 'block';
}

// Function to simulate tracking report progress by ID
function trackReport() {
    const reportId = document.getElementById('reportId').value;
    const reportStatus = document.getElementById('reportStatus');

    // Placeholder logic for tracking (can be replaced with real backend data)
    if (reportId === "123") {
        reportStatus.innerText = "Status: In Progress";
    } else if (reportId === "456") {
        reportStatus.innerText = "Status: Resolved";
    } else if (reportId === "789") {
        reportStatus.innerText = "Status: Pending";
    } else {
        reportStatus.innerText = "Report not found. Please check the ID and try again.";
    }
}
