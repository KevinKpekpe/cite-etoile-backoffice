import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Title,
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Title
);

export function initDashboardChart() {
    const canvas = document.getElementById('dashboard-chart-canvas');
    if (!canvas) {
        return;
    }

    const rawLabels = canvas.dataset.labels ? JSON.parse(canvas.dataset.labels) : [];
    const rawCurrent = canvas.dataset.current ? JSON.parse(canvas.dataset.current) : [];
    const rawPrevious = canvas.dataset.previous ? JSON.parse(canvas.dataset.previous) : [];

    const isDark = document.documentElement.dataset.bsTheme === 'dark';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const hasPrevious = rawPrevious.some((val) => Number(val) > 0);

    const datasets = [
        {
            label: 'Période sélectionnée',
            data: rawCurrent.map(Number),
            backgroundColor: '#1abb9c',
            hoverBackgroundColor: '#14967d',
            borderRadius: 5,
            borderSkipped: false,
            barPercentage: hasPrevious ? 0.45 : 0.6,
            categoryPercentage: 0.7,
        },
    ];

    if (hasPrevious) {
        datasets.unshift({
            label: 'Période précédente',
            data: rawPrevious.map(Number),
            backgroundColor: 'rgba(66, 153, 225, 0.5)',
            hoverBackgroundColor: 'rgba(66, 153, 225, 0.8)',
            borderRadius: 5,
            borderSkipped: false,
            barPercentage: 0.45,
            categoryPercentage: 0.7,
        });
    }

    const chartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: rawLabels,
            datasets: datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 600,
                easing: 'easeOutQuart',
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        font: {
                            family: 'system-ui, -apple-system, sans-serif',
                            size: 12,
                        },
                    },
                },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#0f172a',
                    titleColor: '#ffffff',
                    bodyColor: '#cbd5e1',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    usePointStyle: true,
                    callbacks: {
                        label(context) {
                            const val = context.raw || 0;
                            const formatted = new Intl.NumberFormat('fr-FR').format(val);
                            return ` ${context.dataset.label}: ${formatted} USD`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11,
                        },
                        maxRotation: 0,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor,
                    },
                    border: {
                        dash: [4, 4],
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11,
                        },
                        callback(value) {
                            if (value >= 1000000) {
                                return `${value / 1000000}M`;
                            }
                            if (value >= 1000) {
                                return `${value / 1000}k`;
                            }
                            return value;
                        },
                    },
                },
            },
        },
    });

    // Theme toggle observer
    const observer = new MutationObserver(() => {
        const dark = document.documentElement.dataset.bsTheme === 'dark';
        const newGrid = dark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
        const newText = dark ? '#94a3b8' : '#64748b';

        chartInstance.options.scales.y.grid.color = newGrid;
        chartInstance.options.scales.x.ticks.color = newText;
        chartInstance.options.scales.y.ticks.color = newText;
        chartInstance.options.plugins.legend.labels.color = newText;
        chartInstance.options.plugins.tooltip.backgroundColor = dark ? '#1e293b' : '#0f172a';
        chartInstance.update();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme'],
    });
}

document.addEventListener('DOMContentLoaded', initDashboardChart);
