/**
 * Charts Initialization JavaScript
 * تهيئة وإعداد الرسوم البيانية لعرض إحصائيات الموظفين
 */

// Charts initialization
document.addEventListener('DOMContentLoaded', function() {
    // استخدام المتغير العام المعرف في ملف index.blade.php
    if (!window.employeesData || window.employeesData.length === 0) {
        console.warn('بيانات الموظفين غير متوفرة أو فارغة');
        return;
    }

    const employeesData = window.employeesData;

    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart');
    if (!attendanceCtx) {
        console.warn('عنصر attendanceChart غير موجود في الصفحة');
        return;
    }

    new Chart(attendanceCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['أيام الحضور', 'أيام الغياب'],
            datasets: [{
                data: [
                    employeesData.reduce((sum, emp) => sum + emp.actual_attendance_days, 0),
                    employeesData.reduce((sum, emp) => sum + emp.absences, 0)
                ],
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Leaves Chart
    const leavesCtx = document.getElementById('leavesChart').getContext('2d');
    new Chart(leavesCtx, {
        type: 'bar',
        data: {
            labels: ['الإجازات المأخوذة', 'الإجازات المتبقية', 'الأذونات'],
            datasets: [{
                label: 'عدد الأيام',
                data: [
                    employeesData.reduce((sum, emp) => sum + emp.taken_leaves, 0),
                    employeesData.reduce((sum, emp) => sum + emp.remaining_leaves, 0),
                    employeesData.reduce((sum, emp) => sum + emp.permissions, 0)
                ],
                backgroundColor: ['#17a2b8', '#28a745', '#ffc107'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Time Chart (Delays and Overtime)
    const timeCtx = document.getElementById('timeChart').getContext('2d');
    new Chart(timeCtx, {
        type: 'bar',
        data: {
            labels: employeesData.map(emp => emp.name),
            datasets: [
                {
                    label: 'دقائق التأخير',
                    data: employeesData.map(emp => emp.delays),
                    backgroundColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'ساعات العمل الإضافي',
                    data: employeesData.map(emp => emp.overtimes),
                    backgroundColor: '#007bff',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        autoSkip: true,
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
