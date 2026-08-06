if (typeof Chart !== 'undefined') {
    const growthEl = document.getElementById('growthChart');
    if (growthEl) {
        new Chart(growthEl, {
            type: 'line',
            data: {
                labels: growthLabels,
                datasets: [{
                    label: 'Clients',
                    data: growthData,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,0.12)',
                    fill: true,
                    tension: 0.42,
                    pointRadius: 4,
                    pointBackgroundColor: '#198754'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 5 } } }
            }
        });
    }

    const statusEl = document.getElementById('statusChart');
    if (statusEl) {
        new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Completed', 'Overdue'],
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#f5b700', '#198754', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '60%',
                plugins: { legend: { position: 'top' } }
            }
        });
    }
}
