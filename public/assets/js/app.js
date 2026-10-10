// Enterprise HRIS Client Script
document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // 2. Live Digital Clock Widget
    const clockElement = document.getElementById('liveClock');
    if (clockElement) {
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            clockElement.textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 3. Auto-dismiss alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 4. File Drop Area Interaction
    const fileDropArea = document.querySelector('.file-drop-area');
    const fileInput = document.querySelector('.file-drop-area input[type="file"]');
    const fileNameDisplay = document.getElementById('selectedFileName');

    if (fileDropArea && fileInput) {
        ['dragenter', 'dragover'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                fileDropArea.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                fileDropArea.classList.remove('dragover');
            }, false);
        });

        fileDropArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) {
                fileInput.files = files;
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = files[0].name;
                }
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length && fileNameDisplay) {
                fileNameDisplay.textContent = fileInput.files[0].name;
            }
        });
    }
});
